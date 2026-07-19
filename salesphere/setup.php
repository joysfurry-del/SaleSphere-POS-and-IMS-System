<?php
/**
 * Salesphere - First-Time Setup
 *
 * Run this ONCE after importing the database schema.
 * Access: http://localhost/salesphere/setup.php
 *
 * This will:
 * 1. Seed the 7 roles
 * 2. Create a "Main Branch"
 * 3. Create an Admin user with password: admin123
 * 4. Add your Avatar column
 */

$dbHost = 'localhost';
$dbName = 'salesphere_db';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "<h2 style='font-family:sans-serif;'>Salesphere Setup</h2>";

    // 1. Add missing columns if not exists
    $colChecks = [
        "ALTER TABLE user ADD COLUMN Avatar VARCHAR(255) DEFAULT NULL AFTER Tele" => 'Avatar',
        "ALTER TABLE user ADD COLUMN FullName VARCHAR(100) DEFAULT NULL AFTER Username" => 'FullName',
        "ALTER TABLE user ADD COLUMN IsActive TINYINT(1) NOT NULL DEFAULT 1 AFTER Avatar" => 'IsActive',
    ];
    foreach ($colChecks as $sql => $col) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green;'>✓ $col column added</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "<p style='color:gray;'>- $col column already exists</p>";
            }
        }
    }
    // Set FullName from Username for existing users
    $pdo->exec("UPDATE user SET FullName = Username WHERE FullName IS NULL");

    // 2. Seed Roles
    $roles = [
        [1, 'Admin'],
        [2, 'Accountant'],
        [3, 'Cashier'],
        [4, 'Branch Manager'],
        [5, 'Sales Executive'],
        [6, 'Digital Marketing'],
        [7, 'Call Center'],
    ];
    $roleStmt = $pdo->prepare("INSERT IGNORE INTO role (RoleID, RoleName) VALUES (?, ?)");
    foreach ($roles as $r) {
        $roleStmt->execute($r);
    }
    echo "<p style='color:green;'>✓ Roles seeded (Admin, Accountant, Cashier, Branch Manager, Sales Exec, Digital Marketing, Call Center)</p>";

    // 3. Seed Main Branch
    $pdo->exec("INSERT IGNORE INTO branch (BranchID, BranchName, Location) VALUES (1, 'Main Branch', '123 Main Street')");
    echo "<p style='color:green;'>✓ Main Branch created</p>";

    // 4. Create / fix admin user
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $check = $pdo->query("SELECT UserID, Password FROM user WHERE Username = 'admin'")->fetch();
    if ($check) {
        if (!password_verify('admin123', $check['Password'])) {
            $stmt = $pdo->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
            $stmt->execute([$hash, $check['UserID']]);
            echo "<p style='color:green;'>✓ Admin password reset</p>";
        } else {
            echo "<p>✓ Admin user already exists with correct password</p>";
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO user (RoleID, BranchID, Username, Password, Email, Tele) VALUES (1, 1, 'admin', ?, 'admin@salesphere.com', '+60 12-3456789')");
        $stmt->execute([$hash]);
        echo "<p style='color:green;font-weight:bold;'>✓ Admin user created!<br>Username: <strong>admin</strong><br>Password: <strong>admin123</strong></p>";
    }

    // 5. Seed default tax rate
    $taxCheck = $pdo->query("SELECT SettingNumber FROM system_settings WHERE SettingName = 'tax_rate'")->fetch();
    if ($taxCheck) {
        echo "<p>✓ Tax rate already set (SettingNumber: {$taxCheck['SettingNumber']})</p>";
    } else {
        $pdo->exec("INSERT INTO system_settings (UserID, SettingName, SettingValue) VALUES (1, 'tax_rate', '6.00')");
        echo "<p style='color:green;'>✓ Default tax rate set to 6%</p>";
    }

    echo "<hr><p><a href='index.php' style='color:#FF5722;font-weight:bold;'>→ Go to Login</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;font-family:sans-serif;'>Database error: " . $e->getMessage() . "</p>";
    echo "<p style='font-family:sans-serif;'>Make sure MySQL is running and the database '{$dbName}' exists.</p>";
}
