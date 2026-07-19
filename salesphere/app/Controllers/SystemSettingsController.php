<?php
namespace App\Controllers;

use App\Models\SystemSetting;

class SystemSettingsController {
    public static function index(): array {
        // get all system settings
        return SystemSetting::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $name  = trim($_POST['setting_name'] ?? '');
        $value = trim($_POST['setting_value'] ?? '');

        if ($name === '' || $value === '') {
            echo json_encode(['success' => false, 'message' => 'Both name and value are required.']);
            exit;
        }

        // check for duplicate setting name
        if (SystemSetting::nameExists($name)) {
            echo json_encode(['success' => false, 'message' => 'Setting name already exists.']);
            exit;
        }

        // insert new setting
        $id = SystemSetting::create([
            'UserID'       => (int)$_SESSION['user']['UserID'],
            'SettingName'  => $name,
            'SettingValue' => $value,
        ]);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Created setting #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Setting '$name' created.", 'id' => $id]);
        exit;
    }

    public static function update(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id    = (int)($_POST['setting_id'] ?? 0);
        $name  = trim($_POST['setting_name'] ?? '');
        $value = trim($_POST['setting_value'] ?? '');

        if ($id === 0 || $name === '' || $value === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }

        // update setting in db
        SystemSetting::update($id, [
            'SettingName'  => $name,
            'SettingValue' => $value,
            'UserID'       => (int)$_SESSION['user']['UserID'],
        ]);

        // log to transaction log
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Updated setting #$id ($name)"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => "Setting '$name' updated."]);
        exit;
    }

    public static function delete(): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $id = (int)($_POST['setting_id'] ?? 0);
        if ($id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid setting ID.']);
            exit;
        }

        // delete setting and log
        SystemSetting::delete($id);

        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user']['UserID'], "Deleted setting #$id"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => 'Setting deleted.']);
        exit;
    }
}
