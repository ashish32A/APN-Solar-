<?php
// app/Models/Model.php - Base Model

class Model {

    protected static string $table = '';

    protected static function db(): PDO {
        global $pdo;
        return $pdo;
    }

    public static function all(string $orderBy = 'id DESC'): array {
        return static::db()->query("SELECT * FROM " . static::$table . " ORDER BY {$orderBy}")->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): void {
        static::db()->prepare("DELETE FROM " . static::$table . " WHERE id = ?")->execute([$id]);
    }

    public static function count(): int {
        return (int)static::db()->query("SELECT COUNT(*) FROM " . static::$table)->fetchColumn();
    }
}
