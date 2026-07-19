<?php
namespace App\Controllers;

use App\Models\CategoryManager;

class CategoryController {
    public static function index(): array {
        // get all categories
        return CategoryManager::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Category name is required.']);
            exit;
        }

        // check for duplicate name
        if (CategoryManager::nameExists($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
            exit;
        }

        // insert new category
        $id = CategoryManager::create($name);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created category #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Category '$name' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id   = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($id === 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // check for duplicate name excluding current id
        if (CategoryManager::nameExists($name, $id)) {
            echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
            exit;
        }

        // update category in db
        CategoryManager::update($id, $name);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated category #$id to ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Category updated to '$name'."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id = (int)($_POST['category_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
            exit;
        }

        try {
            // delete category and log
            CategoryManager::delete($id);
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted category #$id"]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete category: it may have products linked to it.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Category deleted.']);
        exit;
    }
}
