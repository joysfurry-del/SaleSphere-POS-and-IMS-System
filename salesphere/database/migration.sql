-- Migration: Add Avatar column to User table
-- Run this after importing the schema dump

ALTER TABLE `user`
  ADD COLUMN `Avatar` VARCHAR(255) DEFAULT NULL AFTER `Tele`;

-- Optional: Seed a default admin account (password: admin123)
-- Password hash is bcrypt of 'admin123'
-- INSERT INTO `role` (`RoleID`, `RoleName`) VALUES
--   (1, 'Admin'),
--   (2, 'Accountant'),
--   (3, 'Cashier'),
--   (4, 'Branch Manager'),
--   (5, 'Sales Executive'),
--   (6, 'Digital Marketing'),
--   (7, 'Call Center');
-- 
-- INSERT INTO `branch` (`BranchID`, `BranchName`, `Location`) VALUES
--   (1, 'Main Branch', '123 Main Street');
-- 
-- INSERT INTO `user` (`UserID`, `RoleID`, `BranchID`, `Username`, `Password`, `Email`, `Tele`, `Avatar`) VALUES
--   (1, 1, 1, 'admin', '$2y$12$LJ3m4ys3Lg3YOCwKkCFR.OzBz0CqFqFqFqFqFqFqFqFqFqFqFq', 'admin@salesphere.com', '+60 12-3456789', NULL);
