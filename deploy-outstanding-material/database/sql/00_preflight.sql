-- Outstanding Material preflight (READ ONLY)
-- Run this file first. Continue only when both requirements show PASS.
-- This script does not create, alter, delete, or insert any database object/data.

SELECT
    DATABASE() AS active_database,
    CASE
        WHEN EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'users'
        ) THEN 'PASS'
        ELSE 'STOP: table users is required before installing Outstanding Material'
    END AS users_requirement,
    CASE
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'outstanding_materials'
        )
        AND NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'outstanding_material_invoices'
        ) THEN 'PASS'
        ELSE 'STOP: Outstanding Material tables already exist; do not run the fresh schema script'
    END AS outstanding_material_requirement;

SELECT
    table_name,
    table_type
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('users', 'outstanding_materials', 'outstanding_material_invoices')
ORDER BY table_name;