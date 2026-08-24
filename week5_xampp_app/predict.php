<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/ml_client.php';
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
    'ph', 'sulphates', 'alcohol',
];
$values = [];
foreach ($fieldNames as $field) {
    $submittedValue = $_POST[$field] ?? null;
    if (!is_scalar($submittedValue)) {
        flash('error', 'Every prediction value must be a valid non-negative number.');
        redirect('dashboard.php#prediction');
    }

    $raw = trim((string) $submittedValue);
    if ($raw === '' || !is_numeric($raw)) {
        flash('error', 'Every prediction value must be a valid non-negative number.');
        redirect('dashboard.php#prediction');
    }

    $numericValue = (float) $raw;
    if (!is_finite($numericValue) || $numericValue < 0) {
        flash('error', 'Every prediction value must be a valid non-negative number.');
        redirect('dashboard.php#prediction');
    }
    $values[$field] = $numericValue;
}

if (!$pdo instanceof PDO) {
    flash('error', $database_connection_error);
    redirect('dashboard.php#prediction');
}

try {
    $modelResult = request_model_prediction($values);
} catch (MlPredictionException $exception) {
    error_log('Model prediction request failed: ' . $exception->getMessage());
    flash('error', $exception->browserMessage());
    redirect('dashboard.php#prediction');
}

$score = $modelResult['predicted_quality'];
$label = $modelResult['quality_label'];
$modelInfo = $modelResult['model'];

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
        'ph' => $values['ph'],
        'sulphates' => $values['sulphates'],
        'alcohol' => $values['alcohol'],
    ]);

    $sampleId = (int) $pdo->lastInsertId();
    $modelStatement = $pdo->prepare(
        'SELECT model_id
         FROM models
         WHERE model_name = :model_name
           AND model_version = :model_version
           AND is_active = 1
         LIMIT 1'
    );
    $modelStatement->execute([
        'model_name' => $modelInfo['name'],
        'model_version' => $modelInfo['version'],
    ]);
    $modelId = (int) $modelStatement->fetchColumn();
    if ($modelId < 1) {
        throw new RuntimeException(
            'No active database model matches '
            . $modelInfo['name'] . ' v' . $modelInfo['version'] . '.'
        );
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
            <h1>Predicted Drink Quality</h1>
            <div class="score-box"><span class="score-label">Predicted Quality Class</span><strong class="score-value"><?php echo e($score); ?></strong><span class="quality-badge"><?php echo e($label); ?></span></div>
            <p class="result-note">Prediction ID: <?php echo $predictionId; ?>. Generated by <?php echo e($modelInfo['name']); ?> v<?php echo e($modelInfo['version']); ?> (<?php echo e($modelInfo['algorithm']); ?>) and saved in MySQL.</p>
            <div class="result-actions"><a href="dashboard.php#prediction" class="primary-btn">Make Another Prediction</a><a href="history.php" class="secondary-btn">View Saved History</a></div>
        </div>
    </section>
</main>
<?php render_footer(); ?>
