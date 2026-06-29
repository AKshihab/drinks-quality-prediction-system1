import os
from pathlib import Path
import logging

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

list_of_files = [
    "static/assets/img/.gitkeep",
    "static/assets/favicon.ico",

    "static/css/styles.css",

    "static/css2/nunito-font.css",
    "static/css2/style.css",

    "static/js/scripts.js",

    "templates/index.html",
    "templates/results.html",

    "project-docs/week-2-report/screenshots/.gitkeep",
]

for filepath in list_of_files:
    filepath = Path(filepath)
    filedir = filepath.parent
    filename = filepath.name

    if str(filedir) != ".":
        os.makedirs(filedir, exist_ok=True)
        logging.info(f"Directory ready: {filedir}")

    if not filepath.exists():
        with open(filepath, "w", encoding="utf-8") as f:
            pass
        logging.info(f"Created empty file: {filepath}")
    else:
        logging.info(f"{filename} already exists")