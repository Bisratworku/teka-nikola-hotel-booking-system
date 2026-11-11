-- Add user column to audit_logs table
ALTER TABLE audit_logs ADD COLUMN user VARCHAR(255) DEFAULT NULL AFTER user_id;

-- Update existing records to populate the user column based on user_id
UPDATE audit_logs a 
LEFT JOIN users u ON a.user_id = u.id 
SET a.user = u.name 
WHERE a.user_id IS NOT NULL;
