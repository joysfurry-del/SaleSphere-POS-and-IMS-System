<?php
namespace App\Controllers;

use App\Models\InventoryManager;

class InventoryController {
    public static function index(?int $branchId = null): array {
        // get inventory for branch (or all)
        return InventoryManager::getAll($branchId);
    }

    public static function getBranches(): array {
        return InventoryManager::getBranches();
    }

    public static function getProducts(): array {
        return InventoryManager::getProducts();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $branchId  = (int)($_POST['branch_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty       = (int)($_POST['qty'] ?? 0);
        if ($branchId === 0 || $productId === 0 || $qty < 0) {
            echo json_encode(['success' => false, 'message' => 'Branch, product, and valid quantity required.']);
            exit;
        }
        // check if product already in inventory for this branch
        $existing = InventoryManager::getByBranchAndProduct($branchId, $productId);
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'This product already exists in inventory for this branch. Use Edit to update quantity.']);
            exit;
        }
        // insert new inventory record
        $id = InventoryManager::create([
            'BranchID'     => $branchId,
            'ProductID'    => $productId,
            'AvailableQty' => $qty,
        ]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created inventory #$id"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Stock item added.', 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id  = (int)($_POST['inventory_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? -1);
        if ($id === 0 || $qty < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        // update stock qty and timestamp
        InventoryManager::update($id, ['AvailableQty' => $qty, 'LastUpdated' => date('Y-m-d H:i:s')]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated inventory #$id to qty $qty"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Stock quantity updated.']);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id = (int)($_POST['inventory_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }
        // delete inventory record
        InventoryManager::delete($id);
        echo json_encode(['success' => true, 'message' => 'Stock item removed.']);
        exit;
    }
}
