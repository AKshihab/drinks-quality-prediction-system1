from pathlib import Path

import pandas as pd
import streamlit as st

from mlproject.prediction_service import API_TO_MODEL_FEATURES, PredictionService


PROJECT_ROOT = Path(__file__).resolve().parent
MODEL_PATH = PROJECT_ROOT / "artifacts" / "model_trainer" / "model.pkl"
MODEL_METADATA_PATH = (
    PROJECT_ROOT / "artifacts" / "model_trainer" / "model_metadata.json"
)
TRAIN_DATA_PATH = PROJECT_ROOT / "artifacts" / "data_transformation" / "train.csv"


@st.cache_resource
def load_prediction_service(
    model_path: Path, metadata_path: Path
) -> PredictionService:
    return PredictionService(model_path, metadata_path)


@st.cache_data
def load_reference_data(data_path: Path) -> pd.DataFrame:
    return pd.read_csv(data_path)


def main() -> None:
    st.set_page_config(
        page_title="Drinks Quality Prediction",
        page_icon="🥤",
        layout="wide",
    )
    st.title("Drinks Quality Prediction System")
    st.write(
        "Enter the measured properties of a drink and submit the form to "
        "predict its quality class."
    )

    if (
        not MODEL_PATH.is_file()
        or not MODEL_METADATA_PATH.is_file()
        or not TRAIN_DATA_PATH.is_file()
    ):
        st.error(
            "The trained model is not available. Run `python main.py` before "
            "starting this application."
        )
        st.stop()

    try:
        prediction_service = load_prediction_service(
            MODEL_PATH, MODEL_METADATA_PATH
        )
        reference_data = load_reference_data(TRAIN_DATA_PATH)
    except Exception as error:
        st.error(f"Unable to load model artifacts: {error}")
        st.stop()

    feature_names = prediction_service.model_feature_names

    with st.form("prediction_form"):
        columns = st.columns(2)
        input_values = {}
        for index, feature_name in enumerate(feature_names):
            feature_data = reference_data[feature_name]
            container = columns[index % len(columns)]
            if pd.api.types.is_integer_dtype(feature_data):
                input_values[feature_name] = container.number_input(
                    feature_name,
                    min_value=int(feature_data.min()),
                    max_value=int(feature_data.max()),
                    value=int(feature_data.median()),
                    step=1,
                )
            else:
                input_values[feature_name] = container.number_input(
                    feature_name,
                    min_value=float(feature_data.min()),
                    max_value=float(feature_data.max()),
                    value=float(feature_data.median()),
                    format="%.4f",
                )

        submitted = st.form_submit_button("Predict quality", type="primary")

    if submitted:
        api_values = {
            api_name: input_values[model_name]
            for api_name, model_name in API_TO_MODEL_FEATURES.items()
        }
        try:
            result = prediction_service.predict(api_values)
        except Exception as error:
            st.error(f"Prediction failed: {error}")
        else:
            st.success("Prediction completed")
            st.metric(
                "Predicted drink quality",
                f"{result['predicted_quality']} ({result['quality_label']})",
            )

    model_info = prediction_service.model_info
    st.caption(
        f"{model_info['name']} v{model_info['version']} "
        f"({model_info['algorithm']}) | "
        f"Artifact: {MODEL_PATH.relative_to(PROJECT_ROOT)}"
    )


if __name__ == "__main__":
    main()
