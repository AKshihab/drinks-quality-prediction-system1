import os
import json
import yaml
import joblib

from pathlib import Path
from typing import Any
from box import ConfigBox
from box.exceptions import BoxValueError
from ensure import ensure_annotations

from mlproject import logger


@ensure_annotations
def read_yaml(path_to_yaml: Path) -> ConfigBox:
    """
    Reads a YAML file and returns its content as ConfigBox.

    Args:
        path_to_yaml (Path): Path to YAML file.

    Raises:
        ValueError: If YAML file is empty.
        Exception: If any other error occurs.

    Returns:
        ConfigBox: YAML content as ConfigBox object.
    """
    try:
        with open(path_to_yaml, "r") as yaml_file:
            content = yaml.safe_load(yaml_file)

            if content is None:
                raise BoxValueError("YAML file is empty")

            logger.info(f"YAML file loaded successfully from: {path_to_yaml}")
            return ConfigBox(content)

    except BoxValueError:
        raise ValueError("YAML file is empty")

    except Exception as e:
        raise e


@ensure_annotations
def create_directories(path_to_directories: list, verbose: bool = True):
    """
    Creates multiple directories.

    Args:
        path_to_directories (list): List of directory paths.
        verbose (bool, optional): Whether to log directory creation.
    """
    for path in path_to_directories:
        os.makedirs(path, exist_ok=True)

        if verbose:
            logger.info(f"Created directory at: {path}")


@ensure_annotations
def save_json(path: Path, data: dict):
    """
    Saves dictionary data into a JSON file.

    Args:
        path (Path): Path to JSON file.
        data (dict): Data to be saved in JSON file.
    """
    with open(path, "w") as f:
        json.dump(data, f, indent=4)

    logger.info(f"JSON file saved at: {path}")


@ensure_annotations
def load_json(path: Path) -> ConfigBox:
    """
    Loads JSON file data.

    Args:
        path (Path): Path to JSON file.

    Returns:
        ConfigBox: JSON data as class attributes instead of dictionary.
    """
    with open(path, "r") as f:
        content = json.load(f)

    logger.info(f"JSON file loaded successfully from: {path}")
    return ConfigBox(content)


@ensure_annotations
def save_bin(data: Any, path: Path):
    """
    Saves binary file.

    Args:
        data (Any): Data to be saved as binary.
        path (Path): Path to binary file.
    """
    joblib.dump(value=data, filename=path)
    logger.info(f"Binary file saved at: {path}")


@ensure_annotations
def load_bin(path: Path) -> Any:
    """
    Loads binary data.

    Args:
        path (Path): Path to binary file.

    Returns:
        Any: Object stored in the file.
    """
    data = joblib.load(path)
    logger.info(f"Binary file loaded from: {path}")
    return data


@ensure_annotations
def get_size(path: Path) -> str:
    """
    Gets file size in KB.

    Args:
        path (Path): Path of the file.

    Returns:
        str: Size of the file in KB.
    """
    size_in_kb = round(os.path.getsize(path) / 1024)
    return f"~ {size_in_kb} KB"