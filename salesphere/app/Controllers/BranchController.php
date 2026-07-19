<?php
namespace App\Controllers;

use App\Models\Branch;

class BranchController {
    public static function index(): array {
        // get all branches
        return Branch::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $loc  = trim($_POST['location'] ?? '');
        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Branch name is required.']);
            exit;
        }
        // insert new branch
        $id = Branch::create(['BranchName' => $name, 'Location' => $loc]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)")->execute([$_SESSION['user']['UserID'], "Created branch #$id ($name)"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => "Branch '$name' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id   = (int)($_POST['branch_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $loc  = trim($_POST['location'] ?? '');
        if ($id === 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Required fields missing.']);
            exit;
        }
        // update branch in db
        Branch::update($id, ['BranchName' => $name, 'Location' => $loc]);
        echo json_encode(['success' => true, 'message' => "Branch '$name' updated."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }
        $id = (int)($_POST['branch_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid branch ID.']);
            exit;
        }
        // delete branch from db
        Branch::delete($id);
        echo json_encode(['success' => true, 'message' => 'Branch deleted.']);
        exit;
    }
}
