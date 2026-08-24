from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class DataIngestionConfig:
    root_dir: Path
    source_URL: str
    local_data_file: Path
    unzip_dir: Path


@dataclass(frozen=True)
class DataValidationConfig:
    root_dir: Path
    unzip_data_dir: Path
    status_file: Path
    all_schema: dict[str, str]
    target_column: str
    allow_missing_values: bool
    allow_unexpected_columns: bool


@dataclass(frozen=True)
class DataTransformationConfig:
    root_dir: Path
    data_path: Path
    target_column: str
    feature_columns: list[str]
    test_size: float
    random_state: int


@dataclass(frozen=True)
class ModelTrainerConfig:
    root_dir: Path
    train_data_path: Path
    model_name: str
    metadata_name: str
    model_display_name: str
    model_version: str
    target_column: str
    validation_size: float
    random_state: int
    logistic_cv: int
    logistic_max_iter: int
    elastic_net_alpha: float
    elastic_net_l1_ratio: float
    metric_average: str
    zero_division: int


@dataclass(frozen=True)
class ModelEvaluationConfig:
    path_of_model: Path
    test_data_path: Path
    metric_file_name: Path
    target_column: str
    metric_average: str
    zero_division: int
