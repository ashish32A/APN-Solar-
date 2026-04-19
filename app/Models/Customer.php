<?php
// app/Models/Customer.php

require_once __DIR__ . '/Model.php';

class Customer extends Model {
    protected static string $table = 'customers';

    public static function withPayments(): array {
        return static::db()->query("
            SELECT c.*, p.total_amount, p.due_amount, p.payment_received
            FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            ORDER BY c.id DESC
        ")->fetchAll();
    }

    public static function create(array $data): int {
        $sql = "INSERT INTO customers
                (operator_name, group_name, name, email, mobile, ifsc_code, electricity_id, kw, account_number)
                VALUES (:operator_name,:group_name,:name,:email,:mobile,:ifsc_code,:electricity_id,:kw,:account_number)";
        static::db()->prepare($sql)->execute($data);
        return (int)static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $data['id'] = $id;
        $sql = "UPDATE customers SET
                operator_name=:operator_name, group_name=:group_name, name=:name, email=:email,
                mobile=:mobile, ifsc_code=:ifsc_code, electricity_id=:electricity_id, kw=:kw,
                account_number=:account_number WHERE id=:id";
        static::db()->prepare($sql)->execute($data);
    }
}
