-- Week 5 unified XAMPP database, extended with the Week 6 profile field
-- Import this complete file in phpMyAdmin.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE models (
    model_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(100) NOT NULL,
    algorithm VARCHAR(100) NOT NULL,
    model_version VARCHAR(30) NOT NULL,
    accuracy DECIMAL(5,2) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_models_name_version UNIQUE (model_name, model_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE drink_samples (
    sample_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    fixed_acidity DECIMAL(7,3) NOT NULL,
    volatile_acidity DECIMAL(7,3) NOT NULL,
    citric_acid DECIMAL(7,3) NOT NULL,
    residual_sugar DECIMAL(8,3) NOT NULL,
    chlorides DECIMAL(8,4) NOT NULL,
    free_sulfur_dioxide DECIMAL(8,2) NOT NULL,
    total_sulfur_dioxide DECIMAL(8,2) NOT NULL,
    density DECIMAL(8,5) NOT NULL,
    ph DECIMAL(5,2) NOT NULL,
    sulphates DECIMAL(7,3) NOT NULL,
    alcohol DECIMAL(6,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_samples_user (user_id),
    CONSTRAINT fk_samples_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE predictions (
    prediction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sample_id INT UNSIGNED NOT NULL,
    model_id INT UNSIGNED NOT NULL,
    predicted_quality DECIMAL(5,2) NOT NULL,
    quality_label VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_predictions_sample (sample_id),
    INDEX idx_predictions_model (model_id),
    INDEX idx_predictions_date (created_at),
    CONSTRAINT fk_predictions_sample FOREIGN KEY (sample_id) REFERENCES drink_samples(sample_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_predictions_model FOREIGN KEY (model_id) REFERENCES models(model_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test login: demo@gmail.com / DemoUser123!
-- Only the secure password hash is stored below.
INSERT INTO users (full_name, email, password_hash, role) VALUES
('Demo Administrator', 'demo@gmail.com', '$2y$12$7ecaDRsTbhArtLfrssJErO52hB7ZZ8H22eE96CD4B3jmy6DjWi9HK', 'Admin');

INSERT INTO models (model_name, algorithm, model_version, accuracy, is_active) VALUES
('Week 5 Prototype Model', 'Demonstration Formula', '1.0', NULL, TRUE);
