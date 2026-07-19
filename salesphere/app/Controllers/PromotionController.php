<?php
namespace App\Controllers;

use App\Models\PromotionManager;

class PromotionController {
    public static function index(): array {
        $search = trim($_GET['search'] ?? '');
        // get promotions with optional search
        return PromotionManager::getAll(null, $search);
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($code === '' || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Code and name are required.']); exit;
        }
        // insert new promotion
        $id = PromotionManager::create([
            'Code' => $code,
            'Name' => $name,
            'Description' => trim($_POST['description'] ?? ''),
            'Type' => $_POST['type'] ?? 'Percentage',
            'Scope' => $_POST['scope'] ?? 'Order',
            'Value' => (float)($_POST['value'] ?? 0),
            'MinOrderAmount' => (float)($_POST['min_order_amount'] ?? 0),
            'MaxDiscountAmount' => $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null,
            'BuyQuantity' => $_POST['buy_qty'] !== '' ? (int)$_POST['buy_qty'] : null,
            'GetQuantity' => $_POST['get_qty'] !== '' ? (int)$_POST['get_qty'] : null,
            'GetDiscountPercent' => $_POST['get_discount_percent'] !== '' ? (float)$_POST['get_discount_percent'] : null,
            'StartDate' => $_POST['start_date'],
            'EndDate' => $_POST['end_date'],
            'UsageLimit' => $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null,
            'PerCustomerLimit' => (int)($_POST['per_customer_limit'] ?? 1),
            'IsActive' => isset($_POST['is_active']) ? 1 : 0,
            'IsPublic' => isset($_POST['is_public']) ? 1 : 0,
            'Priority' => (int)($_POST['priority'] ?? 0),
            'CreatedBy' => $_SESSION['user']['UserID'] ?? null,
        ]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created promotion #$id ($code)"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Promotion '$code' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['promotion_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid promotion ID.']); exit;
        }
        $data = [
            'Code' => trim($_POST['code'] ?? ''),
            'Name' => trim($_POST['name'] ?? ''),
            'Description' => trim($_POST['description'] ?? ''),
            'Type' => $_POST['type'] ?? 'Percentage',
            'Scope' => $_POST['scope'] ?? 'Order',
            'Value' => (float)($_POST['value'] ?? 0),
            'MinOrderAmount' => (float)($_POST['min_order_amount'] ?? 0),
            'MaxDiscountAmount' => $_POST['max_discount_amount'] !== '' ? (float)$_POST['max_discount_amount'] : null,
            'BuyQuantity' => $_POST['buy_qty'] !== '' ? (int)$_POST['buy_qty'] : null,
            'GetQuantity' => $_POST['get_qty'] !== '' ? (int)$_POST['get_qty'] : null,
            'GetDiscountPercent' => $_POST['get_discount_percent'] !== '' ? (float)$_POST['get_discount_percent'] : null,
            'StartDate' => $_POST['start_date'],
            'EndDate' => $_POST['end_date'],
            'UsageLimit' => $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null,
            'PerCustomerLimit' => (int)($_POST['per_customer_limit'] ?? 1),
            'IsActive' => isset($_POST['is_active']) ? 1 : 0,
            'IsPublic' => isset($_POST['is_public']) ? 1 : 0,
            'Priority' => (int)($_POST['priority'] ?? 0),
        ];
        // update promotion in db
        PromotionManager::update($id, $data);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated promotion #$id"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Promotion updated.']);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['promotion_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid promotion ID.']); exit;
        }
        // check if promotion exists
        $p = PromotionManager::getById($id);
        if (!$p) {
            echo json_encode(['success' => false, 'message' => 'Promotion not found.']); exit;
        }
        // delete promotion and log
        PromotionManager::delete($id);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted promotion #$id ({$p['Code']})"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Promotion '{$p['Code']}' deleted."]);
        exit;
    }
}
