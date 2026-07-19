<?php
namespace App\Controllers;

use App\Models\FeedbackManager;

class FeedbackController {
    public static function index(): array {
        $refType = $_GET['ref_type'] ?? '';
        $search = trim($_GET['search'] ?? '');
        // get feedback with optional ref type filter and search
        return FeedbackManager::getAll($refType, $search);
    }

    public static function create(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $rating = (int)($_POST['rating'] ?? 0);
        $refType = $_POST['reference_type'] ?? '';
        $refId = (int)($_POST['reference_id'] ?? 0);
        // validate rating and reference
        if ($rating < 1 || $rating > 5 || !$refType || $refId === 0) {
            echo json_encode(['success' => false, 'message' => 'Rating (1-5), reference type and ID are required.']); exit;
        }
        // insert new feedback
        $id = FeedbackManager::create([
            'ReferenceType' => $refType,
            'ReferenceID' => $refId,
            'CustomerID' => $_POST['customer_id'] ? (int)$_POST['customer_id'] : null,
            'UserID' => $_SESSION['user']['UserID'] ?? null,
            'Rating' => $rating,
            'Title' => trim($_POST['title'] ?? ''),
            'Comment' => trim($_POST['comment'] ?? ''),
            'Pros' => trim($_POST['pros'] ?? ''),
            'Cons' => trim($_POST['cons'] ?? ''),
        ]);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created feedback #$id"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Feedback submitted.', 'id' => $id]);
        exit;
    }

    public static function respond(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['feedback_id'] ?? 0);
        $response = trim($_POST['admin_response'] ?? '');
        if ($id === 0 || $response === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
        }
        // save admin response to feedback
        FeedbackManager::respond($id, $response, $_SESSION['user']['UserID']);
        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Responded to feedback #$id"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Response saved.']);
        exit;
    }

    public static function toggleApproved(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['feedback_id'] ?? 0);
        if ($id === 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
        // toggle approval status
        FeedbackManager::toggleApproved($id);
        echo json_encode(['success' => true, 'message' => 'Approval toggled.']);
        exit;
    }

    public static function toggleFeatured(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['feedback_id'] ?? 0);
        if ($id === 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
        // toggle featured status
        FeedbackManager::toggleFeatured($id);
        echo json_encode(['success' => true, 'message' => 'Featured toggled.']);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']); exit;
        }
        $id = (int)($_POST['feedback_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid feedback ID.']); exit;
        }
        // check if feedback exists
        $f = FeedbackManager::getById($id);
        if (!$f) {
            echo json_encode(['success' => false, 'message' => 'Feedback not found.']); exit;
        }
        // delete feedback and log
        FeedbackManager::delete($id);
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted feedback #$id"]);
        } catch (\Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Feedback deleted.']);
        exit;
    }
}
