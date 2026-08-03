<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'] ?? 'User';
$login_time = (int) ($_SESSION['login_time'] ?? time());
$is_admin = $user_role === 'Admin';
$predictions = [];
$dashboard_error = '';

if ($pdo instanceof PDO) {
    try {
        $query = 'SELECT
                    p.prediction_id,
                    ds.sample_id,
                    u.full_name AS record_owner,
                    m.model_name,
                    m.algorithm,
                    ds.alcohol,
                    ds.ph,
                    p.predicted_quality,
                    p.quality_label,
                    p.created_at AS prediction_date
                  FROM predictions AS p
                  INNER JOIN drink_samples AS ds
                    ON p.sample_id = ds.sample_id
                  INNER JOIN users AS u
                    ON ds.user_id = u.user_id
                  INNER JOIN models AS m
                    ON p.model_id = m.model_id';

        if ($is_admin) {
            $query .= ' ORDER BY p.created_at DESC';
            $statement = $pdo->prepare($query);
            $statement->execute();
        } else {
            $query .= ' WHERE ds.user_id = :user_id ORDER BY p.created_at DESC';
            $statement = $pdo->prepare($query);
            $statement->execute(['user_id' => $user_id]);
        }

        $predictions = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Dashboard prediction query failed: ' . $exception->getMessage());
        $dashboard_error = 'Prediction history is temporarily unavailable. Please check that MySQL is running and try again.';
    }
} else {
    $dashboard_error = $database_connection_error !== ''
        ? $database_connection_error
        : 'Prediction history is temporarily unavailable.';
}

$prediction_count = count($predictions);

function escape_output($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Machine learning based drinks quality prediction web application">
    <meta name="author" content="Group 7">
    <title>Dashboard | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="app-header">
        <div class="container header-content">
            <a href="dashboard.php" class="brand">
                <span class="brand-icon">DQ</span>
                <span>Drinks Quality Prediction</span>
            </a>

            <nav class="app-nav result-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero-section">
            <div class="container hero-grid">
                <div class="hero-text">
                    <p class="tagline">Machine Learning Project</p>
                    <h1>Drinks Quality Prediction System</h1>
                    <p class="hero-description">
                        Predict the quality score of drinks by entering physicochemical values such as acidity,
                        residual sugar, chlorides, density, pH, sulphates, and alcohol.
                    </p>

                    <h2 class="welcome-user">
                        Welcome,
                        <?= htmlspecialchars(
                            $_SESSION['user_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h2>

                    <p class="welcome-email">
                        <?= htmlspecialchars(
                            $_SESSION['user_email'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                </div>

                <aside class="hero-card" aria-label="Account summary">
                    <h2>Account Summary</h2>
                    <ul>
                        <li><strong>Role:</strong> <?php echo escape_output($user_role); ?></li>
                        <li><strong>Signed in:</strong> <?php echo escape_output(date('F j, Y, g:i a', $login_time)); ?></li>
                        <li><strong>Total predictions:</strong> <?php echo escape_output($prediction_count); ?></li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="history-section">
            <div class="container">
                <div class="section-heading history-heading">
                    <p class="tagline">Relational data</p>
                    <h2 id="history-title">Prediction history</h2>
                    <p>
                        <?php if ($is_admin): ?>
                            Admin view: all users' seeded prediction records.
                        <?php else: ?>
                            User view: only your prediction records.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($dashboard_error !== ''): ?>
                    <div class="message error" role="alert">
                        <?php echo escape_output($dashboard_error); ?>
                    </div>
                <?php elseif ($predictions === []): ?>
                    <div class="empty-state" role="status">
                        No prediction history is available for this account yet.
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <caption>Prediction history retrieved by joining users, drink samples, predictions, and models.</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Prediction ID</th>
                                    <th scope="col">Sample ID</th>
                                    <?php if ($is_admin): ?>
                                        <th scope="col">Submitted by</th>
                                    <?php endif; ?>
                                    <th scope="col">Model</th>
                                    <th scope="col">Algorithm</th>
                                    <th scope="col">Alcohol</th>
                                    <th scope="col">pH</th>
                                    <th scope="col">Quality</th>
                                    <th scope="col">Label</th>
                                    <th scope="col">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($predictions as $prediction): ?>
                                    <tr>
                                        <td><?php echo escape_output($prediction['prediction_id']); ?></td>
                                        <td><?php echo escape_output($prediction['sample_id']); ?></td>
                                        <?php if ($is_admin): ?>
                                            <td><?php echo escape_output($prediction['record_owner']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo escape_output($prediction['model_name']); ?></td>
                                        <td><?php echo escape_output($prediction['algorithm']); ?></td>
                                        <td><?php echo escape_output(number_format((float) $prediction['alcohol'], 2)); ?></td>
                                        <td><?php echo escape_output(number_format((float) $prediction['ph'], 2)); ?></td>
                                        <td><?php echo escape_output(number_format((float) $prediction['predicted_quality'], 2)); ?></td>
                                        <td><?php echo escape_output($prediction['quality_label']); ?></td>
                                        <td><?php echo escape_output($prediction['prediction_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="app-footer">
        <div class="container">
            <p>&copy; 2026 Group 7 - Drinks Quality Prediction System</p>
            <p>Software Development Project III | Northern University Bangladesh</p>
        </div>
    </footer>
</body>
</html>
