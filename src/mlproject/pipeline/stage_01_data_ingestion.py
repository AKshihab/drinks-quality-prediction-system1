from mlproject import logger
from mlproject.components.data_ingestion import DataIngestion
from mlproject.config.configuration import ConfigurationManager


class DataIngestionTrainingPipeline:
    def __init__(self):
        pass

    def main(self) -> None:
        try:
            config = ConfigurationManager()
            component_config = config.get_data_ingestion_config()
            data_ingestion = DataIngestion(config=component_config)
            data_ingestion.download_file()
            data_ingestion.extract_zip_file()
            logger.info("Data ingestion pipeline completed")
        except Exception:
            logger.exception("Data ingestion pipeline failed")
            raise


if __name__ == "__main__":
    DataIngestionTrainingPipeline().main()
