# Week 6 — Database Integration & Web Application Security

## Verification status

This Week 6 work extends the existing Week 5 PHP/MySQL application in `week5_xampp_app/`. The security work remains intact, and the current application now sends prediction requests to the corrected trained Python model through a localhost Flask API. See `README_ML_INTEGRATION.md` for the model-service setup.

The security and ML integration have been reviewed statically in the repository, and all 14 PHP files in the active XAMPP application pass `php -l` syntax validation. The local XAMPP PHP installation provides `PDO`, `pdo_mysql`, cURL, and JSON. At the time this guide was updated, Apache and MySQL were stopped, so live database operations and browser behavior have **not** yet been claimed as passed. Complete the manual tests in this document before submission.

## 1. Objective

The objective of Week 6 is to build on the working Week 5 application and demonstrate secure database-backed web development through:

- a centralized and securely configured PDO connection;
- parameterized SQL queries and SQL Injection protection;
- secure registration and login with password hashing and verification;
- regenerated, protected, and time-limited PHP sessions;
- an authenticated profile page with a parameterized bio update;
- output escaping that prevents stored profile content from executing as HTML or JavaScript; and
- clear database installation, migration, and security-testing instructions.

The canonical Week 6 application is:

```text
week5_xampp_app/
```

The canonical browser URL after copying it into XAMPP is:

```text
http://localhost/week5_xampp_app/
```

The browser still uses the PHP application. `http://127.0.0.1:5000` is now a server-to-server prediction API; `/health` can be opened directly only to verify that the model is ready.

## 2. What Week 5 already had — PREVIOUSLY IMPLEMENTED

The following features were already present and were reused rather than rebuilt:

| Previously implemented feature | Existing evidence |
|---|---|
| Central PDO connection to `drinks_quality_db` | `week5_xampp_app/config/db.php` |
| PDO exception mode, associative fetches, and native prepares | `week5_xampp_app/config/db.php` |
| Registration validation and duplicate-email lookup | `week5_xampp_app/register.php` |
| Password storage through `password_hash(..., PASSWORD_DEFAULT)` | `week5_xampp_app/register.php` |
| Database-backed login through a prepared `SELECT` | `week5_xampp_app/login.php` |
| Password verification through `password_verify()` | `week5_xampp_app/login.php` |
| Session ID regeneration after successful login | `week5_xampp_app/includes/auth.php` |
| Protected pages and five-minute inactivity timeout | `week5_xampp_app/includes/auth.php` |
| Secure session cookie settings | `week5_xampp_app/config/app.php` |
| CSRF protection for state-changing forms | `week5_xampp_app/config/app.php` and the PHP forms |
| Logout through POST, CSRF verification, cookie clearing, and session destruction | `week5_xampp_app/logout.php` and `week5_xampp_app/includes/auth.php` |
| Prepared prediction inserts and user-filtered prediction history | `week5_xampp_app/predict.php` and `week5_xampp_app/history.php` |
| Admin/user history separation | `week5_xampp_app/history.php` |
| Shared HTML escaping helper | `e()` in `week5_xampp_app/config/app.php` |

## 3. What was reused rather than rebuilt

Week 6 continues to use the existing:

- database name `drinks_quality_db`;
- `users`, `models`, `drink_samples`, and `predictions` tables;
- `user_id`, `full_name`, `email`, `password_hash`, `role`, and `created_at` user columns;
- PDO connection in `week5_xampp_app/config/db.php`;
- registration and login pages;
- session variables `user_id`, `user_name`, `user_email`, and `user_role`;
- five-minute inactivity timeout and role behavior;
- dashboard, prediction, history, and logout flows;
- shared navigation, CSS, and CSRF functions; and
- the existing ML training pipeline, corrected to exclude the non-predictive dataset `Id` column.

No duplicate login page, registration page, database connection, authentication helper, session system, or database was created.

## 4. What was added or improved in Week 6 — ADDED/IMPROVED

