<?php

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO users (
                    username, first_name, middle_initial, last_name, email, contact_number,
                    password_hash, address_number, address_street, address_barangay,
                    address_province, address_city, address_postal_code, shipping_same_as_complete,
                    shipping_number, shipping_street, shipping_barangay, shipping_province,
                    shipping_city, shipping_postal_code, delivery_mode, payment_mode,
                    city, province, role
                )
                VALUES (
                    :username, :first_name, :middle_initial, :last_name, :email, :contact_number,
                    :password_hash, :address_number, :address_street, :address_barangay,
                    :address_province, :address_city, :address_postal_code, :shipping_same_as_complete,
                    :shipping_number, :shipping_street, :shipping_barangay, :shipping_province,
                    :shipping_city, :shipping_postal_code, :delivery_mode, :payment_mode,
                    :city, :province, :role
                )';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'username' => $data['username'],
            'first_name' => $data['first_name'],
            'middle_initial' => $data['middle_initial'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'contact_number' => $data['contact_number'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'address_number' => $data['address_number'],
            'address_street' => $data['address_street'],
            'address_barangay' => $data['address_barangay'],
            'address_province' => $data['address_province'],
            'address_city' => $data['address_city'],
            'address_postal_code' => $data['address_postal_code'],
            'shipping_same_as_complete' => !empty($data['shipping_same_as_complete']) ? 1 : 0,
            'shipping_number' => $data['shipping_number'],
            'shipping_street' => $data['shipping_street'],
            'shipping_barangay' => $data['shipping_barangay'],
            'shipping_province' => $data['shipping_province'],
            'shipping_city' => $data['shipping_city'],
            'shipping_postal_code' => $data['shipping_postal_code'],
            'delivery_mode' => $data['delivery_mode'],
            'payment_mode' => $data['payment_mode'],
            'city' => $data['city'],
            'province' => $data['province'],
            'role' => $data['role'] ?? 'user',
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function generateUsername(string $email, string $firstName, string $lastName): string
    {
        $base = strtolower(trim($firstName . $lastName));
        if ($base === '') {
            $base = strstr($email, '@', true) ?: 'user';
        }

        $base = preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', $base)) ?: 'user';
        if (strlen($base) < 3) {
            $base .= 'user';
        }

        $base = substr($base, 0, 24);
        $username = $base;
        $suffix = 1;

        while ($this->findByUsername($username)) {
            $suffix++;
            $username = substr($base, 0, 24 - strlen((string) $suffix)) . $suffix;
        }

        return $username;
    }

    public function all(): array
    {
        $statement = $this->db->query(
            'SELECT id, username, email, contact_number, city, province, role, account_status,
                    email_verified_at, suspension_reason, moderation_notes, created_at
             FROM users
             ORDER BY created_at DESC'
        );

        return $statement->fetchAll();
    }

    public function updateRole(int $id, string $role): bool
    {
        $statement = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');

        return $statement->execute([
            'id' => $id,
            'role' => $role,
        ]);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $sql = 'UPDATE users
                SET username = :username, city = :city, province = :province, seller_bio = :seller_bio
                WHERE id = :id';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'id' => $id,
            'username' => $data['username'],
            'city' => $data['city'],
            'province' => $data['province'],
            'seller_bio' => $data['seller_bio'] ?: null,
        ]);
    }

    public function updateModeration(int $id, string $status, string $reason, string $notes): bool
    {
        $statement = $this->db->prepare(
            'UPDATE users SET account_status = :status, suspension_reason = :reason, moderation_notes = :notes WHERE id = :id'
        );
        return $statement->execute(['id' => $id, 'status' => $status, 'reason' => $reason ?: null, 'notes' => $notes ?: null]);
    }

    public function sellerProfile(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT users.id, users.username, users.city, users.province, users.seller_bio, users.created_at,
                    users.email_verified_at, users.account_status,
                    (SELECT COUNT(*) FROM listings WHERE listings.user_id = users.id AND listings.status = 'active') AS active_listing_count,
                    (SELECT COUNT(*) FROM orders WHERE orders.seller_id = users.id AND orders.status = 'completed') AS completed_sale_count
             FROM users WHERE users.id = :id AND users.account_status = 'active' LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function updatePassword(int $id, string $password): bool
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        return $statement->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
