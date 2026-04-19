<?php
// app/Models/User.php

require_once __DIR__ . '/Model.php';

class User extends Model {
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array {
        $stmt = static::db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $password, string $role = 'admin'): void {
        $stmt = static::db()->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
}
