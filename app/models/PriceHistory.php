<?php

class PriceHistory
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function recent(int $limit = 50): array
    {
        $statement = $this->db->prepare(
            'SELECT price_history.*, cards.card_name, cards.set_name, cards.collector_number
             FROM price_history
             INNER JOIN cards ON cards.id = price_history.card_id
             ORDER BY price_history.date_captured DESC, price_history.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function forCard(int $cardId): array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM price_history
             WHERE card_id = :card_id
             ORDER BY date_captured DESC, created_at DESC'
        );
        $statement->execute(['card_id' => $cardId]);

        return $statement->fetchAll();
    }

    public function latestForCard(int $cardId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT *
             FROM price_history
             WHERE card_id = :card_id
             ORDER BY date_captured DESC, created_at DESC
             LIMIT 1'
        );
        $statement->execute(['card_id' => $cardId]);
        $price = $statement->fetch();

        return $price ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO price_history
                    (card_id, source_name, currency, price, converted_php_price, date_captured, notes)
                VALUES
                    (:card_id, :source_name, :currency, :price, :converted_php_price, :date_captured, :notes)';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'card_id' => (int) $data['card_id'],
            'source_name' => $data['source_name'],
            'currency' => strtoupper($data['currency']),
            'price' => $data['price'],
            'converted_php_price' => $data['converted_php_price'],
            'date_captured' => $data['date_captured'],
            'notes' => $data['notes'] ?: null,
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM price_history WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM price_history')->fetchColumn();
    }
}
