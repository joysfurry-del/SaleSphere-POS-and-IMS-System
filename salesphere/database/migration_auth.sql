-- Add authentication columns to customer table
ALTER TABLE `customer`
  ADD COLUMN IF NOT EXISTS `Password` VARCHAR(255) DEFAULT NULL AFTER `Phone`,
  ADD COLUMN IF NOT EXISTS `ApiToken` VARCHAR(64) DEFAULT NULL AFTER `Password`,
  ADD INDEX IF NOT EXISTS `ApiToken` (`ApiToken`);
