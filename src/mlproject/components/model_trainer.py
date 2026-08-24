from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.linear_model import ElasticNet, LogisticRegressionCV
from sklearn.metrics import accuracy_score, f1_score, precision_score, recall_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler

from mlproject import logger
from mlproject.entity.config_entity import ModelTrainerConfig
from mlproject.utils.common import save_json


class ModelTrainer:
    def __init__(self, config: ModelTrainerConfig):
        self.config = config

    @staticmethod
    def _class_predictions(predictions: np.ndarray, classes: np.ndarray) -> np.ndarray:
        predictions = np.asarray(predictions)
        if not np.all(np.isin(predictions, classes)):
            predictions = np.clip(
                np.rint(predictions),
                classes.min(),
                classes.max(),
            )
        return predictions.astype(classes.dtype)

    def _metrics(self, actual: pd.Series, predicted: np.ndarray) -> dict[str, float]:
        return {
            "accuracy": float(accuracy_score(actual, predicted)),
            "precision": float(
                precision_score(
                    actual,
                    predicted,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
            "recall": float(
                recall_score(
                    actual,
                    predicted,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
            "f1_score": float(
                f1_score(
                    actual,
                    predicted,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
        }

    def train(self) -> str:
        self.config.root_dir.mkdir(parents=True, exist_ok=True)
        train_data = pd.read_csv(self.config.train_data_path)
        logger.info("Training data shape: %s", train_data.shape)

        if self.config.target_column not in train_data.columns:
            raise ValueError(
                f"Target column '{self.config.target_column}' is missing from training data"
            )

        features = train_data.drop(columns=[self.config.target_column])
        target = train_data[self.config.target_column]
        classes = np.sort(target.unique())
        x_fit, x_validation, y_fit, y_validation = train_test_split(
            features,
            target,
            test_size=self.config.validation_size,
            random_state=self.config.random_state,
            stratify=target,
        )

        smallest_class_size = int(y_fit.value_counts().min())
        cross_validation_folds = min(self.config.logistic_cv, smallest_class_size)
        if cross_validation_folds < 2:
            raise ValueError("Training data needs at least two samples in every class")

        candidates = {
            "LogisticRegressionCV": make_pipeline(
                StandardScaler(),
                LogisticRegressionCV(
                    cv=cross_validation_folds,
                    max_iter=self.config.logistic_max_iter,
                    random_state=self.config.random_state,
                ),
            ),
            "ElasticNet": ElasticNet(
                alpha=self.config.elastic_net_alpha,
                l1_ratio=self.config.elastic_net_l1_ratio,
                random_state=self.config.random_state,
            ),
        }

        candidate_results = {}
        for model_name, model in candidates.items():
            model.fit(x_fit, y_fit)
            predictions = self._class_predictions(model.predict(x_validation), classes)
            candidate_results[model_name] = self._metrics(y_validation, predictions)
            logger.info(
                "%s validation metrics: %s",
                model_name,
                candidate_results[model_name],
            )

        selected_model_name = max(
            candidate_results,
            key=lambda name: (
                candidate_results[name]["f1_score"],
                candidate_results[name]["accuracy"],
            ),
        )
        selected_model = candidates[selected_model_name]
        selected_model.fit(features, target)

        model_path = Path(self.config.root_dir) / self.config.model_name
        joblib.dump(selected_model, model_path)
        metadata_path = Path(self.config.root_dir) / self.config.metadata_name
        feature_ranges = {
            column: {
                "min": float(features[column].min()),
                "max": float(features[column].max()),
            }
            for column in features.columns
        }
        save_json(
            metadata_path,
            {
                "model_name": self.config.model_display_name,
                "model_version": self.config.model_version,
                "algorithm": selected_model_name,
                "target_column": self.config.target_column,
                "feature_names": features.columns.tolist(),
                "feature_ranges": feature_ranges,
                "classes": [int(value) for value in classes],
            },
        )
        logger.info("Selected model: %s", selected_model_name)
        logger.info("Saved model to %s", model_path)
        logger.info("Saved model metadata to %s", metadata_path)
        logger.info("Model training completed")
        return selected_model_name
