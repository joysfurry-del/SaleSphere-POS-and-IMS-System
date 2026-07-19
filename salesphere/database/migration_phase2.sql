-- ============================================================
-- Phase 2 Migration: Foreign Keys, Missing Columns, New Tables
-- ============================================================

-- 1. MISSING COLUMNS ON EXISTING TABLES
-- ============================================================

ALTER TABLE `user`
  ADD COLUMN `FullName` varchar(100) DEFAULT NULL AFTER `Username`,
  ADD COLUMN `IsActive` tinyint(1) NOT NULL DEFAULT 1 AFTER `Avatar`,
  ADD COLUMN `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `IsActive`,
  ADD COLUMN `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `CreatedAt`;

ALTER TABLE `product`
  ADD COLUMN `CostPrice` decimal(10,2) DEFAULT NULL AFTER `Price`,
  ADD COLUMN `Barcode` varchar(50) DEFAULT NULL AFTER `ProductSKU`,
  ADD COLUMN `IsActive` tinyint(1) NOT NULL DEFAULT 1 AFTER `IsEcommerce`,
  ADD INDEX `idx_product_barcode` (`Barcode`);

ALTER TABLE `inventory`
  ADD COLUMN `ReorderLevel` int(11) NOT NULL DEFAULT 5 AFTER `AvailableQty`,
  ADD COLUMN `MinStockLevel` int(11) NOT NULL DEFAULT 0 AFTER `ReorderLevel`;

ALTER TABLE `online_order`
  ADD COLUMN `AssignedTo` int(11) DEFAULT NULL AFTER `BranchID`,
  ADD COLUMN `AssignedAt` datetime DEFAULT NULL AFTER `AssignedTo`,
  ADD INDEX `idx_oo_assigned` (`AssignedTo`);

ALTER TABLE `stock_transfer`
  ADD COLUMN `SourceBranchID` int(11) DEFAULT NULL AFTER `BranchID`,
  ADD COLUMN `DestinationBranchID` int(11) DEFAULT NULL AFTER `SourceBranchID`,
  ADD COLUMN `RequestedBy` int(11) DEFAULT NULL AFTER `DestinationBranchID`,
  ADD COLUMN `ApprovedBy` int(11) DEFAULT NULL AFTER `RequestedBy`,
  ADD COLUMN `ApprovedAt` datetime DEFAULT NULL AFTER `ApprovedBy`,
  ADD COLUMN `RejectedBy` int(11) DEFAULT NULL AFTER `ApprovedAt`,
  ADD COLUMN `RejectedAt` datetime DEFAULT NULL AFTER `RejectedBy`,
  ADD COLUMN `CompletedAt` datetime DEFAULT NULL AFTER `RejectedAt`,
  ADD COLUMN `Notes` text DEFAULT NULL AFTER `CompletedAt`,
  MODIFY COLUMN `Status` enum('Pending','Approved','Rejected','Dispatched','Completed') NOT NULL DEFAULT 'Pending';

-- 2. NEW TABLES
-- ============================================================

