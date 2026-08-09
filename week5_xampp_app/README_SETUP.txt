DRINKS QUALITY PREDICTION SYSTEM - WEEK 5 XAMPP SETUP
======================================================

WHAT THIS VERSION DOES
----------------------
1. Register multiple users with different email addresses.
2. Save each account in MySQL.
3. Store password_hash values instead of readable passwords.
4. Require the exact registered email and password at login.
5. Block dashboard.php when the user is not logged in.
6. Show the logged-in user and a real Logout button on the main website.
7. Store prediction samples and results under the logged-in user.
8. Show each normal user's own history; Admin sees all records.

IMPORTANT
---------
This is local email/password registration. It accepts Gmail addresses, but it is NOT the
"Sign in with Google" OAuth service. Real Google OAuth requires a Google Cloud project,
OAuth client ID, secret, and an HTTPS callback URL. Local MySQL login is appropriate for
Week 5 database work.

INSTALLATION
------------
1. Extract the supplied ZIP.
2. Copy the folder named week5_xampp_app to:
   C:\xampp\htdocs\week5_xampp_app
3. Open XAMPP Control Panel.
4. Start Apache and MySQL. Both should turn green.
5. Open this URL in your browser:
   http://localhost/phpmyadmin/
6. Click Import -> Choose File.
7. Select:
   C:\xampp\htdocs\week5_xampp_app\database\schema.sql
8. Click Go and confirm that drinks_quality_db is created.
9. Open:
   http://localhost/week5_xampp_app/

READY-MADE TEST ACCOUNT
-----------------------
Email: demo@gmail.com
Password: DemoUser123!

HOW TO TEST MULTIPLE USERS
--------------------------
1. Open Register.
2. Create User A with a new email and password.
3. Log in using User A's exact details.
4. Log out.
5. Register User B with another email.
6. Log in using User B's exact details.
7. Try a wrong password; the main website will not open.
8. Enter a prediction and open History.

HOW TO SEE DATABASE RECORDS
---------------------------
1. Open http://localhost/phpmyadmin/
2. Select drinks_quality_db.
3. Open users to see registered emails and password hashes.
4. Open drink_samples and predictions to see saved results.

Do not expect to see the original passwords in phpMyAdmin. Correct applications never
store readable passwords. PHP checks passwords using password_verify().

COMMON PROBLEMS
---------------
- "Database connection failed": Start MySQL and import schema.sql.
- Apache does not start: another program may be using port 80. Use XAMPP Config or stop it.
- Page opens as file:///...: wrong method. Use http://localhost/week5_xampp_app/.
- Unknown database: import database/schema.sql again.
- MySQL root has a password: edit config/db.php and set $database_password.
- You imported schema.sql again: it resets the four tables and removes users registered later.

PHP SYNTAX CHECKS (OPTIONAL)
----------------------------
Run in PowerShell:
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\login.php
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\register.php
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\dashboard.php
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\predict.php
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\history.php
C:\xampp\php\php.exe -l C:\xampp\htdocs\week5_xampp_app\logout.php
