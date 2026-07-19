-- Migration: Add order and order_item tables for POS
-- Run this after importing the schema dump

CREATE TABLE IF NOT EXISTS `order` (
  `OrderID` INT NOT NULL AUTO_INCREMENT,
  `BranchID` INT NOT NULL,
  `CashierID` INT NOT NULL,
  `CustomerName` VARCHAR(100) DEFAULT NULL,
  `Subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `TaxRate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `PaymentMethod` VARCHAR(20) NOT NULL DEFAULT 'Cash',
  `AmountPaid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Change` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`OrderID`),
  KEY `BranchID` (`BranchID`),
  KEY `CashierID` (`CashierID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_item` (
  `OrderItemID` INT NOT NULL AUTO_INCREMENT,
  `OrderID` INT NOT NULL,
  `ProductID` INT NOT NULL,
  `ProductName` VARCHAR(200) NOT NULL,
  `Price` DECIMAL(10,2) NOT NULL,
  `Quantity` INT NOT NULL DEFAULT 1,
  `LineTotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`OrderItemID`),
  KEY `OrderID` (`OrderID`),
  KEY `ProductID` (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