| Week 6 change | File | Purpose |
|---|---|---|
| Protected profile page | `week5_xampp_app/profile.php` | Loads the signed-in user's account and updates the bio using the session user ID. |
| Nullable `bio` column in the active fresh-install schema | `week5_xampp_app/database/schema.sql` | Gives new installations the complete Week 6 user schema. |
| Matching `bio` column in the repository-level schema | `database/schema.sql` | Keeps the second documented fresh-install schema consistent. |
| Non-destructive upgrade migration | `week5_xampp_app/database/week6_security_update.sql` | Adds `bio` to an existing Week 5 database without dropping users or predictions. |
| Profile navigation and Week 6 footer text | `week5_xampp_app/includes/layout.php` | Integrates Profile into the authenticated application. |
| Profile and textarea styling | `week5_xampp_app/assets/styles.css` | Preserves the existing design language on desktop and mobile. |
| Generic invalid-login response | `week5_xampp_app/login.php` | Returns `Invalid email or password.` without indicating whether an account exists. |
| Week 6 setup and testing guide | `README_WEEK6.md` | Documents implementation, migration, expected results, and submission steps. |
| Trained-model API integration | `ml_api.py` and the PHP ML client | Replaces the demonstration formula with the corrected 11-feature model while preserving PHP sessions and MySQL history. |

The profile page also limits the bio to 1,000 UTF-8 characters, verifies the CSRF token, uses a POST/redirect/GET flow after an update, logs technical database exceptions internally, and shows safe browser-facing error messages.

## 5. PDO connection security

The existing centralized connection is:

```text
week5_xampp_app/config/db.php
```

It connects to:

```text
Host: 127.0.0.1
Port: 3306
Database: drinks_quality_db
Username: root
Password: empty (standard local XAMPP default)
Charset: utf8mb4
```

Its PDO options include:

```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
```

`ERRMODE_EXCEPTION` makes database failures catchable, `FETCH_ASSOC` returns predictable associative rows, and disabling emulated prepares makes MySQL/MariaDB handle prepared parameters natively.

Detailed exception messages are written through `error_log()`. Browser users receive a safe setup or availability message rather than the raw PDO exception. The empty `root` password is only the standard local XAMPP default. If the local MySQL root user has a password, change the local `$database_password` value and do not commit the real password.

## 6. Prepared statements

Dynamic values are passed separately from SQL text:

- registration duplicate check: prepared `SELECT` by `:email`;
- registration: prepared `INSERT` using name, email, password hash, and role parameters;
- login: prepared `SELECT` by `:email`;
- dashboard: prepared prediction count using `:user_id`;
- prediction: prepared sample and result `INSERT` statements;
- normal-user history: prepared filtering by `:user_id`;
- profile loading: prepared `SELECT` using the authenticated `:user_id`; and
- profile update: prepared `UPDATE` using `:bio` and authenticated `:user_id`.

The profile update is conceptually:

```php
$update = $pdo->prepare(
    'UPDATE users
     SET bio = :bio
     WHERE user_id = :user_id'
);

$update->execute([
    'bio' => $bio,
    'user_id' => $_SESSION['user_id'],
]);
```

The active-model lookup in `predict.php` uses prepared model-name and version parameters returned by the local model API. The Admin history clauses are fixed application strings rather than user input.

## 7. SQL Injection mitigation

No submitted email, name, password, bio, prediction value, or user ID is concatenated into executable SQL.

The login query compares the submitted email through `:email`. The profile page takes the account ID from `$_SESSION['user_id']`; it does not accept an editable user ID from GET or POST. Therefore a user cannot select another account by changing a URL or hidden form field.

Login failures now use the same message:

```text
Invalid email or password.
```

This avoids both SQL Injection and account-enumeration clues. Manual Test 6 uses the payload `' OR '1'='1` and must not open the dashboard.

## 8. Password hashing and verification

Registration uses:

```php
password_hash($password, PASSWORD_DEFAULT)
```

Login uses:

```php
password_verify($password, $user['password_hash'])
```

