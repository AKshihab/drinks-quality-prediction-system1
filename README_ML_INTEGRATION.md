# PHP and Trained-Model Integration

## Architecture

The browser submits the protected prediction form to PHP. PHP validates the
session, CSRF token, and numeric values, then sends the 11 measurements as JSON
to `http://127.0.0.1:5000/predict`. Flask runs the trained model and returns an
integer quality class, label, and model identity. Only after that succeeds does
PHP open a MySQL transaction and save the sample and prediction.

There is no demonstration-formula fallback. If Python is stopped, the PHP page
shows a safe availability message and does not create a partial database row.

## One-time installation

1. From the repository root, activate the environment and install requirements:

   ```powershell
   conda activate mlproj
   pip install -r requirements.txt
   ```

2. The checked model artifacts are in `artifacts/model_trainer/`. To regenerate
   them and the evaluation scores from the source data, run:

   ```powershell
   python main.py
   ```

3. Copy the complete `week5_xampp_app` folder to:

   ```text
   C:\xampp\htdocs\week5_xampp_app
   ```

4. Start Apache and MySQL in XAMPP.

5. In `http://localhost/phpmyadmin/`, choose exactly one data path:

   - Existing Week 5 database: back it up, import
     `database/week6_security_update.sql` if the `users.bio` column is absent,
     then import `database/ml_model_integration_update.sql`.
   - Empty installation: import `database/schema.sql` only. It already includes
     the profile field and trained-model record.

   Never import the fresh schema over an existing database that contains users
   or history; it deliberately recreates the tables.

## Start and test

Keep this command running in a separate terminal from XAMPP:

```powershell
conda activate mlproj
Set-Location "C:\Users\shiha\ML-Projects\drinks-quality-prediction-system1"
python ml_api.py
```

Check the service:

```powershell
Invoke-RestMethod http://127.0.0.1:5000/health
```

Then open `http://localhost/week5_xampp_app/`, sign in, select **Use Sample
Data**, and submit. The expected trained-model result is quality `5`, label
`Average`, model `Drinks Quality Classifier`, version `1.0`.

History must show the saved result under the signed-in user. Existing prototype
history remains attached to its original model record.

## Troubleshooting

- **Prediction service unavailable:** keep `python ml_api.py` running and check
  the health URL. The API must use port 5000 on `127.0.0.1`.
- **No active database model matches:** import
  `database/ml_model_integration_update.sql` into the existing database.
- **Database connection failed:** start MySQL and verify `config/db.php`.
- **Invalid feature range:** enter values within the ranges learned from the
  training data; the API rejects extrapolation.
- **Old PHP page still appears:** recopy the repository's `week5_xampp_app`
  folder into `C:\xampp\htdocs`.

## Verification commands

```powershell
python -m unittest discover -s tests -v
& "C:\xampp\php\php.exe" -l "week5_xampp_app\predict.php"
& "C:\xampp\php\php.exe" -l "week5_xampp_app\includes\ml_client.php"
```
