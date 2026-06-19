<?php

class CollectionItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forUser(int $userId): array
    {
        $sql = 'SELECT collections.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line, cards.image_url,
                       latest_prices.converted_php_price AS latest_php_price
                FROM collections
                INNER JOIN cards ON cards.id = collections.card_id
                LEFT JOIN price_history latest_prices
                    ON latest_prices.id = (
                        SELECT ph.id
                        FROM price_history ph
                        WHERE ph.card_id = cards.id
                        ORDER BY ph.date_captured DESC, ph.created_at DESC
                        LIMIT 1
                    )
                WHERE collections.user_id = :user_id
                ORDER BY cards.card_name ASC, cards.set_name ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $sql = 'SELECT collections.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line, cards.image_url
                FROM collections
                INNER JOIN cards ON cards.id = collections.card_id
                WHERE collections.id = :id AND collections.user_id = :user_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
        $item = $statement->fetch();

        return $item ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO collections
                    (user_id, card_id, quantity, card_condition, language, is_foil, acquisition_price, notes)
                VALUES
                    (:user_id, :card_id, :quantity, :card_condition, :language, :is_foil, :acquisition_price, :notes)';

        $statement = $this->db->prepare($sql);

        return $statement->execute($this->params($data));
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $sql = 'UPDATE collections SET
                    quantity = :quantity,
                    card_condition = :card_condition,
                    language = :language,
                    is_foil = :is_foil,
                    acquisition_price = :acquisition_price,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id';

        $params = $this->params($data);
        $params['id'] = $id;
        $params['user_id'] = $userId;
        unset($params['card_id']);

        $statement = $this->db->prepare($sql);

        return $statement->execute($params);
    }

    public function delete(int $id, int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM collections WHERE id = :id AND user_id = :user_id');

        return $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public function totalsForUser(int $userId): array
    {
        $sql = 'SELECT
                    COALESCE(SUM(quantity), 0) AS total_cards,
                    COALESCE(SUM(collections.quantity * collections.acquisition_price), 0) AS acquisition_total,
                    COALESCE(SUM(collections.quantity * latest_prices.converted_php_price), 0) AS estimated_market_total
                FROM collections
                LEFT JOIN price_history latest_prices
                    ON latest_prices.id = (
                        SELECT ph.id
                        FROM price_history ph
                        WHERE ph.card_id = collections.card_id
                        ORDER BY ph.date_captured DESC, ph.created_at DESC
                        LIMIT 1
                    )
                WHERE collections.user_id = :user_id';

        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetch() ?: ['total_cards' => 0, 'acquisition_total' => 0, 'estimated_market_total' => 0];
    }

    private function params(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?? null,
            'card_id' => $data['card_id'] ?? null,
            'quantity' => (int) $data['quantity'],
            'card_condition' => $data['card_condition'],
            'language' => $data['language'],
            'is_foil' => !empty($data['is_foil']) ? 1 : 0,
            'acquisition_price' => $data['acquisition_price'] === '' ? 0 : $data['acquisition_price'],
            'notes' => $data['notes'] ?: null,
        ];
    }
}
