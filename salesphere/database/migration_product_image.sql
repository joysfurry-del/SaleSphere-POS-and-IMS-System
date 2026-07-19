-- Add ProductImage column for e-commerce
ALTER TABLE product ADD COLUMN IF NOT EXISTS `ProductImage` VARCHAR(255) DEFAULT NULL AFTER `Description`;
