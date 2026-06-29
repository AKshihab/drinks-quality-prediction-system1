import os
import shutil
import logging
from pathlib import Path

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

project_name = "mlproject"

# -----------------------------
# Correct project folder/files
# -----------------------------
list_of_files = [
    # Source package
    f"src/{project_name}/__init__.py",
    f"src/{project_name}/components/__init__.py",
    f"src/{project_name}/components/data_ingestion.py",
    f"src/{project_name}/components/data_validation.py",
    f"src/{project_name}/components/data_transformation.py",
    f"src/{project_name}/components/model_trainer.py",
    f"src/{project_name}/components/model_evaluation.py",

    f"src/{project_name}/utils/__init__.py",
    f"src/{project_name}/utils/common.py",

    f"src/{project_name}/config/__init__.py",
    f"src/{project_name}/config/configuration.py",

    f"src/{project_name}/pipeline/__init__.py",
    f"src/{project_name}/pipeline/training_pipeline.py",
    f"src/{project_name}/pipeline/prediction_pipeline.py",

    f"src/{project_name}/entity/__init__.py",
    f"src/{project_name}/entity/config_entity.py",

    f"src/{project_name}/constants/__init__.py",

    # Config files
    "config/config.yaml",
    "params.yaml",
    "schema.yaml",

    # Main project files
    "main.py",
    "app.py",
    "prediction.py",
    "requirements.txt",
    "setup.py",
    "README.md",
    ".gitignore",

    # Research files
    "research/trials.ipynb",
    "research/01_data_ingestion.ipynb",
    "research/02_data_validation.ipynb",
    "research/03_data_transformation.ipynb",
    "research/04_model_trainer.ipynb",
    "research/05_model_evaluation.ipynb",

    # Flask templates
    "templates/index.html",
    "templates/results.html",

    # Static files - correct structure
    "static/assets/img/.gitkeep",
    "static/assets/favicon.ico",
    "static/css/styles.css",
    "static/css2/nunito-font.css",
    "static/css2/style.css",
    "static/js/scripts.js",

    # Logs and docs
    "logs/.gitkeep",
    "project-docs/week-2-report/screenshots/.gitkeep",
]


def create_file(filepath: str):
    filepath = Path(filepath)
    filedir = filepath.parent

    if str(filedir) != ".":
        os.makedirs(filedir, exist_ok=True)
        logging.info(f"Directory ready: {filedir}")

    if not filepath.exists() or filepath.stat().st_size == 0:
        with open(filepath, "w", encoding="utf-8") as f:
            pass
        logging.info(f"Created empty file: {filepath}")
    else:
        logging.info(f"Already exists, skipped: {filepath}")


def move_file_if_exists(old_path: str, new_path: str):
    old_path = Path(old_path)
    new_path = Path(new_path)

    if old_path.exists():
        os.makedirs(new_path.parent, exist_ok=True)

        if new_path.exists() and new_path.stat().st_size > 0:
            logging.warning(f"Destination already has file, not overwritten: {new_path}")
            logging.warning(f"Old file still remains here: {old_path}")
        else:
            shutil.move(str(old_path), str(new_path))
            logging.info(f"Moved: {old_path} -> {new_path}")


def remove_empty_folder(folder_path: str):
    folder_path = Path(folder_path)

    if folder_path.exists() and folder_path.is_dir():
        try:
            folder_path.rmdir()
            logging.info(f"Removed empty folder: {folder_path}")
        except OSError:
            logging.info(f"Folder not empty, kept: {folder_path}")


# -----------------------------
# Create correct files/folders
# -----------------------------
for file in list_of_files:
    create_file(file)


# -----------------------------
# Fix wrongly placed CSS/JS files
# Example wrong path:
# static/assets/CSS/styles.css
# -----------------------------
move_file_if_exists("static/assets/CSS/styles.css", "static/css/styles.css")
move_file_if_exists("static/assets/CSS/style.css", "static/css2/style.css")
move_file_if_exists("static/assets/CSS/nunito-font.css", "static/css2/nunito-font.css")
move_file_if_exists("static/assets/CSS/css2/style.css", "static/css2/style.css")
move_file_if_exists("static/assets/CSS/css2/nunito-font.css", "static/css2/nunito-font.css")
move_file_if_exists("static/assets/CSS/js/scripts.js", "static/js/scripts.js")


# -----------------------------
# Remove old empty wrong folders
# -----------------------------
remove_empty_folder("static/assets/CSS/js")
remove_empty_folder("static/assets/CSS/css2")
remove_empty_folder("static/assets/CSS")
remove_empty_folder("static/assets")


logging.info("Project structure setup completed successfully.")