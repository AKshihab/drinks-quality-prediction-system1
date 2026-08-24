from mlproject import logger
from mlproject.components.data_transformation import DataTransformation
from mlproject.config.configuration import ConfigurationManager


class DataTransformationTrainingPipeline:
    def __init__(self):
        pass

    def main(self) -> None:
        try:
            config = ConfigurationManager()
            component_config = config.get_data_transformation_config()
            DataTransformation(config=component_config).train_test_splitting()
            logger.info("Data transformation pipeline completed")
        except Exception:
            logger.exception("Data transformation pipeline failed")
            raise


if __name__ == "__main__":
    DataTransformationTrainingPipeline().main()
