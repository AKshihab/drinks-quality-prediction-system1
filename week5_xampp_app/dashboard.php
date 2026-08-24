<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/db.php';
require_login();

$predictionCount = 0;
if ($pdo instanceof PDO) {
    try {
        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM predictions p
             INNER JOIN drink_samples ds ON ds.sample_id = p.sample_id
             WHERE ds.user_id = :user_id'
        );
        $count->execute(['user_id' => $_SESSION['user_id']]);
        $predictionCount = (int) $count->fetchColumn();
    } catch (PDOException $exception) {
        error_log('Prediction count failed: ' . $exception->getMessage());
    }
}

$notice = pull_flash();
render_header('Dashboard', 'home');
?>
<main>
    <?php if ($notice): ?>
        <div class="container page-flash"><div class="flash-message <?php echo e($notice['type']); ?>"><?php echo e($notice['message']); ?></div></div>
    <?php endif; ?>

    <section class="hero-section" id="home">
        <div class="container hero-grid">
            <div class="hero-text">
                <p class="tagline">Protected Main Website</p>
                <h1>Drinks Quality Prediction System</h1>
                <p class="hero-description">This page opens only after MySQL verifies the exact registered email and password.</p>
                <p class="welcome-user">Welcome, <?php echo e($_SESSION['user_name']); ?> (<?php echo e($_SESSION['user_email']); ?>)</p>
                <div class="hero-actions">
                    <a href="#prediction" class="primary-btn">Start Prediction</a>
                    <a href="history.php" class="secondary-btn">View My History</a>
                </div>
            </div>
            <aside class="hero-card" aria-label="Account summary">
                <h2>Signed-in Account</h2>
                <ul>
                    <li>Role: <?php echo e($_SESSION['user_role']); ?></li>
                    <li>Your saved predictions: <?php echo $predictionCount; ?></li>
                    <li>Session protection enabled</li>
                    <li>Logout available in the navbar</li>
                </ul>
            </aside>
        </div>
    </section>

    <section class="stats-section">
        <div class="container stats-grid">
            <article class="stat-card"><h3>Authentication</h3><p>PHP Session + MySQL</p></article>
            <article class="stat-card"><h3>Accounts</h3><p>Multiple Unique Emails</p></article>
            <article class="stat-card"><h3>Password Storage</h3><p>Secure Hashes</p></article>
        </div>
    </section>

    <section class="prediction-section" id="prediction">
        <div class="container">
            <div class="section-heading">
                <p class="tagline">Prediction Dashboard</p>
                <h2>Enter Drink Feature Values</h2>
                <p>The trained Python model validates these measurements, predicts the quality class, and stores the sample and result under your account.</p>
            </div>

            <?php if ($database_connection_error !== ''): ?>
                <div class="flash-message error"><?php echo e($database_connection_error); ?></div>
            <?php endif; ?>

            <form class="prediction-form" id="predictionForm" action="predict.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <?php
                $fields = [
                    'fixed_acidity' => ['Fixed Acidity', '7.4'],
                    'volatile_acidity' => ['Volatile Acidity', '0.70'],
                    'citric_acid' => ['Citric Acid', '0.00'],
                    'residual_sugar' => ['Residual Sugar', '1.9'],
                    'chlorides' => ['Chlorides', '0.076'],
                    'free_sulfur_dioxide' => ['Free Sulfur Dioxide', '11'],
                    'total_sulfur_dioxide' => ['Total Sulfur Dioxide', '34'],
                    'density' => ['Density', '0.9978'],
                    'ph' => ['pH', '3.51'],
                    'sulphates' => ['Sulphates', '0.56'],
                    'alcohol' => ['Alcohol', '9.4'],
                ];
                foreach ($fields as $name => [$label, $example]):
                ?>
                    <div class="form-group">
                        <label for="<?php echo e($name); ?>"><?php echo e($label); ?></label>
                        <input type="number" step="any" min="0" id="<?php echo e($name); ?>" name="<?php echo e($name); ?>" placeholder="Example: <?php echo e($example); ?>" required>
                        <small class="error-message" aria-live="polite"></small>
                    </div>
                <?php endforeach; ?>
                <div class="form-actions">
                    <button type="submit" class="primary-btn">Predict and Save</button>
                    <button type="button" class="secondary-btn" id="sampleDataBtn">Use Sample Data</button>
                    <button type="reset" class="clear-btn">Clear</button>
                </div>
            </form>
        </div>
    </section>

    <section class="features-section" id="features">
        <div class="container">
            <div class="section-heading"><p class="tagline">System Features</p><h2>Week 5 Database Integration</h2></div>
            <div class="features-grid">
                <article class="feature-card"><h3>Real Registration</h3><p>Every new email is inserted into the users table. Duplicate emails are rejected.</p></article>
                <article class="feature-card"><h3>Real Login</h3><p>The email is queried with a prepared statement and the password hash is verified securely.</p></article>
                <article class="feature-card"><h3>Protected Website</h3><p>Directly opening dashboard.php without a valid session redirects the visitor to login.php.</p></article>
                <article class="feature-card"><h3>Real Logout</h3><p>Logout destroys the session and prevents the protected website from opening through the Back button.</p></article>
                <article class="feature-card"><h3>Separate User History</h3><p>Normal users see only predictions connected to their own user ID.</p></article>
                <article class="feature-card"><h3>Trained ML Prediction</h3><p>PHP calls the local Python API, which loads the validated scikit-learn model artifact.</p></article>
            </div>
        </div>
    </section>

    <section class="about-section" id="about">
        <div class="container about-content">
            <div><p class="tagline">About the Project</p><h2>PHP Website with Python ML Inference</h2><p>PHP controls authentication, forms, and MySQL history while a localhost Flask API performs inference with the trained model.</p></div>
            <div class="about-box"><h3>Technology Stack</h3><p>PHP 8+, MySQL/MariaDB, Python, Flask, scikit-learn, PDO prepared statements, sessions, CSRF protection, HTML, CSS, and JavaScript.</p></div>
        </div>
    </section>
</main>
<?php render_footer(); ?>
