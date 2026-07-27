# Drinks Quality Prediction System

## How to Run

### 1. Create a Conda Environment

```powershell
conda create -n mlproj python=3.10 -y
```

### 2. Activate the Environment

```powershell
conda activate mlproj
```

If the `mlproj` environment already exists, skip step 1.

### 3. Install Requirements

```powershell
pip install -r requirements.txt
```

### 4. Start Flask

```powershell
python app.py
```

Open `http://127.0.0.1:5000` in a browser.
An Internet connection is needed to load the Tailwind CSS CDN.

## Pages to Test

- Login: `http://127.0.0.1:5000/`
- Register: `http://127.0.0.1:5000/register`
- Dashboard: `http://127.0.0.1:5000/dashboard`

On the dashboard, test empty and negative prediction values, the sample-data
button, the clear button, and a valid prediction. On the registration page,
test invalid email addresses and weak, moderate, and strong passwords. Also
check both pages at a narrow mobile screen width and review the browser console
for errors.