The database column is `users.password_hash VARCHAR(255)`. Plain-text passwords, MD5, and SHA-1 are not used. On the current PHP version, a new default hash normally begins with `$2y$` and is much longer than the password, but the important test is that the stored value is a recognized password hash and never equals the submitted password.

The server validates registration names and email addresses, requires at least eight password characters, requires uppercase, lowercase, and numeric characters, checks password confirmation, and rejects duplicate emails.

## 9. Session security

The application starts one named PHP session, `dqps_session`, with:

- `HttpOnly` cookies;
- `SameSite=Lax`;
- secure cookies when HTTPS is active;
- a session-only cookie lifetime; and
- a root application cookie path.

After a successful password check, `login_user()` calls:

```php
session_regenerate_id(true);
```

It then stores the database user ID, name, email, role, login time, activity time, and a new CSRF token. `require_login()` protects Dashboard, Prediction, History, and Profile and expires a session after 300 seconds of inactivity. Protected responses use no-cache headers.

Logout accepts POST only, verifies the CSRF token, clears session variables, expires the session cookie, destroys the session, starts a clean session for the logout message, and returns to Login.

## 10. Profile management

`week5_xampp_app/profile.php` is available only to authenticated users. It retrieves:

```text
user_id
full_name
email
role
bio
created_at
```

The page displays the signed-in user's full name, email, role, account creation date, and saved bio. The user can update only the bio. The update uses POST, a CSRF token, a 1,000-character server-side limit, and a parameterized `UPDATE`.

Both the profile `SELECT` and `UPDATE` take `user_id` from the authenticated session. A request cannot choose another person's `user_id`.

## 11. XSS mitigation

The shared helper is:

```php
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
```

Profile name, email, role, creation time, bio, textarea value, messages, and navigation user data are escaped before insertion into HTML. Prediction-history database values are also escaped.

The application may store the literal text:

```html
<script>alert('XSS')</script>
```

in the `bio` column. Storage is not execution. When displayed, `htmlspecialchars()` converts the special characters for the HTML context, so the browser shows the payload as text and does not create a script element. Manual Test 9 verifies this behavior.

## 12. Database schema and migration changes

The database remains:

```text
drinks_quality_db
```

The Week 6 user schema is:

