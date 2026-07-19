<?php
namespace App\Controllers;

use App\Models\User;
use App\Helpers\AuditHelper;

class AuthController {
    public static function login(): void {
        $loginUrl = \BASE_URL . '/public/login.php';

        // redirect if not post request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect($loginUrl);
        }

        // check csrf token
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request token. Please try again.';
            redirect($loginUrl);
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // check empty fields
        if ($username === '' || $password === '') {
            $_SESSION['error'] = 'Please enter both username and password.';
            redirect($loginUrl);
        }

        // find user by username
        $user = User::findByUsername($username);

        // verify password
        if (!$user || !password_verify($password, $user['Password'])) {
            AuditHelper::logLogin(0, $username, false);
            $_SESSION['error'] = 'Invalid username or password.';
            redirect($loginUrl);
        }

        // check if account is active
        if (empty($user['IsActive'])) {
            AuditHelper::logLogin(0, $username, false);
            $_SESSION['error'] = 'Your account has been deactivated. Contact an administrator.';
            redirect($loginUrl);
        }

        session_regenerate_id(true);

        // set session data
        $_SESSION['user'] = [
            'UserID'     => (int)$user['UserID'],
            'Username'   => $user['Username'],
            'FullName'   => $user['FullName'] ?? $user['Username'],
            'RoleID'     => (int)$user['RoleID'],
            'RoleName'   => $user['RoleName'],
            'BranchID'   => (int)$user['BranchID'],
            'BranchName' => $user['BranchName'] ?? '',
            'Email'      => $user['Email'],
            'Tele'       => $user['Tele'] ?? '',
            'Avatar'     => $user['Avatar'] ?? null,
        ];

        AuditHelper::logLogin((int)$user['UserID'], $username, true);

        // redirect to role-based dashboard
        $dash = \ROLE_MAP[$user['RoleID']] ?? 'admin';
        redirect(\BASE_URL . "/public/dashboards/$dash.php");
    }

    public static function logout(): void {
        // log logout action
        if (!empty($_SESSION['user'])) {
            AuditHelper::log('Logout', 'User', $_SESSION['user']['UserID'], $_SESSION['user']['Username'] . ' logged out');
        }

        // clear session and destroy
        $_SESSION = [];
        session_destroy();

        // delete session cookie
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 86400, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }

        redirect(\BASE_URL . '/public/login.php');
    }
}
