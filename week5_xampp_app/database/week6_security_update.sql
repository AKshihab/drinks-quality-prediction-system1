-- Drinks Quality Prediction System - Week 6 additive security update
-- Run this file only when upgrading an existing Week 5 drinks_quality_db.
-- It preserves all users, samples, predictions, models, and relationships.

USE drinks_quality_db;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER role;
