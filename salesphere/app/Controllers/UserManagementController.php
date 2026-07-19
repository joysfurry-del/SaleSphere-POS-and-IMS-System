<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Helpers\AuditHelper;

class UserManagementController {
    public static function index(): array {
        // get all users
        return User::getAll();
    }

    public static function getRoles(): array {
        $db = \Database::getConnection();
        return $db->query("SELECT * FROM role ORDER BY RoleID")->fetchAll();
    }

    public static function getBranches(): array {
        return Branch::getAll();
    }

    public static function create(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $username  = trim($_POST['username'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? $username);
        $password  = $_POST['password'] ?? '';
        $email     = trim($_POST['email'] ?? '');
        $tele      = trim($_POST['tele'] ?? '');
        $roleId    = (int)($_POST['role_id'] ?? 0);
        $branchId  = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;

        if ($username === '' || $password === '' || $email === '' || $roleId === 0) {
            jsonResponse(['success' => false, 'message' => 'Username, password, email, and role are required.']);
        }

        // check password length
        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        }

        // validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }

        // check for duplicate username
        if (User::usernameExists($username)) {
            jsonResponse(['success' => false, 'message' => 'Username already taken.']);
        }

        // check for duplicate email
        if (User::emailExists($email)) {
            jsonResponse(['success' => false, 'message' => 'Email already registered.']);
        }

        // create user with hashed password
        $userId = User::create([
            'Username' => $username,
            'FullName' => $fullName,
            'Password' => password_hash($password, PASSWORD_BCRYPT),
            'Email'    => $email,
            'Tele'     => $tele,
            'RoleID'   => $roleId,
            'BranchID' => $branchId,
        ]);

        AuditHelper::log('Created user', 'User', $userId, $username);

        jsonResponse(['success' => true, 'message' => "User '$username' created successfully.", 'id' => $userId]);
    }

    public static function update(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $id       = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? $username);
        $email    = trim($_POST['email'] ?? '');
        $tele     = trim($_POST['tele'] ?? '');
        $roleId   = (int)($_POST['role_id'] ?? 0);
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : null;

        if ($id === 0 || $username === '' || $email === '' || $roleId === 0) {
            jsonResponse(['success' => false, 'message' => 'Required fields missing.']);
        }

        // validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }

        // check for duplicate username excluding current user
        if (User::usernameExists($username, $id)) {
            jsonResponse(['success' => false, 'message' => 'Username already taken.']);
        }

        // check for duplicate email excluding current user
        if (User::emailExists($email, $id)) {
            jsonResponse(['success' => false, 'message' => 'Email already registered.']);
        }

        $data = [
            'Username' => $username,
            'FullName' => $fullName,
            'Email'    => $email,
            'Tele'     => $tele,
            'RoleID'   => $roleId,
            'BranchID' => $branchId,
        ];

        if ($isActive !== null) {
            // Prevent deactivating the last active admin
            if ($isActive === 0) {
                $targetUser = User::findById($id);
                if ($targetUser && (int)$targetUser['RoleID'] === 1) {
                    $admins = User::getAll();
                    $activeAdmins = array_filter($admins, fn($a) => (int)$a['RoleID'] === 1 && (int)$a['IsActive'] === 1 && (int)$a['UserID'] !== $id);
                    if (empty($activeAdmins)) {
                        jsonResponse(['success' => false, 'message' => 'Cannot deactivate the last active admin.']);
                    }
                }
            }
            $data['IsActive'] = $isActive;
        }

        // update password if provided
        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            if (strlen($password) < 6) {
                jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            }
            $data['Password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        // update user in db
        User::update($id, $data);
        AuditHelper::log('Updated user', 'User', $id, $username);

        jsonResponse(['success' => true, 'message' => "User '$username' updated successfully."]);
    }

    public static function delete(): void {
        header('Content-Type: application/json');
        requireAuth();

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
        }

        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
        }

        // prevent self-deletion
        if ($id === (int)($_SESSION['user']['UserID'] ?? 0)) {
            jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.']);
        }

        // check if user exists
        $user = User::findById($id);
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found.']);
        }

        // Prevent deleting the last admin
        if ((int)$user['RoleID'] === 1) {
            $admins = User::getAll();
            $otherAdmins = array_filter($admins, fn($a) => (int)$a['RoleID'] === 1 && (int)$a['UserID'] !== $id);
            if (empty($otherAdmins)) {
                jsonResponse(['success' => false, 'message' => 'Cannot delete the last admin account.']);
            }
        }

        // delete user and log
        User::delete($id);
        AuditHelper::log('Deleted user', 'User', $id, $user['Username']);

        jsonResponse(['success' => true, 'message' => "User '{$user['Username']}' deleted."]);
    }
}
