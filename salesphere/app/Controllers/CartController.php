<?php
namespace App\Controllers;

use App\Models\CartManager;
use App\Models\ProductManager;

class CartController {
    public static function getCart(): void {
        header('Content-Type: application/json');
        $branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

        if ($branchId === 0) {
            echo json_encode(['success' => false, 'message' => 'Branch not set.']);
            exit;
        }

        // get or create cart for this branch
        $cart = CartManager::getOrCreateCart($branchId, $customerId);
        $items = CartManager::getItems($cart['CartID']);

        echo json_encode([
            'success' => true,
            'cart' => $cart,
            'items' => $items,
        ]);
        exit;
    }

    public static function addItem(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($branchId === 0 || $productId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // get product details
        $product = ProductManager::getById($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // Check inventory
        $inventory = \App\Models\InventoryManager::getByBranchAndProduct($branchId, $productId);
        $availableQty = $inventory ? (int)$inventory['AvailableQty'] : 0;
        if ($availableQty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Out of stock.']);
            exit;
        }

        // get or create cart and check existing qty
        $cart = CartManager::getOrCreateCart($branchId, $customerId);
        $existingItems = CartManager::getItems($cart['CartID']);
        $inCart = 0;
        foreach ($existingItems as $item) {
            if ($item['ProductID'] == $productId) $inCart += (int)$item['Quantity'];
        }
        // check if total qty exceeds stock
        if ($inCart + $quantity > $availableQty) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
            exit;
        }

        // calculate line total and add to cart
        $lineTotal = round($product['Price'] * $quantity, 2);
        CartManager::addItem($cart['CartID'], [
            'ProductID' => $productId,
            'ProductName' => $product['ProductName'],
            'Price' => $product['Price'],
            'Quantity' => $quantity,
            'LineTotal' => $lineTotal,
        ]);

        $cart = CartManager::getById($cart['CartID']);
        $items = CartManager::getItems($cart['CartID']);

        echo json_encode(['success' => true, 'message' => 'Added to cart.', 'cart' => $cart, 'items' => $items]);
        exit;
    }

    public static function updateQuantity(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));

        if ($cartItemId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item.']);
            exit;
        }

        // get current cart item
        $item = CartManager::getCartItem($cartItemId);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
            exit;
        }

        // check stock before updating qty
        if ($quantity > 0) {
            $branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
            $inventory = \App\Models\InventoryManager::getByBranchAndProduct($branchId, $item['ProductID']);
            $availableQty = $inventory ? (int)$inventory['AvailableQty'] : 0;
            if ($quantity > $availableQty) {
                echo json_encode(['success' => false, 'message' => 'Not enough stock.']);
                exit;
            }
        }

        CartManager::updateItemQuantity($cartItemId, $quantity);
        $cart = CartManager::getById($item['CartID']);
        $items = CartManager::getItems($cart['CartID']);

        echo json_encode(['success' => true, 'cart' => $cart, 'items' => $items]);
        exit;
    }

    public static function removeItem(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        if ($cartItemId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item.']);
            exit;
        }

        // get cart item to find cart id
        $item = CartManager::getCartItem($cartItemId);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
            exit;
        }

        CartManager::removeItem($cartItemId);
        $cart = CartManager::getById($item['CartID']);
        $items = CartManager::getItems($cart['CartID']);

        echo json_encode(['success' => true, 'cart' => $cart, 'items' => $items]);
        exit;
    }

    public static function clearCart(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $branchId = (int)($_SESSION['user']['BranchID'] ?? 0);
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;

        // get cart and clear all items
        $cart = CartManager::getOrCreateCart($branchId, $customerId);
        CartManager::clear($cart['CartID']);

        $cart = CartManager::getById($cart['CartID']);
        echo json_encode(['success' => true, 'message' => 'Cart cleared.', 'cart' => $cart, 'items' => []]);
        exit;
    }

    public static function applyPromotion(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $cartId = (int)($_POST['cart_id'] ?? 0);
        $code = trim($_POST['promo_code'] ?? '');

        if ($cartId === 0 || $code === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // lookup promotion by code
        $promo = \App\Models\PromotionManager::getByCode($code);
        if (!$promo) {
            echo json_encode(['success' => false, 'message' => 'Invalid promotion code.']);
            exit;
        }

        // validate promo against cart total
        $cartRow = \App\Models\CartManager::getById($cartId);
        $cartTotal = $cartRow ? (float)($cartRow['Total'] ?? 0) : 0;

        $error = \App\Models\PromotionManager::validate($promo, $cartTotal);
        if ($error) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }

        CartManager::applyPromotion($cartId, $promo['PromotionID'], $code);
        $cart = CartManager::getById($cartId);
        $items = CartManager::getItems($cartId);

        echo json_encode(['success' => true, 'message' => 'Promotion applied.', 'cart' => $cart, 'items' => $items]);
        exit;
    }

    public static function removePromotion(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $cartId = (int)($_POST['cart_id'] ?? 0);
        if ($cartId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        CartManager::removePromotion($cartId);
        $cart = CartManager::getById($cartId);
        $items = CartManager::getItems($cartId);

        echo json_encode(['success' => true, 'message' => 'Promotion removed.', 'cart' => $cart, 'items' => $items]);
        exit;
    }
}