# Week 4 — PHP Authentication and Session Management

This folder contains the Week 4 server-side laboratory work for the **Drinks Quality Prediction System**. It is intentionally separate from the existing Week 3 Flask application.

## What was implemented

- PHP login form using `POST`
- Server-side empty-field, email-format, and password-length validation
- Demonstration authentication using `password_hash()` and `password_verify()`
- PHP session creation and session ID regeneration
- Dashboard access protection
- Five-minute inactivity timeout
- Complete logout that clears variables, deletes the session cookie, and destroys the session

## Folder structure

```text
week4_php_auth/
├── index.php
├── login.php
├── dashboard.php
├── logout.php
├── README_WEEK4.md
└── assets/
    └── style.css
```

## Demonstration credentials

```text
Email: student@university.edu
Password: secureStudent123
```

## Run on this Windows PC with XAMPP

1. Install XAMPP if it is not already installed.
2. Put the complete repository here:

```text
C:\xampp\htdocs\drinks-quality-prediction-system1
```

3. Open the XAMPP Control Panel.
4. Start **Apache** and confirm that it becomes green.
5. Open Command Prompt and run:

```bat
cd /d C:\xampp\htdocs\drinks-quality-prediction-system1
C:\xampp\php\php.exe -l week4_php_auth\index.php
C:\xampp\php\php.exe -l week4_php_auth\login.php
C:\xampp\php\php.exe -l week4_php_auth\dashboard.php
C:\xampp\php\php.exe -l week4_php_auth\logout.php
start http://localhost/drinks-quality-prediction-system1/week4_php_auth/
```

Main URL:

```text
http://localhost/drinks-quality-prediction-system1/week4_php_auth/
```

Direct URLs:

```text
http://localhost/drinks-quality-prediction-system1/week4_php_auth/login.php
http://localhost/drinks-quality-prediction-system1/week4_php_auth/dashboard.php
```

Do not use VS Code Live Server and do not open the PHP files with a `file:///` address. PHP must run through Apache.

## Manual tests

1. Open `dashboard.php` before login. It should redirect to `login.php`.
2. Submit empty fields. It should show an empty-field error.
3. Submit an invalid email. It should show an email-format error.
4. Submit a password shorter than 8 characters. It should show a password-length error.
5. Submit incorrect credentials. It should show an invalid-credentials error.
6. Submit the correct credentials. It should redirect to `dashboard.php`.
7. Refresh the dashboard. The session should remain active.
8. Click **Log out**. It should return to the login page with a success message.
9. Reopen `dashboard.php`. It should redirect to `login.php`.

## Test the timeout quickly

The normal timeout in `dashboard.php` is:

```php
$timeout_duration = 300;
```

For a quick test, temporarily change `300` to `10`, log in, wait more than 10 seconds, and refresh the dashboard. Restore it to `300` before submission.

## Run on another Windows PC without Git

1. Copy or ZIP the complete `drinks-quality-prediction-system1` folder.
2. Install XAMPP on the other PC.
3. Extract or copy the folder to:

```text
C:\xampp\htdocs\drinks-quality-prediction-system1
```

4. Start Apache in XAMPP.
5. Run the same syntax-check commands shown above.
6. Open the main localhost URL.

The other PC does not need Python, Flask, Conda, AWS, MySQL, or Git to test this isolated Week 4 PHP module. It only needs XAMPP with Apache and PHP.

## Notes

- No database is required.
- AWS is not required.
- MySQL does not need to be started.
- This folder does not replace or modify the Week 3 Flask application.

