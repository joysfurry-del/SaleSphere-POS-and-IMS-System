<?php
namespace App\Models;

use PDO;

class CartManager {
    // find active cart by session
    public static function getBySession(string $sessionId, int $branchId): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.* FROM cart c
             WHERE c.SessionID = ? AND c.BranchID = ? AND c.Status = 'Active'
             ORDER BY c.CreatedAt DESC LIMIT 1"
        );
        $stmt->execute([$sessionId, $branchId]);
        return $stmt->fetch() ?: null;
    }

    // find active cart by customer
    public static function getByCustomer(int $customerId, int $branchId): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.* FROM cart c
             WHERE c.CustomerID = ? AND c.BranchID = ? AND c.Status = 'Active'
             ORDER BY c.CreatedAt DESC LIMIT 1"
        );
        $stmt->execute([$customerId, $branchId]);
        return $stmt->fetch() ?: null;
    }

    // get cart by id
    public static function getById(int $cartId): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM cart WHERE CartID = ?");
        $stmt->execute([$cartId]);
        return $stmt->fetch() ?: null;
    }

    // create new cart
    public static function create(array $data): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO cart (CustomerID, SessionID, BranchID, Status, PromotionID, PromotionCode, Notes)
             VALUES (?, ?, ?, 'Active', ?, ?, ?)"
        );
        $stmt->execute([
            $data['CustomerID'] ?? null,
            $data['SessionID'] ?? null,
            $data['BranchID'],
            $data['PromotionID'] ?? null,
            $data['PromotionCode'] ?? null,
            $data['Notes'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    // update cart fields
    public static function update(int $cartId, array $data): bool {
        $db = \Database::getConnection();
        $fields = [];
        $values = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $cartId;
        $sql = "UPDATE cart SET " . implode(', ', $fields) . " WHERE CartID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    // recalculate subtotal, tax and total for cart
    public static function recalculateTotals(int $cartId): void {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(LineTotal), 0) as subtotal FROM cart_item WHERE CartID = ?"
        );
        $stmt->execute([$cartId]);
        $row = $stmt->fetch();
        $subtotal = (float)$row['subtotal'];

        $cart = self::getById($cartId);
        $taxRate = 0;
        if ($cart) {
            $taxRate = \App\Models\OrderManager::getTaxRate();
        }
        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $taxAmount, 2);

        $db->prepare("UPDATE cart SET Subtotal = ?, TaxAmount = ?, Total = ? WHERE CartID = ?")
           ->execute([$subtotal, $taxAmount, $total, $cartId]);
    }

    // get items in cart
    public static function getItems(int $cartId): array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM cart_item WHERE CartID = ? ORDER BY CreatedAt");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    // add item to cart or increase qty if already exist
    public static function addItem(int $cartId, array $item): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO cart_item (CartID, ProductID, ProductName, Price, Quantity, LineTotal, Notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity), LineTotal = LineTotal + VALUES(LineTotal)"
        );
        $stmt->execute([
            $cartId,
            $item['ProductID'],
            $item['ProductName'],
            $item['Price'],
            $item['Quantity'],
            $item['LineTotal'],
            $item['Notes'] ?? null,
        ]);
        self::recalculateTotals($cartId);
        return (int)$db->lastInsertId();
    }

    // update item qty or remove if zero
    public static function updateItemQuantity(int $cartItemId, int $quantity): bool {
        $db = \Database::getConnection();
        if ($quantity <= 0) {
            $stmt = $db->prepare("DELETE FROM cart_item WHERE CartItemID = ?");
            $stmt->execute([$cartItemId]);
        } else {
            $stmt = $db->prepare("SELECT CartID, Price FROM cart_item WHERE CartItemID = ?");
            $stmt->execute([$cartItemId]);
            $item = $stmt->fetch();
            if (!$item) return false;
            $lineTotal = round($item['Price'] * $quantity, 2);
            $stmt = $db->prepare("UPDATE cart_item SET Quantity = ?, LineTotal = ? WHERE CartItemID = ?");
            $stmt->execute([$quantity, $lineTotal, $cartItemId]);
        }
        $cartItem = self::getCartItem($cartItemId);
        if ($cartItem) self::recalculateTotals($cartItem['CartID']);
        return true;
    }

    // get single cart item
    public static function getCartItem(int $cartItemId): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM cart_item WHERE CartItemID = ?");
        $stmt->execute([$cartItemId]);
        return $stmt->fetch() ?: null;
    }

    // remove item from cart and recalculate
    public static function removeItem(int $cartItemId): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT CartID FROM cart_item WHERE CartItemID = ?");
        $stmt->execute([$cartItemId]);
        $item = $stmt->fetch();
        if (!$item) return false;
        $cartId = $item['CartID'];
        $stmt = $db->prepare("DELETE FROM cart_item WHERE CartItemID = ?");
        $stmt->execute([$cartItemId]);
        self::recalculateTotals($cartId);
        return true;
    }

    // clear all items from cart
    public static function clear(int $cartId): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM cart_item WHERE CartID = ?");
        $stmt->execute([$cartId]);
        $stmt = $db->prepare("UPDATE cart SET Subtotal = 0, TaxAmount = 0, Total = 0 WHERE CartID = ?");
        $stmt->execute([$cartId]);
        return true;
    }

    // convert cart into an order
    public static function convertToOrder(int $cartId, array $orderData): int {
        $db = \Database::getConnection();
        $items = self::getItems($cartId);
        if (empty($items)) throw new \RuntimeException('Cart is empty');

        $db->beginTransaction();
        try {
            $orderId = \App\Models\OrderManager::createOrder($orderData, $items);

            $stmt = $db->prepare("UPDATE cart SET Status = 'Converted' WHERE CartID = ?");
            $stmt->execute([$cartId]);

            $db->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // apply promo code to cart
    public static function applyPromotion(int $cartId, int $promotionId, string $code): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE cart SET PromotionID = ?, PromotionCode = ? WHERE CartID = ?");
        $result = $stmt->execute([$promotionId, $code, $cartId]);
        self::recalculateTotals($cartId);
        return $result;
    }

    // remove promo from cart
    public static function removePromotion(int $cartId): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("UPDATE cart SET PromotionID = NULL, PromotionCode = NULL WHERE CartID = ?");
        $result = $stmt->execute([$cartId]);
        self::recalculateTotals($cartId);
        return $result;
    }

    // get existing cart or create new one
    public static function getOrCreateCart(int $branchId, ?int $customerId = null): array {
        $sessionId = session_id();
        if (empty($sessionId)) session_start();
        $sessionId = session_id();

        $cart = null;
        if ($customerId) {
            $cart = self::getByCustomer($customerId, $branchId);
        }
        if (!$cart) {
            $cart = self::getBySession($sessionId, $branchId);
        }
        if (!$cart) {
            $cartId = self::create([
                'CustomerID' => $customerId,
                'SessionID' => $sessionId,
                'BranchID' => $branchId,
            ]);
            $cart = self::getById($cartId);
        }
        return $cart;
    }
}