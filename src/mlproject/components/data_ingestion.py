import urllib.request
import zipfile
from pathlib import Path

from mlproject import logger
from mlproject.entity.config_entity import DataIngestionConfig


class DataIngestion:
    def __init__(self, config: DataIngestionConfig):
        self.config = config

    def download_file(self) -> None:
        self.config.root_dir.mkdir(parents=True, exist_ok=True)
        if self.config.local_data_file.is_file():
            logger.info("Dataset archive already exists: %s", self.config.local_data_file)
            return

        temporary_file = Path(f"{self.config.local_data_file}.part")
        logger.info("Downloading dataset from %s", self.config.source_URL)
        try:
            urllib.request.urlretrieve(self.config.source_URL, temporary_file)
            temporary_file.replace(self.config.local_data_file)
        except Exception:
            temporary_file.unlink(missing_ok=True)
            raise
        logger.info("Dataset downloaded to %s", self.config.local_data_file)

    def extract_zip_file(self) -> None:
        self.config.unzip_dir.mkdir(parents=True, exist_ok=True)
        destination = self.config.unzip_dir.resolve()
        logger.info("Extracting dataset archive to %s", destination)

        with zipfile.ZipFile(self.config.local_data_file, "r") as zip_file:
            for member in zip_file.infolist():
                member_path = (destination / member.filename).resolve()
                if destination != member_path and destination not in member_path.parents:
                    raise ValueError(f"Unsafe path in dataset archive: {member.filename}")
            zip_file.extractall(destination)

        logger.info("Dataset extraction completed")
