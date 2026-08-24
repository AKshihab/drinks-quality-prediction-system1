from mlproject import logger
from mlproject.components.model_evaluation import ModelEvaluation
from mlproject.config.configuration import ConfigurationManager


class ModelEvaluationPipeline:
    def __init__(self):
        pass

    def main(self) -> None:
        try:
            config = ConfigurationManager()
            component_config = config.get_model_evaluation_config()
            ModelEvaluation(config=component_config).evaluate_model()
            logger.info("Model evaluation pipeline completed")
        except Exception:
            logger.exception("Model evaluation pipeline failed")
            raise


if __name__ == "__main__":
    ModelEvaluationPipeline().main()
