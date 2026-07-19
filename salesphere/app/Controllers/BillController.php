<?php
namespace App\Controllers;

use App\Models\BillManager;
use App\Models\Branch;

class BillController {
    public static function index(): array {
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
        $search = trim($_GET['search'] ?? '');
        // get bills with optional branch filter and search
        return BillManager::getAll($branchId, $search);
    }

    public static function getBranches(): array {
        return Branch::getAll();
    }

    public static function getCustomers(): array {
        return BillManager::getCustomers();
    }

    public static function getOrders(): array {
        return BillManager::getOrders();
    }

    public static function getOnlineOrders(): array {
        return BillManager::getOnlineOrders();
    }

    public static function getProducts(): array {
        return BillManager::getProducts();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $refType = $_POST['reference_type'] ?? 'Manual';
        $refId = (int)($_POST['reference_id'] ?? 0);
        if ($branchId === 0) {
            echo json_encode(['success' => false, 'message' => 'Branch is required.']); exit;
        }
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $taxRate = (float)($_POST['tax_rate'] ?? 0);
        $taxAmount = (float)($_POST['tax_amount'] ?? 0);
        $discount = (float)($_POST['discount_amount'] ?? 0);
        $total = (float)($_POST['total'] ?? 0);
        $paid = (float)($_POST['amount_paid'] ?? 0);
        $balance = $total - $paid;
        // create bill record with auto status
        $billId = BillManager::create([
            'BillType' => $_POST['bill_type'] ?? 'Invoice',
            'ReferenceType' => $refType,
            'ReferenceID' => $refId,
            'CustomerID' => (int)($_POST['customer_id'] ?? 0) ?: null,
            'BranchID' => $branchId,
            'CashierID' => $_SESSION['user']['UserID'] ?? null,
            'Status' => $paid >= $total ? 'Paid' : ($paid > 0 ? 'Partial' : 'Draft'),
            'Subtotal' => $subtotal,
            'TaxRate' => $taxRate,
            'TaxAmount' => $taxAmount,
            'DiscountAmount' => $discount,
            'Total' => $total,
            'AmountPaid' => $paid,
            'Balance' => $balance,
            'PaymentMethod' => $_POST['payment_method'] ?? null,
            'PaymentReference' => trim($_POST['payment_reference'] ?? ''),
            'DueDate' => $_POST['due_date'] ?? null,
            'Notes' => trim($_POST['notes'] ?? ''),
        ]);
        // loop through items and add to bill
        $itemNames = $_POST['item_description'] ?? [];
        $itemQtys = $_POST['item_qty'] ?? [];
        $itemPrices = $_POST['item_price'] ?? [];
        $itemTax = $_POST['item_tax'] ?? [];
        $itemLines = $_POST['item_line_total'] ?? [];
        $isStockPurchase = !empty($_POST['is_stock_purchase']);
        for ($i = 0; $i < count($itemNames); $i++) {
            if (empty(trim($itemNames[$i]))) continue;
            BillManager::addItem($billId, [
                'Description' => trim($itemNames[$i]),
                'Quantity' => (float)($itemQtys[$i] ?? 1),
                'UnitPrice' => (float)($itemPrices[$i] ?? 0),
                'TaxAmount' => (float)($itemTax[$i] ?? 0),
                'LineTotal' => (float)($itemLines[$i] ?? 0),
                'SortOrder' => $i,
            ]);
            // if stock purchase, update inventory qty
            if ($isStockPurchase) {
                $db = \Database::getConnection();
                $pStmt = $db->prepare("SELECT ProductID FROM product WHERE ProductName = ? AND IsActive = 1 LIMIT 1");
                $pStmt->execute([trim($itemNames[$i])]);
                $product = $pStmt->fetch();
                if ($product) {
                    $invStmt = $db->prepare("SELECT InventoryID, AvailableQty FROM inventory WHERE BranchID = ? AND ProductID = ?");
                    $invStmt->execute([$branchId, $product['ProductID']]);
                    $inv = $invStmt->fetch();
                    $qty = (float)($itemQtys[$i] ?? 1);
                    if ($inv) {
                        // add to existing inventory
                        $db->prepare("UPDATE inventory SET AvailableQty = AvailableQty + ? WHERE InventoryID = ?")->execute([$qty, $inv['InventoryID']]);
                    } else {
                        // create new inventory record
                        $db->prepare("INSERT INTO inventory (BranchID, ProductID, AvailableQty) VALUES (?, ?, ?)")->execute([$branchId, $product['ProductID'], $qty]);
                    }
                }
            }
        }
        // record payment if any
        if ($paid > 0) {
            BillManager::addPayment($billId, [
                'PaymentMethod' => $_POST['payment_method'] ?? 'Cash',
                'Amount' => $paid,
                'Reference' => trim($_POST['payment_reference'] ?? ''),
                'ReceivedBy' => $_SESSION['user']['UserID'] ?? null,
            ]);
        }
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created bill #$billId"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Invoice created.', 'id' => $billId]);
        exit;
    }

    public static function updateStatus(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['bill_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id === 0 || !$status) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
        }
        // update bill status and log
        BillManager::updateStatus($id, $status);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated bill #$id status to $status"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Bill status updated to $status."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['bill_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bill ID.']); exit;
        }
        // check if bill exists
        $b = BillManager::getById($id);
        if (!$b) {
            echo json_encode(['success' => false, 'message' => 'Bill not found.']); exit;
        }
        // delete bill and log
        BillManager::delete($id);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted bill #$id ({$b['BillNumber']})"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Bill '{$b['BillNumber']}' deleted."]);
        exit;
    }
}
