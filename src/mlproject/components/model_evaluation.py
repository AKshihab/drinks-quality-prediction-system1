import joblib
import numpy as np
import pandas as pd
from sklearn.metrics import accuracy_score, f1_score, precision_score, recall_score

from mlproject import logger
from mlproject.entity.config_entity import ModelEvaluationConfig
from mlproject.utils.common import save_json


class ModelEvaluation:
    def __init__(self, config: ModelEvaluationConfig):
        self.config = config

    def evaluate_model(self) -> dict[str, float]:
        self.config.metric_file_name.parent.mkdir(parents=True, exist_ok=True)
        model = joblib.load(self.config.path_of_model)
        test_data = pd.read_csv(self.config.test_data_path)

        if self.config.target_column not in test_data.columns:
            raise ValueError(
                f"Target column '{self.config.target_column}' is missing from test data"
            )

        features = test_data.drop(columns=[self.config.target_column])
        actual = test_data[self.config.target_column]
        classes = np.sort(actual.unique())
        predictions = np.asarray(model.predict(features))
        if not np.all(np.isin(predictions, classes)):
            predictions = np.clip(
                np.rint(predictions),
                classes.min(),
                classes.max(),
            )
        predictions = predictions.astype(classes.dtype)

        scores = {
            "accuracy": float(accuracy_score(actual, predictions)),
            "precision": float(
                precision_score(
                    actual,
                    predictions,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
            "recall": float(
                recall_score(
                    actual,
                    predictions,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
            "f1_score": float(
                f1_score(
                    actual,
                    predictions,
                    average=self.config.metric_average,
                    zero_division=self.config.zero_division,
                )
            ),
        }

        save_json(self.config.metric_file_name, scores)
        logger.info("Evaluation data shape: %s", test_data.shape)
        logger.info("Model evaluation metrics: %s", scores)
        logger.info("Saved evaluation metrics to %s", self.config.metric_file_name)
        return scores
