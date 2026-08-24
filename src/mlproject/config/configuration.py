from pathlib import Path

from mlproject.constants import CONFIG_FILE_PATH, PARAMS_FILE_PATH, SCHEMA_FILE_PATH
from mlproject.entity.config_entity import (
    DataIngestionConfig,
    DataTransformationConfig,
    DataValidationConfig,
    ModelEvaluationConfig,
    ModelTrainerConfig,
)
from mlproject.utils.common import create_directories, read_yaml


class ConfigurationManager:
    def __init__(
        self,
        config_filepath: Path = CONFIG_FILE_PATH,
        params_filepath: Path = PARAMS_FILE_PATH,
        schema_filepath: Path = SCHEMA_FILE_PATH,
    ):
        self.config = read_yaml(config_filepath)
        self.params = read_yaml(params_filepath)
        self.schema = read_yaml(schema_filepath)
        create_directories([self.config.artifacts_root])

    def get_data_ingestion_config(self) -> DataIngestionConfig:
        config = self.config.data_ingestion
        create_directories([config.root_dir])
        return DataIngestionConfig(
            root_dir=Path(config.root_dir),
            source_URL=config.source_URL,
            local_data_file=Path(config.local_data_file),
            unzip_dir=Path(config.unzip_dir),
        )

    def get_data_validation_config(self) -> DataValidationConfig:
        config = self.config.data_validation
        validation = self.schema.VALIDATION
        create_directories([config.root_dir])
        return DataValidationConfig(
            root_dir=Path(config.root_dir),
            unzip_data_dir=Path(config.unzip_data_dir),
            status_file=Path(config.status_file),
            all_schema=dict(self.schema.COLUMNS),
            target_column=self.schema.TARGET_COLUMN.name,
            allow_missing_values=validation.allow_missing_values,
            allow_unexpected_columns=validation.allow_unexpected_columns,
        )

    def get_data_transformation_config(self) -> DataTransformationConfig:
        config = self.config.data_transformation
        training = self.params.TRAINING
        create_directories([config.root_dir])
        return DataTransformationConfig(
            root_dir=Path(config.root_dir),
            data_path=Path(config.data_path),
            target_column=self.schema.TARGET_COLUMN.name,
            feature_columns=list(self.schema.FEATURE_COLUMNS),
            test_size=float(training.test_size),
            random_state=int(training.random_state),
        )

    def get_model_trainer_config(self) -> ModelTrainerConfig:
        config = self.config.model_trainer
        training = self.params.TRAINING
        model = self.params.MODEL
        evaluation = self.params.EVALUATION
        create_directories([config.root_dir])
        return ModelTrainerConfig(
            root_dir=Path(config.root_dir),
            train_data_path=Path(config.train_data_path),
            model_name=config.model_name,
            metadata_name=config.metadata_name,
            model_display_name=config.model_display_name,
            model_version=str(config.model_version),
            target_column=config.target_column,
            validation_size=float(training.validation_size),
            random_state=int(training.random_state),
            logistic_cv=int(model.logistic_regression.cv),
            logistic_max_iter=int(model.logistic_regression.max_iter),
            elastic_net_alpha=float(model.elastic_net.alpha),
            elastic_net_l1_ratio=float(model.elastic_net.l1_ratio),
            metric_average=evaluation.average,
            zero_division=int(evaluation.zero_division),
        )

    def get_model_evaluation_config(self) -> ModelEvaluationConfig:
        config = self.config.model_evaluation
        evaluation = self.params.EVALUATION
        create_directories([config.root_dir])
        return ModelEvaluationConfig(
            path_of_model=Path(config.path_of_model),
            test_data_path=Path(config.test_data_path),
            metric_file_name=Path(config.metric_file_name),
            target_column=config.target_column,
            metric_average=evaluation.average,
            zero_division=int(evaluation.zero_division),
        )
