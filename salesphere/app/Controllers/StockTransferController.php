<?php
namespace App\Controllers;

use App\Models\StockTransferManager;

class StockTransferController {
    public static function pending(): array {
        // get all pending transfers
        return StockTransferManager::getPending();
    }

    public static function all(): array {
        // get all transfers
        return StockTransferManager::getAll();
    }

    public static function byBranch(int $branchId): array {
        // get transfers for a specific branch
        return StockTransferManager::getByBranch($branchId);
    }

    public static function getProducts(): array {
        return StockTransferManager::getProducts();
    }

    public static function request(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $branchId  = (int)($_SESSION['user']['BranchID'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty       = (int)($_POST['qty'] ?? 0);
        if ($productId === 0 || $qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product and quantity required.']);
            exit;
        }
        // create transfer request
        $id = StockTransferManager::create([
            'BranchID'     => $branchId,
            'ProductID'    => $productId,
            'RequestedQty' => $qty,
        ]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Stock transfer #$id requested for branch #$branchId"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Transfer requested. Awaiting admin approval.', 'id' => $id]);
        exit;
    }

    public static function approve(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id = (int)($_POST['transfer_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid transfer ID.']);
            exit;
        }
        try {
            // approve transfer and update inventory
            $ok = StockTransferManager::approve($id);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
        if ($ok) {
            // log approval
            try {
                $db = \Database::getConnection();
                $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user']['UserID'], "Approved transfer #$id"]);
            } catch (\Throwable $e) {}
            echo json_encode(['success' => true, 'message' => 'Transfer approved. Inventory updated.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transfer not found or already processed.']);
        }
        exit;
    }

    public static function reject(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id = (int)($_POST['transfer_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid transfer ID.']);
            exit;
        }
        // reject transfer request
        $ok = StockTransferManager::reject($id);
        if ($ok) {
            // log rejection
            try {
                $db = \Database::getConnection();
                $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user']['UserID'], "Rejected transfer #$id"]);
            } catch (\Throwable $e) {}
            echo json_encode(['success' => true, 'message' => 'Transfer rejected.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transfer not found or already processed.']);
        }
        exit;
    }
}