| Column | Definition |
|---|---|
| `user_id` | `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| `full_name` | `VARCHAR(100) NOT NULL` |
| `email` | `VARCHAR(120) NOT NULL UNIQUE` |
| `password_hash` | `VARCHAR(255) NOT NULL` |
| `role` | `ENUM('Admin', 'User') NOT NULL DEFAULT 'User'` |
| `bio` | `TEXT NULL` |
| `created_at` | `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP` |

### Upgrade an existing Week 5 database

If `drinks_quality_db` already contains registered users, samples, or predictions, import the security migration if `users.bio` is absent:

```text
C:\xampp\htdocs\week5_xampp_app\database\week6_security_update.sql
```

The migration runs:

```sql
USE drinks_quality_db;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER role;
```

Then import the trained-model registration:

```text
C:\xampp\htdocs\week5_xampp_app\database\ml_model_integration_update.sql
```

Both migrations preserve existing users, password hashes, samples, predictions, models, keys, and relationships. The model migration deactivates prototype models and idempotently activates `Drinks Quality Classifier` version `1.0` for new predictions.

> **Critical upgrade warning:** Do not re-import `week5_xampp_app/database/schema.sql` over an existing database. That full schema deliberately drops and recreates all four tables and will remove registered users and saved prediction history. If you are unsure whether data exists, treat the database as an upgrade and run the two non-destructive migration files above.

### Create a fresh database

If this is a genuinely new installation and there is no data to preserve, import:

```text
C:\xampp\htdocs\week5_xampp_app\database\schema.sql
```

The updated fresh-install schema already creates `users.bio` and registers the trained model, so do not run either migration afterward. It also creates the other three tables and seeds this Admin account:

```text
Email: demo@gmail.com
Password: DemoUser123!
Role: Admin
```

The repository-level `database/schema.sql` also contains `bio` for consistency, but the canonical standalone XAMPP application uses `week5_xampp_app/database/schema.sql`.

## 13. Files changed

### Files created

- `week5_xampp_app/profile.php` — protected profile display and parameterized bio update.
- `week5_xampp_app/database/week6_security_update.sql` — additive migration for existing Week 5 data.
- `week5_xampp_app/database/ml_model_integration_update.sql` — non-destructively activates the trained-model registry row.
- `week5_xampp_app/config/ml.php` and `week5_xampp_app/includes/ml_client.php` — central API settings and validated PHP cURL client.
- `ml_api.py` and `src/mlproject/prediction_service.py` — localhost API and shared validated inference service.
- `README_ML_INTEGRATION.md` — model installation, startup, and troubleshooting guide.
- `README_WEEK6.md` — Week 6 implementation, setup, test, and submission guide.

### Files modified

- `week5_xampp_app/database/schema.sql` — adds nullable `users.bio` for a fresh active-app installation.
- `database/schema.sql` — adds the matching fresh-install `users.bio` column.
- `week5_xampp_app/includes/layout.php` — adds Profile navigation and identifies the Week 6 extension in the footer.
- `week5_xampp_app/assets/styles.css` — adds integrated profile, textarea, and responsive styles.
- `week5_xampp_app/login.php` — changes invalid authentication to the generic `Invalid email or password.` message.
- `week5_xampp_app/predict.php` — calls the trained Python model before saving an atomic database transaction.
- `week5_xampp_app/dashboard.php` and `week5_xampp_app/assets/app.js` — use the real 11-feature contract and trained-model wording.

### Important files reused without replacement

- `week5_xampp_app/config/db.php`
- `week5_xampp_app/config/app.php`
- `week5_xampp_app/includes/auth.php`
- `week5_xampp_app/register.php`
- `week5_xampp_app/logout.php`

## 14. How to run on Windows with XAMPP

The working repository is:

```text
C:\Users\shiha\ML-Projects\drinks-quality-prediction-system1
```

The Apache-served copy should be:

```text
C:\xampp\htdocs\week5_xampp_app
```

### Step 1 — Copy the active application

Open File Explorer and copy this complete folder:

```text
C:\Users\shiha\ML-Projects\drinks-quality-prediction-system1\week5_xampp_app
```

Paste it directly into:

```text
C:\xampp\htdocs
```

Confirm that this exact file exists afterward:

```text
C:\xampp\htdocs\week5_xampp_app\profile.php
```

If an older `C:\xampp\htdocs\week5_xampp_app` already exists, copy the updated project files into it and allow the Week 6 files to replace their older application versions. MySQL data is stored by MySQL, not in this application folder.

### Step 2 — Start XAMPP services

1. Open XAMPP Control Panel.
2. Click **Start** beside Apache.
3. Click **Start** beside MySQL.
4. Confirm both rows turn green.

### Step 3 — Open phpMyAdmin

Open:

```text
http://localhost/phpmyadmin/
```

### Step 4 — Choose exactly one database path

For an **existing Week 5 database with data**:

1. Click `drinks_quality_db` in the left sidebar.
2. Click **Import**.
3. Click **Choose File**.
4. Select `C:\xampp\htdocs\week5_xampp_app\database\week6_security_update.sql`.
5. Click **Go**.
6. Open `users` → **Structure** and confirm a nullable `bio` column appears after `role`.
7. Import `C:\xampp\htdocs\week5_xampp_app\database\ml_model_integration_update.sql`.
8. Open `models` → **Browse** and confirm `Drinks Quality Classifier` version `1.0` is active.

For a **genuinely fresh installation with nothing to preserve**:

1. Click **Import** from the phpMyAdmin home page.
2. Select `C:\xampp\htdocs\week5_xampp_app\database\schema.sql`.
3. Click **Go**.
4. Confirm `drinks_quality_db` contains `users`, `models`, `drink_samples`, and `predictions`.
5. Do not run either migration because the fresh schema already contains `bio` and the trained-model record.

### Step 5 — Start the trained-model API

Open a separate PowerShell terminal and keep this command running:

```powershell
Set-Location "C:\Users\shiha\ML-Projects\drinks-quality-prediction-system1"
conda activate mlproj
python ml_api.py
```

Open `http://127.0.0.1:5000/health` and confirm it returns `"status": "ok"`.

