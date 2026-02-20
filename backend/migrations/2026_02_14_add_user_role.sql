-- Add role support for users (admin/user)
ALTER TABLE users
    ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER password;

-- Index for role-based filtering
CREATE INDEX idx_role ON users(role);

-- Promote default seeded account to admin role
UPDATE users
SET role = 'admin'
WHERE phone_number = '+254700000000';