-- Product Reservations (Sales Exec feature)
CREATE TABLE IF NOT EXISTS `product_reservation` (
  `ReservationID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `ReservedBy` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Status` enum('Active','Confirmed','Expired','Released','Cancelled') NOT NULL DEFAULT 'Active',
  `ExpiresAt` datetime DEFAULT NULL,
  `ConfirmedAt` datetime DEFAULT NULL,
  `ReleasedAt` datetime DEFAULT NULL,
  `CancelledAt` datetime DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReservationID`),
  KEY `idx_res_product` (`ProductID`),
  KEY `idx_res_branch` (`BranchID`),
  KEY `idx_res_customer` (`CustomerID`),
  KEY `idx_res_reserved_by` (`ReservedBy`),
  KEY `idx_res_status` (`Status`),
  KEY `idx_res_expires` (`ExpiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer Inquiries (Call Center feature)
CREATE TABLE IF NOT EXISTS `customer_inquiry` (
  `InquiryID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `Subject` varchar(200) NOT NULL,
  `Message` text NOT NULL,
  `Category` enum('Order','Product','Return','Refund','Shipping','Payment','Account','Other') NOT NULL DEFAULT 'Other',
  `Priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `Status` enum('Open','In Progress','Answered','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `AssignedTo` int(11) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`InquiryID`),
  KEY `idx_inq_customer` (`CustomerID`),
  KEY `idx_inq_assigned` (`AssignedTo`),
  KEY `idx_inq_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inquiry_response` (
  `ResponseID` int(11) NOT NULL AUTO_INCREMENT,
  `InquiryID` int(11) NOT NULL,
  `RespondedBy` int(11) DEFAULT NULL,
  `Message` text NOT NULL,
  `IsStaffResponse` tinyint(1) NOT NULL DEFAULT 1,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ResponseID`),
  KEY `idx_resp_inquiry` (`InquiryID`),
  KEY `idx_resp_responder` (`RespondedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. FOREIGN KEY CONSTRAINTS
-- ============================================================

-- user table
ALTER TABLE `user` ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`RoleID`) REFERENCES `role`(`RoleID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `user` ADD CONSTRAINT `fk_user_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- product table
ALTER TABLE `product` ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`CategoryID`) REFERENCES `category`(`CategoryID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- inventory table
ALTER TABLE `inventory` ADD CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `inventory` ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `inventory` ADD UNIQUE KEY `uq_branch_product` (`BranchID`, `ProductID`);

-- customer table
ALTER TABLE `customer` ADD CONSTRAINT `fk_customer_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- order (POS) table
ALTER TABLE `order` ADD CONSTRAINT `fk_order_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `order` ADD CONSTRAINT `fk_order_cashier` FOREIGN KEY (`CashierID`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `order` ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- order_item table
ALTER TABLE `order_item` ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`OrderID`) REFERENCES `order`(`OrderID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `order_item` ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- bill table
ALTER TABLE `bill` ADD CONSTRAINT `fk_bill_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `bill` ADD CONSTRAINT `fk_bill_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `bill` ADD CONSTRAINT `fk_bill_cashier` FOREIGN KEY (`CashierID`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- bill_item table
ALTER TABLE `bill_item` ADD CONSTRAINT `fk_bi_bill` FOREIGN KEY (`BillID`) REFERENCES `bill`(`BillID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `bill_item` ADD CONSTRAINT `fk_bi_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- bill_payment table
ALTER TABLE `bill_payment` ADD CONSTRAINT `fk_bp_bill` FOREIGN KEY (`BillID`) REFERENCES `bill`(`BillID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `bill_payment` ADD CONSTRAINT `fk_bp_received_by` FOREIGN KEY (`ReceivedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- cart table
ALTER TABLE `cart` ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `cart` ADD CONSTRAINT `fk_cart_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `cart` ADD CONSTRAINT `fk_cart_promotion` FOREIGN KEY (`PromotionID`) REFERENCES `promotion`(`PromotionID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- cart_item table
ALTER TABLE `cart_item` ADD CONSTRAINT `fk_ci_cart` FOREIGN KEY (`CartID`) REFERENCES `cart`(`CartID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `cart_item` ADD CONSTRAINT `fk_ci_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- online_order table
ALTER TABLE `online_order` ADD CONSTRAINT `fk_oo_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `online_order` ADD CONSTRAINT `fk_oo_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `online_order` ADD CONSTRAINT `fk_oo_promotion` FOREIGN KEY (`PromotionID`) REFERENCES `promotion`(`PromotionID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `online_order` ADD CONSTRAINT `fk_oo_assigned` FOREIGN KEY (`AssignedTo`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- online_order_item table
ALTER TABLE `online_order_item` ADD CONSTRAINT `fk_ooi_order` FOREIGN KEY (`OnlineOrderID`) REFERENCES `online_order`(`OnlineOrderID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `online_order_item` ADD CONSTRAINT `fk_ooi_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- promotion table
ALTER TABLE `promotion` ADD CONSTRAINT `fk_promo_creator` FOREIGN KEY (`CreatedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- promotion_usage table
ALTER TABLE `promotion_usage` ADD CONSTRAINT `fk_pu_promotion` FOREIGN KEY (`PromotionID`) REFERENCES `promotion`(`PromotionID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `promotion_usage` ADD CONSTRAINT `fk_pu_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `promotion_usage` ADD CONSTRAINT `fk_pu_order` FOREIGN KEY (`OrderID`) REFERENCES `order`(`OrderID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `promotion_usage` ADD CONSTRAINT `fk_pu_online_order` FOREIGN KEY (`OnlineOrderID`) REFERENCES `online_order`(`OnlineOrderID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `promotion_usage` ADD CONSTRAINT `fk_pu_cart` FOREIGN KEY (`CartID`) REFERENCES `cart`(`CartID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- product_return table
ALTER TABLE `product_return` ADD CONSTRAINT `fk_pr_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `product_return` ADD CONSTRAINT `fk_pr_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `product_return` ADD CONSTRAINT `fk_pr_processed_by` FOREIGN KEY (`ProcessedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- product_return_item table
ALTER TABLE `product_return_item` ADD CONSTRAINT `fk_pri_return` FOREIGN KEY (`ReturnID`) REFERENCES `product_return`(`ReturnID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `product_return_item` ADD CONSTRAINT `fk_pri_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `product_return_item` ADD CONSTRAINT `fk_pri_order_item` FOREIGN KEY (`OrderItemID`) REFERENCES `order_item`(`OrderItemID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `product_return_item` ADD CONSTRAINT `fk_pri_oo_item` FOREIGN KEY (`OnlineOrderItemID`) REFERENCES `online_order_item`(`OnlineOrderItemID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- stock_transfer table
ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_st_source_branch` FOREIGN KEY (`SourceBranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_st_dest_branch` FOREIGN KEY (`DestinationBranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_st_requested_by` FOREIGN KEY (`RequestedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `stock_transfer` ADD CONSTRAINT `fk_st_approved_by` FOREIGN KEY (`ApprovedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- feedback table
ALTER TABLE `feedback` ADD CONSTRAINT `fk_fb_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `feedback` ADD CONSTRAINT `fk_fb_user` FOREIGN KEY (`UserID`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- feedback_media table
ALTER TABLE `feedback_media` ADD CONSTRAINT `fk_fm_feedback` FOREIGN KEY (`FeedbackID`) REFERENCES `feedback`(`FeedbackID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- system_settings table
ALTER TABLE `system_settings` ADD CONSTRAINT `fk_ss_user` FOREIGN KEY (`UserID`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- transaction_log table
ALTER TABLE `transaction_log` ADD CONSTRAINT `fk_tl_user` FOREIGN KEY (`UserID`) REFERENCES `user`(`UserID`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- New table FKs
ALTER TABLE `product_reservation` ADD CONSTRAINT `fk_res_product` FOREIGN KEY (`ProductID`) REFERENCES `product`(`ProductID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `product_reservation` ADD CONSTRAINT `fk_res_branch` FOREIGN KEY (`BranchID`) REFERENCES `branch`(`BranchID`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `product_reservation` ADD CONSTRAINT `fk_res_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `product_reservation` ADD CONSTRAINT `fk_res_reserved_by` FOREIGN KEY (`ReservedBy`) REFERENCES `user`(`UserID`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `customer_inquiry` ADD CONSTRAINT `fk_inq_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `customer_inquiry` ADD CONSTRAINT `fk_inq_assigned` FOREIGN KEY (`AssignedTo`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `inquiry_response` ADD CONSTRAINT `fk_resp_inquiry` FOREIGN KEY (`InquiryID`) REFERENCES `customer_inquiry`(`InquiryID`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `inquiry_response` ADD CONSTRAINT `fk_resp_responder` FOREIGN KEY (`RespondedBy`) REFERENCES `user`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. ADDITIONAL INDEXES FOR PERFORMANCE
-- ============================================================
ALTER TABLE `stock_transfer` ADD INDEX `idx_st_status` (`Status`);
ALTER TABLE `stock_transfer` ADD INDEX `idx_st_source` (`SourceBranchID`);
ALTER TABLE `stock_transfer` ADD INDEX `idx_st_dest` (`DestinationBranchID`);
ALTER TABLE `online_order` ADD INDEX `idx_oo_status` (`Status`);
ALTER TABLE `online_order` ADD INDEX `idx_oo_payment_status` (`PaymentStatus`);
ALTER TABLE `order` ADD INDEX `idx_order_branch_date` (`BranchID`, `CreatedAt`);
ALTER TABLE `bill` ADD INDEX `idx_bill_branch_date` (`BranchID`, `CreatedAt`);
ALTER TABLE `product_return` ADD INDEX `idx_pr_status` (`Status`);
ALTER TABLE `customer` ADD INDEX `idx_customer_active` (`IsActive`);
