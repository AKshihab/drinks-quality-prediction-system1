<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'], $_SESSION['user_email'])) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php');
    exit();
}

$timeout_duration = 300;

if (
    isset($_SESSION['last_activity'])
    && (time() - (int) $_SESSION['last_activity']) > $timeout_duration
) {
    $_SESSION = [];
    session_destroy();

    header('Location: login.php?msg=' . urlencode('Session timed out due to inactivity.'));
    exit();
}

$_SESSION['last_activity'] = time();

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'] ?? 'User';
$login_time = (int) ($_SESSION['login_time'] ?? time());
$is_admin = $user_role === 'Admin';
$predictions = [];
$dashboard_error = '';

require_once __DIR__ . '/config/db.php';

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
    <title>Dashboard | Drinks Quality Prediction System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="page-center dashboard-page">
        <section class="card dashboard-card">
            <div class="dashboard-header">
                <div>
                    <p class="eyebrow">Database-backed dashboard</p>
                    <h1>Drinks Quality Prediction System</h1>
                </div>
                <a href="logout.php" class="button logout-button">Log out</a>
            </div>

            <div class="message success" role="status">
                You are securely authenticated.
            </div>

            <div class="account-details dashboard-meta-grid">
                <div class="detail-row">
                    <span>Name</span>
                    <strong><?php echo escape_output($user_name); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Email</span>
                    <strong><?php echo escape_output($user_email); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Role</span>
                    <strong><?php echo escape_output($user_role); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Login time</span>
                    <strong><?php echo escape_output(date('F j, Y, g:i a', $login_time)); ?></strong>
                </div>

                <div class="detail-row">
                    <span>Total predictions</span>
                    <strong><?php echo escape_output($prediction_count); ?></strong>
                </div>
            </div>

            <?php if ($dashboard_error !== ''): ?>
                <div class="message error database-message" role="alert">
                    <?php echo escape_output($dashboard_error); ?>
                </div>
            <?php endif; ?>

            <section class="history-section" aria-labelledby="history-title">
                <div class="history-heading">
                    <div>
                        <p class="eyebrow">Relational data</p>
                        <h2 id="history-title">Prediction history</h2>
                    </div>
                    <p>
                        <?php if ($is_admin): ?>
                            Admin view: all users' seeded prediction records.
                        <?php else: ?>
                            User view: only your prediction records.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($dashboard_error === '' && $predictions === []): ?>
                    <div class="empty-state" role="status">
                        No prediction history is available for this account yet.
                    </div>
                <?php elseif ($predictions !== []): ?>
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
            </section>

            <p class="timeout-note">For security, this session expires after 5 minutes of inactivity.</p>
        </section>
    </main>
</body>
</html>

