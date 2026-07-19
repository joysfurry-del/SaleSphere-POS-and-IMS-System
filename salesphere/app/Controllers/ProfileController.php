<?php
namespace App\Controllers;

use App\Models\User;

class ProfileController {
    public static function show(int $userId): array {
        $user = User::findById($userId);
        if (!$user) {
            redirect('../login.php');
        }
        return $user;
    }

    public static function updateInfo(int $userId): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tele  = trim($_POST['tele'] ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Name is required.']);
            exit;
        }

        // validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // update user info in db
        $ok = User::update($userId, [
            'Username' => $name,
            'Email'    => $email,
            'Tele'     => $tele,
        ]);

        if ($ok) {
            // update session with new values
            $_SESSION['user']['Username'] = $name;
            $_SESSION['user']['Email']    = $email;
            $_SESSION['user']['Tele']     = $tele;
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made.']);
        }
        exit;
    }

    public static function changePassword(int $userId): void {
        header('Content-Type: application/json');

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit;
        }

        // check if new passwords match
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit;
        }

        // check password length
        if (strlen($new) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        // verify current password
        $user = User::findById($userId);
        if (!$user || !password_verify($current, $user['Password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        // update password with new hash
        User::updatePassword($userId, password_hash($new, PASSWORD_BCRYPT));

        // log password change
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO transaction_log (UserID, ActionDescription) VALUES (?, ?)");
            $stmt->execute([$userId, "Password changed for {$user['Username']}"]);
        } catch (\Throwable $e) {}

        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        exit;
    }

    public static function updateAvatar(int $userId): void {
        header('Content-Type: application/json');

        // check if file was uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['avatar']['error'] ?? -1;
            echo json_encode(['success' => false, 'message' => "Upload failed (error code $code)."]);
            exit;
        }

        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        // validate file type
        if (!in_array($file['type'], $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG or WebP images are allowed.']);
            exit;
        }

        // check file size
        if ($file['size'] > MAX_AVATAR_SIZE) {
            echo json_encode(['success' => false, 'message' => 'Image must be under 2 MB.']);
            exit;
        }

        // ensure profiles directory exists
        $profilesDir = UPLOAD_PATH . 'profiles' . DIRECTORY_SEPARATOR;
        if (!is_dir($profilesDir)) {
            mkdir($profilesDir, 0755, true);
        }

        $destPath = $profilesDir . $userId . '.webp';

        // create image resource from upload
        $source = match ($file['type']) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };

        if (!$source) {
            echo json_encode(['success' => false, 'message' => 'Could not process the image.']);
            exit;
        }

        // resize to 200x200 square thumbnail
        $size = 200;
        $thumb = imagecreatetruecolor($size, $size);
        $sw = imagesx($source);
        $sh = imagesy($source);
        $min = min($sw, $sh);
        imagecopyresampled($thumb, $source, 0, 0, ($sw - $min) / 2, ($sh - $min) / 2, $size, $size, $min, $min);

        // save as webp and clean up
        imagewebp($thumb, $destPath, 80);
        imagedestroy($source);
        imagedestroy($thumb);

        // update user avatar in db and session
        $relativePath = 'profiles/' . $userId . '.webp';
        User::update($userId, ['Avatar' => $relativePath]);
        $_SESSION['user']['Avatar'] = $relativePath;

        echo json_encode([
            'success' => true,
            'message' => 'Avatar updated.',
            'url'     => BASE_URL . '/public/assets/uploads/' . $relativePath,
        ]);
        exit;
    }
}
