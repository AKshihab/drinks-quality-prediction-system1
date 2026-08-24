import pandas as pd

from mlproject import logger
from mlproject.entity.config_entity import DataValidationConfig


class DataValidation:
    def __init__(self, config: DataValidationConfig):
        self.config = config

    def validate_all_columns(self) -> bool:
        self.config.root_dir.mkdir(parents=True, exist_ok=True)
        data = pd.read_csv(self.config.unzip_data_dir)
        expected_columns = self.config.all_schema

        missing_columns = [
            column for column in expected_columns if column not in data.columns
        ]
        unexpected_columns = [
            column for column in data.columns if column not in expected_columns
        ]
        invalid_types = {
            column: {
                "expected": expected_type,
                "actual": str(data[column].dtype),
            }
            for column, expected_type in expected_columns.items()
            if column in data.columns and str(data[column].dtype) != expected_type
        }
        missing_value_count = int(data.isna().sum().sum())

        problems = []
        if missing_columns:
            problems.append(f"missing columns: {missing_columns}")
        if unexpected_columns and not self.config.allow_unexpected_columns:
            problems.append(f"unexpected columns: {unexpected_columns}")
        if invalid_types:
            problems.append(f"invalid types: {invalid_types}")
        if missing_value_count and not self.config.allow_missing_values:
            problems.append(f"missing values: {missing_value_count}")
        if self.config.target_column not in data.columns:
            problems.append(f"missing target column: {self.config.target_column}")

        validation_status = not problems
        self.config.status_file.write_text(
            f"Validation status: {validation_status}\n",
            encoding="utf-8",
        )

        if not validation_status:
            raise ValueError("Dataset validation failed; " + "; ".join(problems))

        logger.info(
            "Dataset validation passed for %s rows and %s columns",
            data.shape[0],
            data.shape[1],
        )
        return True
