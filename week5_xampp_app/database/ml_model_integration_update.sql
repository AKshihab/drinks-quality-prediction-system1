-- Non-destructive trained-model registration for an existing Week 5/6 database.
-- Existing users, samples, predictions, and prototype model rows are preserved.

USE drinks_quality_db;

START TRANSACTION;

UPDATE models
SET is_active = FALSE
WHERE is_active = TRUE;

INSERT INTO models (
    model_name,
    algorithm,
    model_version,
    accuracy,
    is_active
) VALUES (
    'Drinks Quality Classifier',
    'LogisticRegressionCV',
    '1.0',
    60.84,
    TRUE
)
ON DUPLICATE KEY UPDATE
    algorithm = VALUES(algorithm),
    accuracy = VALUES(accuracy),
    is_active = VALUES(is_active);

COMMIT;
