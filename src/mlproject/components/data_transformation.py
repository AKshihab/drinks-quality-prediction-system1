import pandas as pd
from sklearn.model_selection import train_test_split

from mlproject import logger
from mlproject.entity.config_entity import DataTransformationConfig


class DataTransformation:
    def __init__(self, config: DataTransformationConfig):
        self.config = config

    def train_test_splitting(self) -> None:
        self.config.root_dir.mkdir(parents=True, exist_ok=True)
        data = pd.read_csv(self.config.data_path)
        logger.info("Input data shape: %s", data.shape)

        train, test = train_test_split(
            data,
            test_size=self.config.test_size,
            random_state=self.config.random_state,
            stratify=data[self.config.target_column],
        )

        train_path = self.config.root_dir / "train.csv"
        test_path = self.config.root_dir / "test.csv"
        train.to_csv(train_path, index=False)
        test.to_csv(test_path, index=False)

        logger.info("Training data shape: %s", train.shape)
        logger.info("Test data shape: %s", test.shape)
        logger.info("Saved transformed datasets to %s", self.config.root_dir)
