<?php
namespace App\Controllers;

use App\Models\ProductReturnManager;
use App\Models\Branch;

class ProductReturnController {
    public static function index(): array {
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
        $search = trim($_GET['search'] ?? '');
        // get returns with optional branch filter and search
        return ProductReturnManager::getAll($branchId, $search);
    }

    public static function getBranches(): array {
        return Branch::getAll();
    }

    public static function getById(int $id): ?array {
        return ProductReturnManager::getById($id);
    }

    public static function getItems(int $returnId): array {
        return ProductReturnManager::getItems($returnId);
    }

    public static function getCustomers(): array {
        return ProductReturnManager::getCustomers();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        $refType = $_POST['reference_type'] ?? 'Order';
        if ($branchId === 0 || $reason === '') {
            echo json_encode(['success' => false, 'message' => 'Branch and reason are required.']); exit;
        }
        $totalRefund = (float)($_POST['total_refund'] ?? 0);
        // create return record
        $returnId = ProductReturnManager::create([
            'ReferenceType' => $refType,
            'ReferenceID' => (int)($_POST['reference_id'] ?? 0),
            'CustomerID' => $_POST['customer_id'] ? (int)$_POST['customer_id'] : null,
            'BranchID' => $branchId,
            'ProcessedBy' => $_SESSION['user']['UserID'] ?? null,
            'Reason' => $reason,
            'ReasonDetails' => trim($_POST['reason_details'] ?? ''),
            'RefundMethod' => $_POST['refund_method'] ?? 'Original Payment',
            'Subtotal' => (float)($_POST['subtotal'] ?? 0),
            'TaxAmount' => (float)($_POST['tax_amount'] ?? 0),
            'RestockFee' => (float)($_POST['restock_fee'] ?? 0),
            'ShippingFee' => (float)($_POST['shipping_fee'] ?? 0),
            'TotalRefund' => $totalRefund,
            'Notes' => trim($_POST['notes'] ?? ''),
        ]);
        // loop through returned items and add them
        $prodNames = $_POST['item_name'] ?? [];
        $prodIds = $_POST['item_product_id'] ?? [];
        $itemQtys = $_POST['item_qty'] ?? [];
        $itemPrices = $_POST['item_price'] ?? [];
        $itemLines = $_POST['item_line_total'] ?? [];
        for ($i = 0; $i < count($prodNames); $i++) {
            if (empty(trim($prodNames[$i]))) continue;
            ProductReturnManager::addItem($returnId, [
                'ProductID' => (int)($prodIds[$i] ?? 0),
                'ProductName' => trim($prodNames[$i]),
                'Quantity' => (int)($itemQtys[$i] ?? 1),
                'UnitPrice' => (float)($itemPrices[$i] ?? 0),
                'LineTotal' => (float)($itemLines[$i] ?? 0),
            ]);
        }
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created return #$returnId"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Return request created.', 'id' => $returnId]);
        exit;
    }

    public static function updateStatus(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['return_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id === 0 || !$status) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
        }
        // update return status and log
        ProductReturnManager::updateStatus($id, $status);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated return #$id status to $status"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Return status updated to $status."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['return_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid return ID.']); exit;
        }
        // check if return exists
        $r = ProductReturnManager::getById($id);
        if (!$r) {
            echo json_encode(['success' => false, 'message' => 'Return not found.']); exit;
        }
        // delete return and log
        ProductReturnManager::delete($id);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted return #$id ({$r['ReturnNumber']})"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Return '{$r['ReturnNumber']}' deleted."]);
        exit;
    }
}
