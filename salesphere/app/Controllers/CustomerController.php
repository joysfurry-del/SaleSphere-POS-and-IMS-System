<?php
namespace App\Controllers;

use App\Models\CustomerManager;
use App\Models\Branch;

class CustomerController {
    public static function index(): array {
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
        $search = trim($_GET['search'] ?? '');
        // get customers with optional branch filter and search
        return CustomerManager::getAll($branchId, $search);
    }

    public static function getBranches(): array {
        // get all branches for dropdown
        return Branch::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $branchId  = (int)($_POST['branch_id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $city      = trim($_POST['city'] ?? '');
        $state     = trim($_POST['state'] ?? '');
        $postcode  = trim($_POST['postcode'] ?? '');
        $country   = trim($_POST['country'] ?? 'Malaysia');
        $notes     = trim($_POST['notes'] ?? '');

        if ($branchId === 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Branch and customer name are required.']);
            exit;
        }
        // validate email format
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        // check if email already taken
        if (CustomerManager::emailExists($email)) {
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            exit;
        }

        // insert new customer
        $id = CustomerManager::create([
            'BranchID'    => $branchId,
            'CustomerName' => $name,
            'Email'       => $email,
            'Phone'       => $phone,
            'Address'     => $address,
            'City'        => $city,
            'State'       => $state,
            'Postcode'    => $postcode,
            'Country'     => $country,
            'Notes'       => $notes,
        ]);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created customer #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Customer '$name' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id      = (int)($_POST['customer_id'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $state   = trim($_POST['state'] ?? '');
        $postcode= trim($_POST['postcode'] ?? '');
        $country = trim($_POST['country'] ?? 'Malaysia');
        $notes   = trim($_POST['notes'] ?? '');

        if ($id === 0 || $branchId === 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Required fields missing.']);
            exit;
        }
        // validate email format
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }
        // check if email already taken by another customer
        if (CustomerManager::emailExists($email, $id)) {
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            exit;
        }

        // update customer in db
        CustomerManager::update($id, [
            'BranchID'     => $branchId,
            'CustomerName' => $name,
            'Email'        => $email,
            'Phone'        => $phone,
            'Address'      => $address,
            'City'         => $city,
            'State'        => $state,
            'Postcode'     => $postcode,
            'Country'      => $country,
            'Notes'        => $notes,
        ]);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated customer #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Customer '$name' updated."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id = (int)($_POST['customer_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
            exit;
        }
        // check if customer exists
        $customer = CustomerManager::getById($id);
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Customer not found.']);
            exit;
        }
        // delete customer and log
        CustomerManager::delete($id);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted customer #$id ({$customer['CustomerName']})"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Customer '{$customer['CustomerName']}' deleted."]);
        exit;
    }

    public static function getById(int $id): ?array {
        return CustomerManager::getById($id);
    }
}