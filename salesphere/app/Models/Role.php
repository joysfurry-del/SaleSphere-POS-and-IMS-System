<?php
namespace App\Models;

class Role {
    public static function getName(int $roleId): string {
        return \ROLE_NAMES[$roleId] ?? 'Unknown';
    }
}
