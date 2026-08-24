# Drinks Quality Prediction System

The main website is the authenticated PHP/MySQL application in
`week5_xampp_app/`. It calls a localhost Flask API, which loads the trained
scikit-learn model and returns the real drink-quality class.

## Local setup

1. Create and activate the Python environment, then install dependencies:

   ```powershell
   conda create -n mlproj python=3.10 -y
   conda activate mlproj
   pip install -r requirements.txt
   ```

2. To regenerate the model, run `python main.py`. Training explicitly excludes
   the dataset row identifier `Id` and writes `model.pkl`,
   `model_metadata.json`, and the evaluation scores under `artifacts/`.

3. Start the local prediction API from the repository root:

   ```powershell
   python ml_api.py
   ```

   Confirm `http://127.0.0.1:5000/health` reports `"status": "ok"`.

4. Copy `week5_xampp_app` to `C:\xampp\htdocs\week5_xampp_app`, start Apache
   and MySQL, and follow [README_ML_INTEGRATION.md](README_ML_INTEGRATION.md) to
   import the correct fresh schema or non-destructive upgrade scripts.

5. Open `http://localhost/week5_xampp_app/`. The browser uses PHP; port 5000 is
   the server-to-server model endpoint.

The optional Streamlit interface uses the same prediction service and artifact:

```powershell
streamlit run app.py
```

Detailed Week 6 security verification remains in [README_WEEK6.md](README_WEEK6.md).
