<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/db.php';
require_login();

$rows = [];
$error = '';
$isAdmin = ($_SESSION['user_role'] ?? 'User') === 'Admin';

if (!$pdo instanceof PDO) {
    $error = $database_connection_error;
} else {
    try {
        $sql = 'SELECT p.prediction_id, p.predicted_quality, p.quality_label, p.created_at,
                       ds.sample_id, ds.alcohol, ds.ph, ds.fixed_acidity,
                       m.model_name, u.full_name, u.email
                FROM predictions p
                INNER JOIN drink_samples ds ON ds.sample_id = p.sample_id
                INNER JOIN models m ON m.model_id = p.model_id
                INNER JOIN users u ON u.user_id = ds.user_id';

        if ($isAdmin) {
            $sql .= ' ORDER BY p.created_at DESC';
            $statement = $pdo->prepare($sql);
            $statement->execute();
        } else {
            $sql .= ' WHERE ds.user_id = :user_id ORDER BY p.created_at DESC';
            $statement = $pdo->prepare($sql);
            $statement->execute(['user_id' => $_SESSION['user_id']]);
        }
        $rows = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('History query failed: ' . $exception->getMessage());
        $error = 'Prediction history could not be loaded.';
    }
}

render_header('Prediction History', 'history');
?>
<main class="history-page">
    <section class="container history-panel">
        <div class="history-heading">
            <div><p class="tagline">Database Records</p><h1><?php echo $isAdmin ? 'All User Prediction History' : 'My Prediction History'; ?></h1><p><?php echo $isAdmin ? 'Admin view includes records from every registered user.' : 'Only records linked to your user ID are shown.'; ?></p></div>
            <a href="dashboard.php#prediction" class="primary-btn">New Prediction</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="flash-message error"><?php echo e($error); ?></div>
        <?php elseif (!$rows): ?>
            <div class="empty-state">No predictions are saved for this account yet.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><?php if ($isAdmin): ?><th>User</th><?php endif; ?><th>Sample</th><th>Fixed acidity</th><th>pH</th><th>Alcohol</th><th>Quality</th><th>Label</th><th>Model</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo e($row['prediction_id']); ?></td>
                            <?php if ($isAdmin): ?><td><?php echo e($row['full_name']); ?><br><small><?php echo e($row['email']); ?></small></td><?php endif; ?>
                            <td><?php echo e($row['sample_id']); ?></td>
                            <td><?php echo e($row['fixed_acidity']); ?></td>
                            <td><?php echo e($row['ph']); ?></td>
                            <td><?php echo e($row['alcohol']); ?></td>
                            <td><strong><?php echo e($row['predicted_quality']); ?></strong></td>
                            <td><?php echo e($row['quality_label']); ?></td>
                            <td><?php echo e($row['model_name']); ?></td>
                            <td><?php echo e($row['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php render_footer(); ?>
