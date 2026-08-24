from pathlib import Path

from flask import app
import joblib
import numpy as np
import pandas as pd
import streamlit as st


PROJECT_ROOT = Path(__file__).resolve().parent
MODEL_PATH = PROJECT_ROOT / "artifacts" / "model_trainer" / "model.pkl"
TRAIN_DATA_PATH = PROJECT_ROOT / "artifacts" / "data_transformation" / "train.csv"
TARGET_COLUMN = "quality"


@st.cache_resource
def load_model(model_path: Path):
    return joblib.load(model_path)


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

    if not MODEL_PATH.is_file() or not TRAIN_DATA_PATH.is_file():
        st.error(
            "The trained model is not available. Run `python main.py` before "
            "starting this application."
        )
        st.stop()

    try:
        model = load_model(MODEL_PATH)
        reference_data = load_reference_data(TRAIN_DATA_PATH)
    except Exception as error:
        st.error(f"Unable to load model artifacts: {error}")
        st.stop()

    default_features = reference_data.drop(columns=[TARGET_COLUMN]).columns.tolist()
    feature_names = list(getattr(model, "feature_names_in_", default_features))

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
        prediction_input = pd.DataFrame(
            [[input_values[name] for name in feature_names]],
            columns=feature_names,
        )
        try:
            raw_prediction = float(model.predict(prediction_input)[0])
            quality_values = reference_data[TARGET_COLUMN]
            predicted_quality = int(
                np.clip(
                    np.rint(raw_prediction),
                    quality_values.min(),
                    quality_values.max(),
                )
            )
        except Exception as error:
            st.error(f"Prediction failed: {error}")
        else:
            st.success("Prediction completed")
            st.metric("Predicted drink quality", predicted_quality)

    st.caption(f"Model artifact: {MODEL_PATH.relative_to(PROJECT_ROOT)}")


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080, debug=True)