### Step 6 — Open the application

Open:

```text
http://localhost/week5_xampp_app/
```

Useful direct URLs are:

```text
http://localhost/week5_xampp_app/login.php
http://localhost/week5_xampp_app/register.php
http://localhost/week5_xampp_app/dashboard.php
http://localhost/week5_xampp_app/profile.php
http://localhost/week5_xampp_app/history.php
```

Do not open PHP files with `file:///`, VS Code Live Server, or Flask port 5000. PHP executes through XAMPP Apache; Flask port 5000 is only the local prediction API.

### Optional PHP syntax and PDO checks

Run these commands in PowerShell after copying the application:

```powershell
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\profile.php"
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\login.php"
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\register.php"
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\dashboard.php"
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\history.php"
& "C:\xampp\php\php.exe" -l "C:\xampp\htdocs\week5_xampp_app\predict.php"
```

With MySQL running, test the PDO connection:

```powershell
& "C:\xampp\php\php.exe" -r "require 'C:\xampp\htdocs\week5_xampp_app\config\db.php'; if (`$pdo instanceof PDO) { echo 'Database connection OK', PHP_EOL; } else { echo `$database_connection_error, PHP_EOL; exit(1); }"
```

Expected output:

```text
Database connection OK
```

## 15. Security tests — complete student manual plan

Run the tests in order. Use a private/incognito browser window if an old session interferes.

### Test 1 — Database connection and Week 6 schema

**Action:**

1. Start Apache and MySQL.
2. Open `http://localhost/phpmyadmin/`.
3. Select `drinks_quality_db`.
4. Confirm the four tables are `users`, `models`, `drink_samples`, and `predictions`.
5. Open `users` → **Structure**.
6. Confirm these columns exist: `user_id`, `full_name`, `email`, `password_hash`, `role`, `bio`, and `created_at`.
7. Run the optional PDO command from Section 14, or open `http://localhost/week5_xampp_app/login.php` and confirm no database-connection warning appears.

**Expected result:** The PDO check reports `Database connection OK`, and `bio` is a nullable `TEXT` column. No raw MySQL/PDO exception appears in the browser.

### Test 2 — Registration and duplicate email

**Action:**

1. Open `http://localhost/week5_xampp_app/register.php`.
2. Register with:

   ```text
   Full Name: Week Six Test
   Email: week6test@example.com
   Password: Test12345!
   Confirm Password: Test12345!
   ```

3. Click **Register**.
4. Return to Register and submit the exact same details again.
5. In phpMyAdmin, select `drinks_quality_db` → `users` → **Browse**.

**Expected result:** The first registration succeeds and returns to Login. The duplicate attempt is rejected. Exactly one row has `email = week6test@example.com`, its role is `User`, and its initial `bio` is `NULL`.

### Test 3 — Password hashing

**Action:**

1. In phpMyAdmin, go to `drinks_quality_db` → `users` → **Browse**.
2. Locate the row whose email is `week6test@example.com`.
3. Inspect the `password_hash` column.

**Expected result:** `password_hash` contains a long value, normally beginning with `$2y$` on this PHP version. It must not equal or contain the readable password `Test12345!`. There is no plain `password` column.

### Test 4 — Correct login and session ID regeneration

**Action:**

1. Open the Login page.
2. Before signing in, open browser Developer Tools → **Application** → **Cookies** → `http://localhost`.
3. Note the current `dqps_session` cookie value created for the login/CSRF session.
4. Login with:

   ```text
   Email: week6test@example.com
   Password: Test12345!
   ```

5. Inspect `dqps_session` again after Dashboard opens.

**Expected result:** Dashboard opens, shows `Week Six Test`, and the session cookie value differs from the pre-login value because `session_regenerate_id(true)` ran.

### Test 5 — Wrong password and unknown email

**Action:**

1. Log out.
2. Try `week6test@example.com` with password `WrongPassword123`.
3. Then try `nobody@example.com` with password `Test12345!`.

**Expected result:** Neither attempt authenticates. Both show the same generic message, `Invalid email or password.`, and neither reveals whether the email exists.

### Test 6 — SQL Injection attempt

**Action:**

1. On Login, enter the following in the **Email** field:

   ```text
   ' OR '1'='1
   ```

2. Enter any password and click Login.
3. If the browser's built-in email-format validation prevents submission, that is an additional client-side block. To complete a second bypass attempt, enter `week6test@example.com` in Email and the same injection text in Password.

**Expected result:** Authentication fails. No account, dashboard, profile, history, or Admin access is granted. The source-level defense is the prepared email parameter plus `password_verify()`, not client-side validation alone.

### Test 7 — Profile access protection

**Action:**

1. Ensure you are logged out.
2. Manually enter:

   ```text
   http://localhost/week5_xampp_app/profile.php
   ```

**Expected result:** The application redirects to Login and asks you to sign in. No profile data or form is displayed.

### Test 8 — Profile loading and bio update

**Action:**

1. Login as `week6test@example.com` with `Test12345!`.
2. Click **Profile** in the authenticated navigation.
3. Confirm the page displays `Week Six Test`, `week6test@example.com`, role `User`, and an account creation date.
4. Enter this Bio:

   ```text
   I am testing the Week 6 secure profile system.
   ```

5. Click **Update Profile**.
6. Refresh the page or navigate away and return to Profile.
7. In phpMyAdmin, open `drinks_quality_db` → `users` → **Browse**, find `week6test@example.com`, and inspect the `bio` column.

**Expected result:** A success message appears, the bio remains after refresh, and the exact text is stored in `users.bio` for the signed-in user's row only.

### Test 9 — Stored XSS protection

**Action:**

1. While logged in, open Profile.
2. Replace the Bio with:

   ```html
   <script>alert('XSS')</script>
   ```

3. Click **Update Profile**.
4. Refresh Profile.
5. Optionally inspect the `bio` value in phpMyAdmin.

**Expected result:** No JavaScript alert opens. Profile displays the literal script text as harmless text; it does not create or execute a script element. phpMyAdmin may show the original raw characters in `bio`, which is expected because protection occurs when output is escaped. After recording the result, restore the friendly bio from Test 8 if desired.

### Test 10 — Session and logout protection

**Action:**

1. Login and open Dashboard, Profile, and History.
2. Click **Logout** in the navigation.
3. Press the browser Back button, then refresh.
4. Directly revisit:

   ```text
   http://localhost/week5_xampp_app/dashboard.php
   http://localhost/week5_xampp_app/profile.php
   http://localhost/week5_xampp_app/history.php
   ```

5. For the inactivity check, login again, leave the application inactive for more than five minutes, and then refresh a protected page.

**Expected result:** Logout returns to Login with a success message. Refreshing or directly requesting protected pages redirects to Login. After five inactive minutes, the application destroys the old authenticated session and reports that it expired.

### Test 11 — Existing prediction feature

**Action:**

1. Login as `week6test@example.com`.
2. On Dashboard, scroll to **Enter Drink Feature Values**.
3. Click **Use Sample Data** and confirm these values appear:

   | Field | Value |
   |---|---:|
   | Fixed Acidity | 7.4 |
   | Volatile Acidity | 0.70 |
   | Citric Acid | 0.00 |
   | Residual Sugar | 1.9 |
   | Chlorides | 0.076 |
   | Free Sulfur Dioxide | 11 |
   | Total Sulfur Dioxide | 34 |
   | Density | 0.9978 |
   | pH | 3.51 |
   | Sulphates | 0.56 |
   | Alcohol | 9.4 |

4. Click **Predict and Save**.

**Expected result:** The result page shows trained-model quality class `5` and label `Average`, along with a new prediction ID and `Drinks Quality Classifier` version `1.0`. A new row is stored in `drink_samples`, and a related row is stored in `predictions`.

### Test 12 — Existing prediction history

**Action:**

1. Click **View Saved History** after Test 11, or click **History** in the navigation.
2. Find the new prediction.
3. In phpMyAdmin, inspect `drink_samples` and `predictions`.

**Expected result:** The normal user sees the new `5` / `Average` result, the sample values, trained model, and date. `predictions.sample_id` points to the new `drink_samples.sample_id`, and that sample's `user_id` points to the Week Six Test account. A normal user sees only history associated with that user's session ID.

### Test 13 — Admin role behavior

**Action:**

1. In phpMyAdmin, confirm `demo@gmail.com` exists in `users` and its `role` is `Admin`. A fresh schema creates this account automatically.
2. Log out from the normal account.
3. Login with:

   ```text
   Email: demo@gmail.com
   Password: DemoUser123!
   ```

4. Open History.
5. Confirm the table includes a User column and can show the Week Six Test prediction.
6. Log out, return as `week6test@example.com`, and compare History.

**Expected result:** The Admin view can display records belonging to all users, including `week6test@example.com`. The normal user view remains restricted to that user's own predictions. Profile still edits only the currently authenticated account regardless of role. If an upgraded database does not contain the seeded demo account, record this test as pending rather than changing role controls merely to force a pass.

## 16. Expected test results

| Test | Required result before submission |
|---|---|
| 1. Database | PDO connects; four tables and nullable `users.bio` exist; no raw exception is exposed. |
| 2. Registration | New user succeeds; duplicate email is rejected; one user row exists. |
| 3. Password hash | Stored value is a secure hash and not `Test12345!`. |
| 4. Correct login | Dashboard opens and `dqps_session` changes after login. |
| 5. Invalid credentials | Wrong password and unknown email both fail with the same generic message. |
| 6. SQL Injection | `' OR '1'='1` does not authenticate or expose protected content. |
| 7. Profile protection | Logged-out direct access redirects to Login. |
| 8. Profile update | Bio saves through POST and persists for the signed-in user. |
| 9. XSS | No alert executes; script markup appears only as harmless text. |
| 10. Session/logout | Logout and timeout remove protected access. |
| 11. Prediction | Sample data produces `5` / `Average` through the trained model and saves a result. |
| 12. History | The saved result appears with correct user relationships and user scoping. |
| 13. Admin | Admin sees all-user history; normal user remains limited to personal history. |

Do not mark a browser/database result as passed merely because the source code looks correct. Record the observed result after Apache and MySQL are running.

## 17. Week 6 requirements checklist

### Source implementation status

| Requirement | Repository status | Evidence |
|---|---|---|
| Central PDO connection | Implemented; live connection pending | `week5_xampp_app/config/db.php` |
| `PDO::ERRMODE_EXCEPTION` | Implemented in code | `week5_xampp_app/config/db.php` |
| Associative default fetch mode | Implemented in code | `week5_xampp_app/config/db.php` |
| Emulated prepares disabled | Implemented in code | `week5_xampp_app/config/db.php` |
| Prepared dynamic SQL | Implemented in code | Registration, login, dashboard, prediction, history, and profile PHP files |
| SQL Injection defense | Implemented in code; manual bypass test pending | Prepared statements and Test 6 |
| Secure registration | Previously implemented; live test pending | `week5_xampp_app/register.php` |
| `password_hash()` | Previously implemented; database inspection pending | `week5_xampp_app/register.php` |
| `password_verify()` | Previously implemented; live test pending | `week5_xampp_app/login.php` |
| Generic login failure | Improved in Week 6 | `week5_xampp_app/login.php` |
| `session_regenerate_id(true)` | Previously implemented; cookie observation pending | `week5_xampp_app/includes/auth.php` |
| Protected pages and timeout | Previously implemented and reused; browser test pending | `week5_xampp_app/includes/auth.php` |
| Protected profile | Added in Week 6; browser test pending | `week5_xampp_app/profile.php` |
| Parameterized profile `UPDATE` | Added in Week 6; persistence test pending | `week5_xampp_app/profile.php` |
| `htmlspecialchars()` escaping | Implemented through shared `e()`; XSS browser test pending | `week5_xampp_app/config/app.php` and output sites |
| Additive `bio` migration | Added; live import pending | `week5_xampp_app/database/week6_security_update.sql` |
| Trained-model integration migration | Added; live import pending | `week5_xampp_app/database/ml_model_integration_update.sql` |
| Fresh schema includes `bio` | Added in both schema copies | Both `database/schema.sql` files |
| Corrected 11-feature model API | Implemented; live XAMPP test pending | `ml_api.py`, prediction service, and PHP ML client |
| Week 6 documentation | Completed | `README_WEEK6.md` |

### Student submission checklist

- [ ] Apache starts in XAMPP.
- [ ] MySQL starts in XAMPP.
- [ ] Correct upgrade migration or fresh schema path was used.
- [ ] Existing databases received `ml_model_integration_update.sql`.
- [ ] Existing data was backed up before any destructive fresh-schema import.
- [ ] PDO connection works.
- [ ] `PDO::ERRMODE_EXCEPTION` is enabled.
- [ ] Default fetch mode is associative.
- [ ] Emulated prepares are disabled.
- [ ] Registration works.
- [ ] Duplicate registration is rejected.
- [ ] Password is stored as a hash, not plain text.
- [ ] Correct login works.
- [ ] Wrong password and unknown email fail generically.
- [ ] `password_verify()` is used.
- [ ] The session ID changes after successful login.
- [ ] Prepared statements protect dynamic queries.
- [ ] SQL Injection attempt fails safely.
- [ ] Dashboard, History, and Profile are session protected.
- [ ] Profile data loads from the database.
- [ ] Profile bio update persists.
- [ ] XSS payload does not execute.
- [ ] Logout and inactivity timeout remove access.
- [ ] Trained-model API reports healthy and sample data produces `5` / `Average`.
- [ ] Existing prediction history still works.
- [ ] Admin/user history restrictions still work.
- [ ] The corrected model has exactly 11 measurement features and excludes `Id`.
- [ ] `README_WEEK6.md` is complete.
- [ ] `git status` and `git diff` were reviewed.
- [ ] All manual results were recorded honestly.
- [ ] Week 6 commit was created only after tests passed.
- [ ] Correct branch was pushed.

## Git instructions after all tests pass

At the time this document was created, the repository was on `main`, tracking `origin/main`. Recheck rather than assuming:

```powershell
Set-Location "C:\Users\shiha\ML-Projects\drinks-quality-prediction-system1"
git branch --show-current
git status
git diff
git diff --check
```

Review every changed and untracked file. If `git branch --show-current` still prints `main` and the manual tests pass, run:

```powershell
git add README.md README_WEEK6.md README_ML_INTEGRATION.md app.py ml_api.py config params.yaml schema.yaml src tests artifacts/data_transformation/train.csv artifacts/data_transformation/test.csv artifacts/model_trainer/model.pkl artifacts/model_trainer/model_metadata.json artifacts/model_evaluation/scores.json database/schema.sql week5_xampp_app
git status
git diff --cached
git commit -m "Complete Week 6 database security and profile integration"
git push origin main
```

If the branch check prints a different branch, do not push it to `main` blindly. Commit on that branch and push its actual name:

```powershell
git push -u origin YOUR_CURRENT_BRANCH
```

Replace `YOUR_CURRENT_BRANCH` with the exact output of `git branch --show-current`. Do not commit local database passwords, logs, or unrelated files.
