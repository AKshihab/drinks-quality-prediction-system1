<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/config/db.php';
require_login();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect('dashboard.php#prediction');
}

if (!verify_csrf()) {
    flash('error', 'The prediction form expired. Please try again.');
    redirect('dashboard.php#prediction');
}

$fieldNames = [
    'fixed_acidity', 'volatile_acidity', 'citric_acid', 'residual_sugar',
    'chlorides', 'free_sulfur_dioxide', 'total_sulfur_dioxide', 'density',
    'pH', 'sulphates', 'alcohol',
];
$values = [];
foreach ($fieldNames as $field) {
    $raw = trim((string) ($_POST[$field] ?? ''));
    if ($raw === '' || !is_numeric($raw) || (float) $raw < 0) {
        flash('error', 'Every prediction value must be a valid non-negative number.');
        redirect('dashboard.php#prediction');
    }
    $values[$field] = (float) $raw;
}

if (!$pdo instanceof PDO) {
    flash('error', $database_connection_error);
    redirect('dashboard.php#prediction');
}

// Demonstration calculation for the Week 5 web/database prototype.
// Replace this block with the trained Python model/API when the ML integration is ready.
$score = 5.4
    + (($values['alcohol'] - 10.0) * 0.28)
    - (abs($values['volatile_acidity'] - 0.45) * 1.10)
    + (($values['sulphates'] - 0.55) * 0.35)
    - (abs($values['pH'] - 3.30) * 0.20)
    - (max(0.0, $values['chlorides'] - 0.08) * 2.0);
$score = max(3.0, min(8.5, round($score, 2)));
$label = match (true) {
    $score >= 7.0 => 'Excellent',
    $score >= 6.0 => 'Good',
    $score >= 5.0 => 'Average',
    default => 'Poor',
};

try {
    $pdo->beginTransaction();

    $sample = $pdo->prepare(
        'INSERT INTO drink_samples (
            user_id, fixed_acidity, volatile_acidity, citric_acid, residual_sugar,
            chlorides, free_sulfur_dioxide, total_sulfur_dioxide, density, ph,
            sulphates, alcohol
        ) VALUES (
            :user_id, :fixed_acidity, :volatile_acidity, :citric_acid, :residual_sugar,
            :chlorides, :free_sulfur_dioxide, :total_sulfur_dioxide, :density, :ph,
            :sulphates, :alcohol
        )'
    );
    $sample->execute([
        'user_id' => $_SESSION['user_id'],
        'fixed_acidity' => $values['fixed_acidity'],
        'volatile_acidity' => $values['volatile_acidity'],
        'citric_acid' => $values['citric_acid'],
        'residual_sugar' => $values['residual_sugar'],
        'chlorides' => $values['chlorides'],
        'free_sulfur_dioxide' => $values['free_sulfur_dioxide'],
        'total_sulfur_dioxide' => $values['total_sulfur_dioxide'],
        'density' => $values['density'],
        'ph' => $values['pH'],
        'sulphates' => $values['sulphates'],
        'alcohol' => $values['alcohol'],
    ]);

    $sampleId = (int) $pdo->lastInsertId();
    $modelId = (int) $pdo->query('SELECT model_id FROM models WHERE is_active = 1 ORDER BY model_id LIMIT 1')->fetchColumn();
    if ($modelId < 1) {
        throw new RuntimeException('No active model record exists.');
    }

    $prediction = $pdo->prepare(
        'INSERT INTO predictions (sample_id, model_id, predicted_quality, quality_label)
         VALUES (:sample_id, :model_id, :score, :label)'
    );
    $prediction->execute([
        'sample_id' => $sampleId,
        'model_id' => $modelId,
        'score' => $score,
        'label' => $label,
    ]);

    $predictionId = (int) $pdo->lastInsertId();
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Prediction save failed: ' . $exception->getMessage());
    flash('error', 'The prediction could not be saved. Check the database installation.');
    redirect('dashboard.php#prediction');
}

render_header('Prediction Result');
?>
<main class="result-main">
    <section class="result-section">
        <div class="container result-card">
            <p class="tagline">Prediction Saved</p>
            <h1>Predicted Drink Quality Score</h1>
            <div class="score-box"><span class="score-label">Prototype Quality Score</span><strong class="score-value"><?php echo e(number_format($score, 2)); ?></strong><span class="quality-badge"><?php echo e($label); ?></span></div>
            <p class="result-note">Prediction ID: <?php echo $predictionId; ?>. This Week 5 version uses a demonstration formula and stores the result in MySQL. Connect the trained Python model later for the final ML score.</p>
            <div class="result-actions"><a href="dashboard.php#prediction" class="primary-btn">Make Another Prediction</a><a href="history.php" class="secondary-btn">View Saved History</a></div>
        </div>
    </section>
</main>
<?php render_footer(); ?>
