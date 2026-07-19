<?php
namespace App\Controllers;

use App\Models\ProductManager;
use App\Models\CategoryManager;

class ProductController {
    public static function index(): array {
        // get all products
        return ProductManager::getAll();
    }

    public static function getCategories(): array {
        return CategoryManager::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $name        = trim($_POST['product_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0 || $categoryId === 0) {
            echo json_encode(['success' => false, 'message' => 'Product name, price, and category are required.']);
            exit;
        }

        // handle optional image upload
        $imagePath = self::handleImageUpload();
        if ($imagePath === false) return;

        // insert new product
        $id = ProductManager::create([
            'ProductName'  => $name,
            'Description'  => $description,
            'Price'        => $price,
            'CategoryID'   => $categoryId,
            'ProductImage' => $imagePath,
        ]);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created product #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Product '$name' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id          = (int)($_POST['product_id'] ?? 0);
        $name        = trim($_POST['product_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if ($id === 0 || $name === '' || $price <= 0 || $categoryId === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request. All fields required.']);
            exit;
        }

        // handle optional image upload
        $imagePath = self::handleImageUpload();
        if ($imagePath === false) return;

        $data = [
            'ProductName' => $name,
            'Description' => $description,
            'Price'       => $price,
            'CategoryID'  => $categoryId,
        ];
        // only update image if new one uploaded
        if ($imagePath !== null) {
            $data['ProductImage'] = $imagePath;
        }
        // update product in db
        ProductManager::update($id, $data);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated product #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Product '$name' updated."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id = (int)($_POST['product_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
            exit;
        }

        // check if product exists
        $product = ProductManager::getById($id);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // soft delete product
        ProductManager::delete($id);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted product #$id ({$product['ProductName']})"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Product '{$product['ProductName']}' deleted."]);
        exit;
    }

    public static function toggleEcommerce(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id = (int)($_POST['product_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
            exit;
        }

        // toggle ecommerce availability for this product
        ProductManager::toggleEcommerce($id);
        $product = ProductManager::getById($id);
        $status = $product['IsEcommerce'] ? 'enabled' : 'disabled';

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Toggled e-commerce for product #$id ($status)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "E-commerce $status for '{$product['ProductName']}'.", 'IsEcommerce' => (bool)$product['IsEcommerce']]);
        exit;
    }

    private static function handleImageUpload(): string|null|false {
        // check if file was uploaded
        if (empty($_FILES['product_image']) || $_FILES['product_image']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Image upload error.']);
            exit;
        }
        // validate file type
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['product_image']['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.']);
            exit;
        }
        // generate unique filename and save
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = __DIR__ . '/../../public/uploads/products/' . $filename;
        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
            exit;
        }
        return 'uploads/products/' . $filename;
    }
}
