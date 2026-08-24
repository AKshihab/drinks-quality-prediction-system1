import json
import unittest
from pathlib import Path

from mlproject.prediction_service import (
    API_TO_MODEL_FEATURES,
    PredictionService,
    PredictionValidationError,
)


PROJECT_ROOT = Path(__file__).resolve().parents[1]
SAMPLE_VALUES = {
    "fixed_acidity": 7.4,
    "volatile_acidity": 0.70,
    "citric_acid": 0.00,
    "residual_sugar": 1.9,
    "chlorides": 0.076,
    "free_sulfur_dioxide": 11,
    "total_sulfur_dioxide": 34,
    "density": 0.9978,
    "ph": 3.51,
    "sulphates": 0.56,
    "alcohol": 9.4,
}


class PredictionServiceTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.service = PredictionService(
            PROJECT_ROOT / "artifacts" / "model_trainer" / "model.pkl",
            PROJECT_ROOT
            / "artifacts"
            / "model_trainer"
            / "model_metadata.json",
        )

    def test_artifact_has_exact_measurement_features(self):
        self.assertEqual(
            self.service.model_feature_names,
            list(API_TO_MODEL_FEATURES.values()),
        )
        self.assertNotIn("Id", self.service.model_feature_names)

        with (
            PROJECT_ROOT / "artifacts" / "model_evaluation" / "scores.json"
        ).open("r", encoding="utf-8") as scores_file:
            scores = json.load(scores_file)
        self.assertAlmostEqual(scores["accuracy"], 0.6083916083916084)

    def test_sample_predicts_average_quality_five(self):
        result = self.service.predict(SAMPLE_VALUES)

        self.assertEqual(result["predicted_quality"], 5)
        self.assertEqual(result["quality_label"], "Average")
        self.assertEqual(result["model"]["name"], "Drinks Quality Classifier")
        self.assertEqual(result["model"]["version"], "1.0")
        self.assertEqual(result["model"]["algorithm"], "LogisticRegressionCV")

    def test_json_field_order_does_not_change_prediction(self):
        reordered_values = dict(reversed(list(SAMPLE_VALUES.items())))
        self.assertEqual(
            self.service.predict(reordered_values),
            self.service.predict(SAMPLE_VALUES),
        )

    def test_missing_field_is_rejected(self):
        values = dict(SAMPLE_VALUES)
        del values["alcohol"]

        with self.assertRaises(PredictionValidationError):
            self.service.predict(values)

    def test_extra_field_is_rejected(self):
        values = dict(SAMPLE_VALUES, Id=1)

        with self.assertRaises(PredictionValidationError):
            self.service.predict(values)

    def test_non_numeric_non_finite_negative_and_out_of_range_are_rejected(self):
        invalid_values = ["7.4", float("inf"), -1, 1000]

        for value in invalid_values:
            with self.subTest(value=value):
                values = dict(SAMPLE_VALUES, fixed_acidity=value)
                with self.assertRaises(PredictionValidationError):
                    self.service.predict(values)


if __name__ == "__main__":
    unittest.main()
