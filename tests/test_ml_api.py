import unittest
from pathlib import Path

from ml_api import create_app
from mlproject.prediction_service import PredictionService
from tests.test_prediction_service import SAMPLE_VALUES


PROJECT_ROOT = Path(__file__).resolve().parents[1]


class MlApiTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        service = PredictionService(
            PROJECT_ROOT / "artifacts" / "model_trainer" / "model.pkl",
            PROJECT_ROOT
            / "artifacts"
            / "model_trainer"
            / "model_metadata.json",
        )
        app = create_app(service)
        app.config.update(TESTING=True)
        cls.client = app.test_client()

    def test_health_reports_loaded_model(self):
        response = self.client.get("/health")

        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.get_json()["status"], "ok")
        self.assertEqual(
            response.get_json()["model"]["name"],
            "Drinks Quality Classifier",
        )

    def test_predict_returns_real_model_result(self):
        response = self.client.post("/predict", json=SAMPLE_VALUES)

        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.get_json()["predicted_quality"], 5)
        self.assertEqual(response.get_json()["quality_label"], "Average")

    def test_reordered_fields_return_the_same_result(self):
        reordered_values = dict(reversed(list(SAMPLE_VALUES.items())))

        response = self.client.post("/predict", json=reordered_values)

        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.get_json()["predicted_quality"], 5)

    def test_invalid_body_returns_400(self):
        response = self.client.post("/predict", json=[1, 2, 3])

        self.assertEqual(response.status_code, 400)
        self.assertEqual(response.get_json()["error"]["code"], "invalid_input")

    def test_missing_field_returns_400(self):
        values = dict(SAMPLE_VALUES)
        del values["density"]
        response = self.client.post("/predict", json=values)

        self.assertEqual(response.status_code, 400)
        self.assertIn("density", response.get_json()["error"]["message"])

    def test_extra_and_out_of_range_fields_return_400(self):
        extra_response = self.client.post(
            "/predict", json=dict(SAMPLE_VALUES, Id=1)
        )
        range_response = self.client.post(
            "/predict", json=dict(SAMPLE_VALUES, fixed_acidity=1000)
        )

        self.assertEqual(extra_response.status_code, 400)
        self.assertEqual(range_response.status_code, 400)

    def test_missing_artifacts_report_service_unavailable(self):
        app = create_app(
            model_path=PROJECT_ROOT / "does-not-exist.pkl",
            metadata_path=PROJECT_ROOT / "does-not-exist.json",
        )
        app.config.update(TESTING=True)
        client = app.test_client()

        health_response = client.get("/health")
        prediction_response = client.post("/predict", json=SAMPLE_VALUES)

        self.assertEqual(health_response.status_code, 503)
        self.assertEqual(health_response.get_json(), {"status": "unavailable"})
        self.assertEqual(prediction_response.status_code, 503)
        self.assertEqual(
            prediction_response.get_json()["error"]["code"],
            "model_unavailable",
        )


if __name__ == "__main__":
    unittest.main()
