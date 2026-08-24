import json
import math
from collections.abc import Mapping
from numbers import Real
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd


API_TO_MODEL_FEATURES = {
    "fixed_acidity": "fixed acidity",
    "volatile_acidity": "volatile acidity",
    "citric_acid": "citric acid",
    "residual_sugar": "residual sugar",
    "chlorides": "chlorides",
    "free_sulfur_dioxide": "free sulfur dioxide",
    "total_sulfur_dioxide": "total sulfur dioxide",
    "density": "density",
    "ph": "pH",
    "sulphates": "sulphates",
    "alcohol": "alcohol",
}


class PredictionValidationError(ValueError):
    """Raised when a prediction request does not match the model contract."""


def quality_label(predicted_quality: int) -> str:
    if predicted_quality >= 7:
        return "Excellent"
    if predicted_quality >= 6:
        return "Good"
    if predicted_quality >= 5:
        return "Average"
    return "Poor"


class PredictionService:
    def __init__(self, model_path: Path, metadata_path: Path):
        self.model_path = Path(model_path)
        self.metadata_path = Path(metadata_path)
        self.model = joblib.load(self.model_path)

        with self.metadata_path.open("r", encoding="utf-8") as metadata_file:
            self.metadata = json.load(metadata_file)

        self._validate_artifacts()

    def _validate_artifacts(self) -> None:
        expected_model_features = list(API_TO_MODEL_FEATURES.values())
        metadata_features = self.metadata.get("feature_names")
        model_features = list(getattr(self.model, "feature_names_in_", []))

        if metadata_features != expected_model_features:
            raise ValueError(
                "Model metadata does not contain the required 11-feature contract"
            )
        if model_features != expected_model_features:
            raise ValueError(
                "Trained model does not contain the required 11-feature contract"
            )

        feature_ranges = self.metadata.get("feature_ranges")
        if not isinstance(feature_ranges, dict) or set(feature_ranges) != set(
            expected_model_features
        ):
            raise ValueError("Model metadata has incomplete feature ranges")

        classes = self.metadata.get("classes")
        if not isinstance(classes, list) or not classes:
            raise ValueError("Model metadata has no target classes")
        self.classes = np.asarray([int(value) for value in classes], dtype=int)

        for key in ("model_name", "model_version", "algorithm"):
            if not isinstance(self.metadata.get(key), str) or not self.metadata[key]:
                raise ValueError(f"Model metadata is missing {key}")

    @property
    def model_info(self) -> dict[str, str]:
        return {
            "name": self.metadata["model_name"],
            "version": self.metadata["model_version"],
            "algorithm": self.metadata["algorithm"],
        }

    @property
    def model_feature_names(self) -> list[str]:
        return list(self.metadata["feature_names"])

    def predict(self, values: Mapping[str, Any]) -> dict[str, Any]:
        if not isinstance(values, Mapping):
            raise PredictionValidationError("The JSON body must be an object.")

        expected_fields = set(API_TO_MODEL_FEATURES)
        submitted_fields = set(values)
        missing_fields = sorted(expected_fields - submitted_fields)
        extra_fields = sorted(submitted_fields - expected_fields)
        if missing_fields:
            raise PredictionValidationError(
                "Missing prediction fields: " + ", ".join(missing_fields)
            )
        if extra_fields:
            raise PredictionValidationError(
                "Unexpected prediction fields: " + ", ".join(extra_fields)
            )

        model_values: dict[str, float] = {}
        for api_name, model_name in API_TO_MODEL_FEATURES.items():
            raw_value = values[api_name]
            if isinstance(raw_value, bool) or not isinstance(raw_value, Real):
                raise PredictionValidationError(
                    f"{api_name} must be a JSON number."
                )

            value = float(raw_value)
            if not math.isfinite(value):
                raise PredictionValidationError(f"{api_name} must be finite.")
            if value < 0:
                raise PredictionValidationError(
                    f"{api_name} cannot be negative."
                )

            feature_range = self.metadata["feature_ranges"][model_name]
            minimum = float(feature_range["min"])
            maximum = float(feature_range["max"])
            if value < minimum or value > maximum:
                raise PredictionValidationError(
                    f"{api_name} must be between {minimum:g} and {maximum:g}."
                )
            model_values[model_name] = value

        prediction_input = pd.DataFrame(
            [[model_values[name] for name in self.model_feature_names]],
            columns=self.model_feature_names,
        )
        raw_prediction = float(self.model.predict(prediction_input)[0])
        if not math.isfinite(raw_prediction):
            raise RuntimeError("The model returned a non-finite prediction")

        if raw_prediction not in self.classes:
            raw_prediction = float(
                np.clip(
                    np.rint(raw_prediction),
                    self.classes.min(),
                    self.classes.max(),
                )
            )
        predicted_quality = int(raw_prediction)
        if predicted_quality not in self.classes:
            raise RuntimeError("The model returned an unsupported quality class")

        return {
            "predicted_quality": predicted_quality,
            "quality_label": quality_label(predicted_quality),
            "model": self.model_info,
        }
