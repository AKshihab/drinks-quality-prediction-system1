import logging
from pathlib import Path

from flask import Flask, jsonify, request

from mlproject.prediction_service import (
    PredictionService,
    PredictionValidationError,
)


PROJECT_ROOT = Path(__file__).resolve().parent
MODEL_PATH = PROJECT_ROOT / "artifacts" / "model_trainer" / "model.pkl"
METADATA_PATH = (
    PROJECT_ROOT / "artifacts" / "model_trainer" / "model_metadata.json"
)


def create_app(
    prediction_service: PredictionService | None = None,
    model_path: Path = MODEL_PATH,
    metadata_path: Path = METADATA_PATH,
) -> Flask:
    app = Flask(__name__)
    service = prediction_service
    load_error: Exception | None = None

    if service is None:
        try:
            service = PredictionService(model_path, metadata_path)
        except Exception as error:
            load_error = error
            app.logger.error("Unable to load model artifacts: %s", error)

    @app.get("/health")
    def health():
        if service is None:
            return jsonify({"status": "unavailable"}), 503
        return jsonify({"status": "ok", "model": service.model_info})

    @app.post("/predict")
    def predict():
        if service is None:
            if load_error is not None:
                app.logger.error("Prediction unavailable: %s", load_error)
            return (
                jsonify(
                    {
                        "error": {
                            "code": "model_unavailable",
                            "message": "The prediction model is unavailable.",
                        }
                    }
                ),
                503,
            )

        values = request.get_json(silent=True)
        try:
            result = service.predict(values)
        except PredictionValidationError as error:
            return (
                jsonify(
                    {
                        "error": {
                            "code": "invalid_input",
                            "message": str(error),
                        }
                    }
                ),
                400,
            )
        except Exception:
            app.logger.exception("Model prediction failed")
            return (
                jsonify(
                    {
                        "error": {
                            "code": "prediction_failed",
                            "message": "The prediction could not be completed.",
                        }
                    }
                ),
                500,
            )

        return jsonify(result)

    return app


app = create_app()


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    app.run(host="127.0.0.1", port=5000, debug=False)
