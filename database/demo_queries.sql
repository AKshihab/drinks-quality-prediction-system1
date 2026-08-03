-- Drinks Quality Prediction System - Week 5 CRUD and JOIN demonstrations

USE drinks_quality_db;

-- 1. CREATE / INSERT
-- This temporary model exists only to demonstrate CREATE and DELETE safely.
INSERT INTO models (
    model_name,
    algorithm,
    model_version,
    accuracy,
    is_active
) VALUES (
    'CRUD Demo Model',
    'Temporary Demonstration Algorithm',
    '0.0.0',
    NULL,
    FALSE
)
ON DUPLICATE KEY UPDATE
    algorithm = VALUES(algorithm),
    accuracy = VALUES(accuracy),
    is_active = VALUES(is_active);

-- 2. READ / SELECT
SELECT
    model_id,
    model_name,
    algorithm,
    model_version,
    accuracy,
    is_active
FROM models
ORDER BY model_id;

-- 3. UPDATE
UPDATE models
SET
    model_name = 'CRUD Demo Model Updated',
    is_active = FALSE
WHERE model_name = 'CRUD Demo Model'
  AND model_version = '0.0.0';

-- 4. DELETE
-- Only the specifically inserted temporary row is deleted. Seed rows are untouched.
DELETE FROM models
WHERE model_name = 'CRUD Demo Model Updated'
  AND model_version = '0.0.0'
  AND NOT EXISTS (
      SELECT 1
      FROM predictions
      WHERE predictions.model_id = models.model_id
  );

-- 5. INNER JOIN
-- Main four-table query for the Week 5 live demonstration.
SELECT
    p.prediction_id,
    u.full_name,
    u.email,
    ds.sample_id,
    ds.alcohol,
    ds.ph,
    p.predicted_quality,
    p.quality_label,
    m.model_name,
    m.algorithm,
    p.created_at AS prediction_date
FROM predictions AS p
INNER JOIN drink_samples AS ds
    ON p.sample_id = ds.sample_id
INNER JOIN users AS u
    ON ds.user_id = u.user_id
INNER JOIN models AS m
    ON p.model_id = m.model_id
ORDER BY p.created_at DESC;

-- 6. LEFT JOIN
-- Shows every user, including users who have no saved samples or predictions.
SELECT
    u.user_id,
    u.full_name,
    ds.sample_id,
    p.prediction_id,
    p.quality_label
FROM users AS u
LEFT JOIN drink_samples AS ds
    ON u.user_id = ds.user_id
LEFT JOIN predictions AS p
    ON ds.sample_id = p.sample_id
ORDER BY u.user_id, ds.sample_id, p.prediction_id;

-- 7. AGGREGATE QUERY
-- Counts prediction records per user while retaining users with zero predictions.
SELECT
    u.user_id,
    u.full_name,
    COUNT(p.prediction_id) AS prediction_count
FROM users AS u
LEFT JOIN drink_samples AS ds
    ON u.user_id = ds.user_id
LEFT JOIN predictions AS p
    ON ds.sample_id = p.sample_id
GROUP BY u.user_id, u.full_name
ORDER BY prediction_count DESC, u.full_name ASC;
