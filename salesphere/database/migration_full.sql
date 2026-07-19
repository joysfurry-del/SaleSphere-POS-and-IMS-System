-- Migration: Complete schema for all 19 tables
-- Run this after importing the base schema

-- 1. customer table
CREATE TABLE IF NOT EXISTS `customer` (
  `CustomerID` INT NOT NULL AUTO_INCREMENT,
  `BranchID` INT NOT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `Phone` VARCHAR(20) DEFAULT NULL,
  `Address` TEXT DEFAULT NULL,
  `City` VARCHAR(50) DEFAULT NULL,
  `PostalCode` VARCHAR(10) DEFAULT NULL,
  `Country` VARCHAR(50) DEFAULT 'Malaysia',
  `CustomerType` ENUM('Walk-in','Regular','VIP','Corporate') DEFAULT 'Walk-in',
  `CreditLimit` DECIMAL(10,2) DEFAULT 0.00,
  `Balance` DECIMAL(10,2) DEFAULT 0.00,
  `IsActive` TINYINT(1) DEFAULT 1,
  `Notes` TEXT DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CustomerID`),
  KEY `BranchID` (`BranchID`),
  KEY `Email` (`Email`),
  KEY `Phone` (`Phone`),
  KEY `CustomerType` (`CustomerType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. cart table
CREATE TABLE IF NOT EXISTS `cart` (
  `CartID` INT NOT NULL AUTO_INCREMENT,
  `CustomerID` INT DEFAULT NULL,
  `SessionID` VARCHAR(128) DEFAULT NULL,
  `BranchID` INT NOT NULL,
  `Status` ENUM('Active','Abandoned','Converted','Expired') DEFAULT 'Active',
  `Subtotal` DECIMAL(10,2) DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) DEFAULT 0.00,
  `DiscountAmount` DECIMAL(10,2) DEFAULT 0.00,
  `Total` DECIMAL(10,2) DEFAULT 0.00,
  `PromotionID` INT DEFAULT NULL,
  `PromotionCode` VARCHAR(50) DEFAULT NULL,
  `Notes` TEXT DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `ExpiresAt` DATETIME DEFAULT NULL,
  PRIMARY KEY (`CartID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `SessionID` (`SessionID`),
  KEY `BranchID` (`BranchID`),
  KEY `Status` (`Status`),
  KEY `PromotionID` (`PromotionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. cart_item table
CREATE TABLE IF NOT EXISTS `cart_item` (
  `CartItemID` INT NOT NULL AUTO_INCREMENT,
  `CartID` INT NOT NULL,
  `ProductID` INT NOT NULL,
  `ProductName` VARCHAR(200) NOT NULL,
  `Price` DECIMAL(10,2) NOT NULL,
  `Quantity` INT NOT NULL DEFAULT 1,
  `LineTotal` DECIMAL(10,2) NOT NULL,
  `Notes` TEXT DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CartItemID`),
  KEY `CartID` (`CartID`),
  KEY `ProductID` (`ProductID`),
  UNIQUE KEY `unique_cart_product` (`CartID`, `ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. online_order table
CREATE TABLE IF NOT EXISTS `online_order` (
  `OnlineOrderID` INT NOT NULL AUTO_INCREMENT,
  `OrderNumber` VARCHAR(20) NOT NULL,
  `CustomerID` INT NOT NULL,
  `BranchID` INT NOT NULL,
  `Status` ENUM('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled','Returned') DEFAULT 'Pending',
  `PaymentStatus` ENUM('Pending','Paid','Partial','Refunded','Failed') DEFAULT 'Pending',
  `PaymentMethod` VARCHAR(20) DEFAULT 'Online',
  `Subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `TaxRate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `DiscountAmount` DECIMAL(10,2) DEFAULT 0.00,
  `ShippingAmount` DECIMAL(10,2) DEFAULT 0.00,
  `Total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `AmountPaid` DECIMAL(10,2) DEFAULT 0.00,
  `ChangeAmount` DECIMAL(10,2) DEFAULT 0.00,
  `PromotionID` INT DEFAULT NULL,
  `PromotionCode` VARCHAR(50) DEFAULT NULL,
  `ShippingName` VARCHAR(100) DEFAULT NULL,
  `ShippingPhone` VARCHAR(20) DEFAULT NULL,
  `ShippingAddress` TEXT DEFAULT NULL,
  `ShippingCity` VARCHAR(50) DEFAULT NULL,
  `ShippingPostalCode` VARCHAR(10) DEFAULT NULL,
  `ShippingCountry` VARCHAR(50) DEFAULT 'Malaysia',
  `BillingName` VARCHAR(100) DEFAULT NULL,
  `BillingPhone` VARCHAR(20) DEFAULT NULL,
  `BillingAddress` TEXT DEFAULT NULL,
  `BillingCity` VARCHAR(50) DEFAULT NULL,
  `BillingPostalCode` VARCHAR(10) DEFAULT NULL,
  `BillingCountry` VARCHAR(50) DEFAULT 'Malaysia',
  `Notes` TEXT DEFAULT NULL,
  `AdminNotes` TEXT DEFAULT NULL,
  `ConfirmedAt` DATETIME DEFAULT NULL,
  `ShippedAt` DATETIME DEFAULT NULL,
  `DeliveredAt` DATETIME DEFAULT NULL,
  `CancelledAt` DATETIME DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`OnlineOrderID`),
  UNIQUE KEY `OrderNumber` (`OrderNumber`),
  KEY `CustomerID` (`CustomerID`),
  KEY `BranchID` (`BranchID`),
  KEY `Status` (`Status`),
  KEY `PaymentStatus` (`PaymentStatus`),
  KEY `PromotionID` (`PromotionID`),
  KEY `CreatedAt` (`CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. online_order_item table
CREATE TABLE IF NOT EXISTS `online_order_item` (
  `OnlineOrderItemID` INT NOT NULL AUTO_INCREMENT,
  `OnlineOrderID` INT NOT NULL,
  `ProductID` INT NOT NULL,
  `ProductName` VARCHAR(200) NOT NULL,
  `ProductSKU` VARCHAR(50) DEFAULT NULL,
  `Price` DECIMAL(10,2) NOT NULL,
  `Quantity` INT NOT NULL DEFAULT 1,
  `LineTotal` DECIMAL(10,2) NOT NULL,
  `TaxRate` DECIMAL(5,2) DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) DEFAULT 0.00,
  `DiscountAmount` DECIMAL(10,2) DEFAULT 0.00,
  `Notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`OnlineOrderItemID`),
  KEY `OnlineOrderID` (`OnlineOrderID`),
  KEY `ProductID` (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. bill table (Invoices)
CREATE TABLE IF NOT EXISTS `bill` (
  `BillID` INT NOT NULL AUTO_INCREMENT,
  `BillNumber` VARCHAR(20) NOT NULL,
  `BillType` ENUM('Invoice','Credit Note','Debit Note','Proforma') DEFAULT 'Invoice',
  `ReferenceType` ENUM('Order','OnlineOrder','Manual') NOT NULL,
  `ReferenceID` INT NOT NULL,
  `CustomerID` INT DEFAULT NULL,
  `BranchID` INT NOT NULL,
  `CashierID` INT DEFAULT NULL,
  `Status` ENUM('Draft','Issued','Paid','Partial','Overdue','Cancelled','Refunded') DEFAULT 'Draft',
  `Subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `TaxRate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `DiscountAmount` DECIMAL(10,2) DEFAULT 0.00,
  `Total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `AmountPaid` DECIMAL(10,2) DEFAULT 0.00,
  `Balance` DECIMAL(10,2) DEFAULT 0.00,
  `PaymentMethod` VARCHAR(20) DEFAULT NULL,
  `PaymentReference` VARCHAR(100) DEFAULT NULL,
  `DueDate` DATE DEFAULT NULL,
  `IssuedAt` DATETIME DEFAULT NULL,
  `PaidAt` DATETIME DEFAULT NULL,
  `Notes` TEXT DEFAULT NULL,
  `TermsConditions` TEXT DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`BillID`),
  UNIQUE KEY `BillNumber` (`BillNumber`),
  KEY `CustomerID` (`CustomerID`),
  KEY `BranchID` (`BranchID`),
  KEY `CashierID` (`CashierID`),
  KEY `Status` (`Status`),
  KEY `ReferenceType_ReferenceID` (`ReferenceType`, `ReferenceID`),
  KEY `DueDate` (`DueDate`),
  KEY `IssuedAt` (`IssuedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. bill_item table
CREATE TABLE IF NOT EXISTS `bill_item` (
  `BillItemID` INT NOT NULL AUTO_INCREMENT,
  `BillID` INT NOT NULL,
  `ProductID` INT DEFAULT NULL,
  `Description` VARCHAR(200) NOT NULL,
  `Quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `UnitPrice` DECIMAL(10,2) NOT NULL,
  `TaxRate` DECIMAL(5,2) DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) DEFAULT 0.00,
  `DiscountPercent` DECIMAL(5,2) DEFAULT 0.00,
  `DiscountAmount` DECIMAL(10,2) DEFAULT 0.00,
  `LineTotal` DECIMAL(10,2) NOT NULL,
  `SortOrder` INT DEFAULT 0,
  PRIMARY KEY (`BillItemID`),
  KEY `BillID` (`BillID`),
  KEY `ProductID` (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. bill_payment table
CREATE TABLE IF NOT EXISTS `bill_payment` (
  `BillPaymentID` INT NOT NULL AUTO_INCREMENT,
  `BillID` INT NOT NULL,
  `PaymentMethod` VARCHAR(20) NOT NULL,
  `Amount` DECIMAL(10,2) NOT NULL,
  `Reference` VARCHAR(100) DEFAULT NULL,
  `Notes` TEXT DEFAULT NULL,
  `ReceivedBy` INT DEFAULT NULL,
  `ReceivedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`BillPaymentID`),
  KEY `BillID` (`BillID`),
  KEY `ReceivedBy` (`ReceivedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. promotion table
CREATE TABLE IF NOT EXISTS `promotion` (
  `PromotionID` INT NOT NULL AUTO_INCREMENT,
  `Code` VARCHAR(50) NOT NULL,
  `Name` VARCHAR(100) NOT NULL,
  `Description` TEXT DEFAULT NULL,
  `Type` ENUM('Percentage','FixedAmount','BuyXGetY','FreeShipping','Bundle') NOT NULL,
  `Scope` ENUM('Product','Category','Order','Customer','Cart') NOT NULL,
  `Value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `MinOrderAmount` DECIMAL(10,2) DEFAULT 0.00,
  `MaxDiscountAmount` DECIMAL(10,2) DEFAULT NULL,
  `BuyQuantity` INT DEFAULT NULL,
  `GetQuantity` INT DEFAULT NULL,
  `GetDiscountPercent` DECIMAL(5,2) DEFAULT NULL,
  `ApplicableProductIDs` JSON DEFAULT NULL,
  `ApplicableCategoryIDs` JSON DEFAULT NULL,
  `ApplicableCustomerTypes` JSON DEFAULT NULL,
  `StartDate` DATETIME NOT NULL,
  `EndDate` DATETIME NOT NULL,
  `UsageLimit` INT DEFAULT NULL,
  `UsageCount` INT DEFAULT 0,
  `PerCustomerLimit` INT DEFAULT 1,
  `IsActive` TINYINT(1) DEFAULT 1,
  `IsPublic` TINYINT(1) DEFAULT 1,
  `Priority` INT DEFAULT 0,
  `CreatedBy` INT DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PromotionID`),
  UNIQUE KEY `Code` (`Code`),
  KEY `Type` (`Type`),
  KEY `Scope` (`Scope`),
  KEY `IsActive` (`IsActive`),
  KEY `StartDate_EndDate` (`StartDate`, `EndDate`),
  KEY `CreatedBy` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. promotion_usage table
CREATE TABLE IF NOT EXISTS `promotion_usage` (
  `PromotionUsageID` INT NOT NULL AUTO_INCREMENT,
  `PromotionID` INT NOT NULL,
  `CustomerID` INT DEFAULT NULL,
  `OrderID` INT DEFAULT NULL,
  `OnlineOrderID` INT DEFAULT NULL,
  `CartID` INT DEFAULT NULL,
  `DiscountAmount` DECIMAL(10,2) NOT NULL,
  `UsedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`PromotionUsageID`),
  KEY `PromotionID` (`PromotionID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `OrderID` (`OrderID`),
  KEY `OnlineOrderID` (`OnlineOrderID`),
  KEY `CartID` (`CartID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. product_return table
CREATE TABLE IF NOT EXISTS `product_return` (
  `ReturnID` INT NOT NULL AUTO_INCREMENT,
  `ReturnNumber` VARCHAR(20) NOT NULL,
  `ReferenceType` ENUM('Order','OnlineOrder') NOT NULL,
  `ReferenceID` INT NOT NULL,
  `CustomerID` INT DEFAULT NULL,
  `BranchID` INT NOT NULL,
  `ProcessedBy` INT DEFAULT NULL,
  `Status` ENUM('Requested','Approved','Received','Processed','Refunded','Rejected','Cancelled') DEFAULT 'Requested',
  `Reason` ENUM('Defective','Wrong Item','Not as Described','Changed Mind','Damaged','Other') NOT NULL,
  `ReasonDetails` TEXT DEFAULT NULL,
  `RefundMethod` ENUM('Original Payment','Store Credit','Cash','Exchange') DEFAULT 'Original Payment',
  `Subtotal` DECIMAL(10,2) DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) DEFAULT 0.00,
  `RestockFee` DECIMAL(10,2) DEFAULT 0.00,
  `ShippingFee` DECIMAL(10,2) DEFAULT 0.00,
  `TotalRefund` DECIMAL(10,2) DEFAULT 0.00,
  `Notes` TEXT DEFAULT NULL,
  `AdminNotes` TEXT DEFAULT NULL,
  `RequestedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ApprovedAt` DATETIME DEFAULT NULL,
  `ReceivedAt` DATETIME DEFAULT NULL,
  `ProcessedAt` DATETIME DEFAULT NULL,
  `RefundedAt` DATETIME DEFAULT NULL,
  PRIMARY KEY (`ReturnID`),
  UNIQUE KEY `ReturnNumber` (`ReturnNumber`),
  KEY `ReferenceType_ReferenceID` (`ReferenceType`, `ReferenceID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `BranchID` (`BranchID`),
  KEY `ProcessedBy` (`ProcessedBy`),
  KEY `Status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. product_return_item table
CREATE TABLE IF NOT EXISTS `product_return_item` (
  `ReturnItemID` INT NOT NULL AUTO_INCREMENT,
  `ReturnID` INT NOT NULL,
  `OrderItemID` INT DEFAULT NULL,
  `OnlineOrderItemID` INT DEFAULT NULL,
  `ProductID` INT NOT NULL,
  `ProductName` VARCHAR(200) NOT NULL,
  `Quantity` INT NOT NULL DEFAULT 1,
  `UnitPrice` DECIMAL(10,2) NOT NULL,
  `TaxRate` DECIMAL(5,2) DEFAULT 0.00,
  `TaxAmount` DECIMAL(10,2) DEFAULT 0.00,
  `LineTotal` DECIMAL(10,2) NOT NULL,
  `RestockQuantity` INT DEFAULT 0,
  `Condition` ENUM('New','Open Box','Used','Damaged','Missing Parts') DEFAULT 'New',
  `RestockLocation` VARCHAR(100) DEFAULT NULL,
  `Notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`ReturnItemID`),
  KEY `ReturnID` (`ReturnID`),
  KEY `OrderItemID` (`OrderItemID`),
  KEY `OnlineOrderItemID` (`OnlineOrderItemID`),
  KEY `ProductID` (`ProductID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. feedback table
CREATE TABLE IF NOT EXISTS `feedback` (
  `FeedbackID` INT NOT NULL AUTO_INCREMENT,
  `ReferenceType` ENUM('Product','Branch','Order','OnlineOrder') NOT NULL,
  `ReferenceID` INT NOT NULL,
  `CustomerID` INT DEFAULT NULL,
  `UserID` INT DEFAULT NULL,
  `Rating` TINYINT NOT NULL CHECK (`Rating` BETWEEN 1 AND 5),
  `Title` VARCHAR(200) DEFAULT NULL,
  `Comment` TEXT DEFAULT NULL,
  `Pros` TEXT DEFAULT NULL,
  `Cons` TEXT DEFAULT NULL,
  `IsVerifiedPurchase` TINYINT(1) DEFAULT 0,
  `IsApproved` TINYINT(1) DEFAULT 0,
  `IsFeatured` TINYINT(1) DEFAULT 0,
  `AdminResponse` TEXT DEFAULT NULL,
  `RespondedBy` INT DEFAULT NULL,
  `RespondedAt` DATETIME DEFAULT NULL,
  `HelpfulCount` INT DEFAULT 0,
  `ReportedCount` INT DEFAULT 0,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`FeedbackID`),
  KEY `ReferenceType_ReferenceID` (`ReferenceType`, `ReferenceID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `UserID` (`UserID`),
  KEY `Rating` (`Rating`),
  KEY `IsApproved` (`IsApproved`),
  KEY `IsFeatured` (`IsFeatured`),
  KEY `CreatedAt` (`CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. feedback_media table
CREATE TABLE IF NOT EXISTS `feedback_media` (
  `FeedbackMediaID` INT NOT NULL AUTO_INCREMENT,
  `FeedbackID` INT NOT NULL,
  `MediaType` ENUM('Image','Video') NOT NULL,
  `FilePath` VARCHAR(255) NOT NULL,
  `FileName` VARCHAR(255) DEFAULT NULL,
  `FileSize` INT DEFAULT NULL,
  `MimeType` VARCHAR(100) DEFAULT NULL,
  `SortOrder` INT DEFAULT 0,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FeedbackMediaID`),
  KEY `FeedbackID` (`FeedbackID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns to existing tables
-- product.IsEcommerce
ALTER TABLE `product` ADD COLUMN IF NOT EXISTS `IsEcommerce` TINYINT(1) NOT NULL DEFAULT 0 AFTER `Price`;

-- inventory.LastUpdated
ALTER TABLE `inventory` ADD COLUMN IF NOT EXISTS `LastUpdated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `AvailableQty`;

-- stock_transfer.Status (ensure ENUM)
-- ALTER TABLE `stock_transfer` MODIFY COLUMN `Status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending';

-- Add foreign keys (optional, for referential integrity)
-- ALTER TABLE `user` ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`RoleID`) REFERENCES `role`(`RoleID`);
-- ALTER TABLE `user` ADD CONSTRAINT `fk_user_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`);
-- ALTER TABLE `product` ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`CategoryID`) REFERENCES `category`(`CategoryID`);
-- ALTER TABLE `inventory` ADD CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`);
-- ALTER TABLE `inventory` ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`);
-- ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_stock_transfer_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`);
-- ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_stock_transfer_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`);
-- ALTER TABLE `order` ADD CONSTRAINT `fk_order_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`);
-- ALTER TABLE `order` ADD CONSTRAINT `fk_order_cashier` FOREIGN KEY (`CashierID`) REFERENCES `user`(`UserID`);
-- ALTER TABLE `order_item` ADD CONSTRAINT `fk_order_item_order` FOREIGN KEY (`OrderID`) REFERENCES `order`(`OrderID`);
-- ALTER TABLE `order_item` ADD CONSTRAINT `fk_order_item_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`);
-- ALTER TABLE `system_settings` ADD CONSTRAINT `fk_system_settings_user` FOREIGN KEY (`UserID`) REFERENCES `user`(`UserID`);
-- ALTER TABLE `transaction_log` ADD CONSTRAINT `fk_transaction_log_user` FOREIGN KEY (`UserID`) REFERENCES `user`(`UserID`);