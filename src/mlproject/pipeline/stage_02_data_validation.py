from mlproject import logger
from mlproject.components.data_validation import DataValidation
from mlproject.config.configuration import ConfigurationManager


class DataValidationTrainingPipeline:
    def __init__(self):
        pass

    def main(self) -> None:
        try:
            config = ConfigurationManager()
            component_config = config.get_data_validation_config()
            DataValidation(config=component_config).validate_all_columns()
            logger.info("Data validation pipeline completed")
        except Exception:
            logger.exception("Data validation pipeline failed")
            raise


if __name__ == "__main__":
    DataValidationTrainingPipeline().main()
