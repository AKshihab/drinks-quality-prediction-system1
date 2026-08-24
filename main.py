from mlproject import logger
from mlproject.pipeline.stage_01_data_ingestion import DataIngestionTrainingPipeline
from mlproject.pipeline.stage_02_data_validation import DataValidationTrainingPipeline
from mlproject.pipeline.stage_03_data_transformation import (
    DataTransformationTrainingPipeline,
)
from mlproject.pipeline.stage_04_model_training import ModelTrainingPipeline
from mlproject.pipeline.stage_05_model_evaluation import ModelEvaluationPipeline


PIPELINE_STAGES = (
    ("Data Ingestion", DataIngestionTrainingPipeline),
    ("Data Validation", DataValidationTrainingPipeline),
    ("Data Transformation", DataTransformationTrainingPipeline),
    ("Model Training", ModelTrainingPipeline),
    ("Model Evaluation", ModelEvaluationPipeline),
)


if __name__ == "__main__":
    for stage_name, pipeline_class in PIPELINE_STAGES:
        try:
            logger.info("%s stage started", stage_name)
            pipeline_class().main()
            logger.info("%s stage completed", stage_name)
        except Exception:
            logger.exception("%s stage failed", stage_name)
            raise
