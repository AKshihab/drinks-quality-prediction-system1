from mlproject import logger
from mlproject.components.model_trainer import ModelTrainer
from mlproject.config.configuration import ConfigurationManager


class ModelTrainingPipeline:
    def __init__(self):
        pass

    def main(self) -> None:
        try:
            config = ConfigurationManager()
            component_config = config.get_model_trainer_config()
            ModelTrainer(config=component_config).train()
            logger.info("Model training pipeline completed")
        except Exception:
            logger.exception("Model training pipeline failed")
            raise


if __name__ == "__main__":
    ModelTrainingPipeline().main()
