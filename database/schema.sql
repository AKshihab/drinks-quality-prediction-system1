-- Drinks Quality Prediction System - Week 5 schema with the Week 6 profile field
-- Re-runnable MySQL/MariaDB script for phpMyAdmin and XAMPP.

CREATE DATABASE IF NOT EXISTS drinks_quality_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE drinks_quality_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS predictions;
DROP TABLE IF EXISTS drink_samples;
DROP TABLE IF EXISTS models;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'User') NOT NULL DEFAULT 'User',
    bio TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE models (
    model_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(100) NOT NULL,
    algorithm VARCHAR(100) NOT NULL,
    model_version VARCHAR(30) NOT NULL,
    accuracy DECIMAL(5,2) NULL DEFAULT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_models_name_version UNIQUE (model_name, model_version)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE drink_samples (
    sample_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    fixed_acidity DECIMAL(6,3) NOT NULL,
    volatile_acidity DECIMAL(6,3) NOT NULL,
    citric_acid DECIMAL(6,3) NOT NULL,
    residual_sugar DECIMAL(7,3) NOT NULL,
    chlorides DECIMAL(7,4) NOT NULL,
    free_sulfur_dioxide DECIMAL(7,2) NOT NULL,
    total_sulfur_dioxide DECIMAL(7,2) NOT NULL,
    density DECIMAL(7,5) NOT NULL,
    ph DECIMAL(4,2) NOT NULL,
    sulphates DECIMAL(5,3) NOT NULL,
    alcohol DECIMAL(5,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_drink_samples_user_id (user_id),
    CONSTRAINT fk_drink_samples_user
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE predictions (
    prediction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sample_id INT UNSIGNED NOT NULL,
    model_id INT UNSIGNED NOT NULL,
    predicted_quality DECIMAL(5,2) NOT NULL,
    quality_label VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_predictions_sample_id (sample_id),
    INDEX idx_predictions_model_id (model_id),
    INDEX idx_predictions_created_at (created_at),
    CONSTRAINT fk_predictions_sample
        FOREIGN KEY (sample_id)
        REFERENCES drink_samples (sample_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_predictions_model
        FOREIGN KEY (model_id)
        REFERENCES models (model_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Passwords are PHP PASSWORD_DEFAULT hashes, never plain-text values.
-- Admin demonstration password: secureStudent123
INSERT INTO users (user_id, full_name, email, password_hash, role) VALUES
    (1, 'System Administrator', 'student@university.edu', '$2y$10$ThEX/fn6ETAPJq99naRXv.cjNa41Ast3XmxqGne.0RD7LPKCt5Bl2', 'Admin'),
    (2, 'Nadia Rahman', 'nadia.rahman@example.com', '$2y$10$XMbjNf/so4/gcu.1qtqsOuRktp2Bcm6Tk4SChRMukcscQq.Gs/w0K', 'User'),
    (3, 'Farhan Ahmed', 'farhan.ahmed@example.com', '$2y$10$ovDCC/94KJy0AemGqiQh/.ziYF61sbxbRgR5uYXHpHH5c9.LDMktS', 'User');

-- Prototype rows remain for the seeded relational examples. Only the trained
-- classifier is active for new application predictions.
INSERT INTO models (
    model_id,
    model_name,
    algorithm,
    model_version,
    accuracy,
    is_active
) VALUES
    (1, 'ElasticNet Prototype', 'ElasticNet Regression', '1.0', NULL, FALSE),
    (2, 'Random Forest Prototype', 'Random Forest Regression', '1.0', NULL, FALSE),
    (3, 'Drinks Quality Classifier', 'LogisticRegressionCV', '1.0', 60.84, TRUE);

INSERT INTO drink_samples (
    sample_id,
    user_id,
    fixed_acidity,
    volatile_acidity,
    citric_acid,
    residual_sugar,
    chlorides,
    free_sulfur_dioxide,
    total_sulfur_dioxide,
    density,
    ph,
    sulphates,
    alcohol,
    created_at
) VALUES
    (1, 1, 7.400, 0.700, 0.000, 1.900, 0.0760, 11.00, 34.00, 0.99780, 3.51, 0.560, 9.40, '2026-08-01 09:00:00'),
    (2, 2, 7.800, 0.580, 0.020, 2.000, 0.0730, 9.00, 18.00, 0.99680, 3.36, 0.570, 9.50, '2026-08-01 10:00:00'),
    (3, 3, 11.200, 0.280, 0.560, 1.900, 0.0750, 17.00, 60.00, 0.99800, 3.16, 0.580, 9.80, '2026-08-01 11:00:00'),
    (4, 1, 6.700, 0.760, 0.020, 1.800, 0.0780, 6.00, 12.00, 0.99600, 3.55, 0.630, 9.95, '2026-08-01 12:00:00');

-- These predictions are demonstration seed records for relational database testing.
-- They are not claimed to be outputs produced by a trained model in this repository.
INSERT INTO predictions (
    prediction_id,
    sample_id,
    model_id,
    predicted_quality,
    quality_label,
    created_at
) VALUES
    (1, 1, 1, 5.00, 'Average', '2026-08-01 09:01:00'),
    (2, 2, 2, 6.00, 'Good', '2026-08-01 10:01:00'),
    (3, 3, 1, 4.00, 'Poor', '2026-08-01 11:01:00'),
    (4, 4, 2, 7.00, 'Excellent', '2026-08-01 12:01:00');
