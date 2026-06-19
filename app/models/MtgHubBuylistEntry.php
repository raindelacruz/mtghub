<?php

class MtgHubBuylistEntry
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function allActive(): array
    {
        $sql = 'SELECT mtghub_buylist_entries.*, cards.card_name, cards.set_name AS card_set_name,
                       cards.collector_number, cards.rarity, cards.type_line,
                       GREATEST(mtghub_buylist_entries.target_quantity - mtghub_buylist_entries.received_quantity, 0) AS remaining_quantity
                FROM mtghub_buylist_entries
                INNER JOIN cards ON cards.id = mtghub_buylist_entries.card_id
                WHERE mtghub_buylist_entries.is_active = 1
                  AND mtghub_buylist_entries.target_quantity > mtghub_buylist_entries.received_quantity
                ORDER BY cards.card_name ASC, cards.set_name ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function allForAdmin(): array
    {
        $sql = 'SELECT mtghub_buylist_entries.*, cards.card_name, cards.set_name AS card_set_name,
                       cards.collector_number,
                       GREATEST(mtghub_buylist_entries.target_quantity - mtghub_buylist_entries.received_quantity, 0) AS remaining_quantity
                FROM mtghub_buylist_entries
                INNER JOIN cards ON cards.id = mtghub_buylist_entries.card_id
                ORDER BY mtghub_buylist_entries.is_active DESC, cards.card_name ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find($id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT mtghub_buylist_entries.*, cards.card_name, cards.set_name AS card_set_name,
                    cards.collector_number, cards.rarity, cards.type_line,
                    GREATEST(mtghub_buylist_entries.target_quantity - mtghub_buylist_entries.received_quantity, 0) AS remaining_quantity
             FROM mtghub_buylist_entries
             INNER JOIN cards ON cards.id = mtghub_buylist_entries.card_id
             WHERE mtghub_buylist_entries.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $id]);
        $entry = $statement->fetch();

        return $entry ?: null;
    }

    public function findActiveForCard(int $cardId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT *, GREATEST(target_quantity - received_quantity, 0) AS remaining_quantity
             FROM mtghub_buylist_entries
             WHERE card_id = :card_id AND is_active = 1 AND target_quantity > received_quantity
             ORDER BY credit_offer DESC, cash_offer DESC
             LIMIT 1'
        );
        $statement->execute(['card_id' => $cardId]);
        $entry = $statement->fetch();

        return $entry ?: null;
    }

    public function activeByCardIds(array $cardIds): array
    {
        $cardIds = array_values(array_unique(array_filter(array_map('intval', $cardIds))));
        if ($cardIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $statement = $this->db->prepare(
            "SELECT *, GREATEST(target_quantity - received_quantity, 0) AS remaining_quantity
             FROM mtghub_buylist_entries
             WHERE is_active = 1 AND target_quantity > received_quantity AND card_id IN ($placeholders)"
        );
        $statement->execute($cardIds);

        $entries = [];
        foreach ($statement->fetchAll() as $entry) {
            $entries[(int) $entry['card_id']] = $entry;
        }

        return $entries;
    }

    public function create($data): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO mtghub_buylist_entries
                (card_id, set_name, accepted_condition, cash_offer, credit_offer, target_quantity, received_quantity, is_active, admin_notes)
             VALUES
                (:card_id, :set_name, :accepted_condition, :cash_offer, :credit_offer, :target_quantity, 0, :is_active, :admin_notes)'
        );

        return $statement->execute($this->params($data));
    }

    public function update($id, $data): bool
    {
        $params = $this->params($data);
        $params['id'] = (int) $id;
        unset($params['card_id']);

        $statement = $this->db->prepare(
            'UPDATE mtghub_buylist_entries SET
                set_name = :set_name,
                accepted_condition = :accepted_condition,
                cash_offer = :cash_offer,
                credit_offer = :credit_offer,
                target_quantity = :target_quantity,
                is_active = :is_active,
                admin_notes = :admin_notes
             WHERE id = :id'
        );

        return $statement->execute($params);
    }

    public function toggleActive($id): bool
    {
        $statement = $this->db->prepare('UPDATE mtghub_buylist_entries SET is_active = 1 - is_active WHERE id = :id');
        return $statement->execute(['id' => (int) $id]);
    }

    public function remainingQuantity($id): int
    {
        $statement = $this->db->prepare('SELECT GREATEST(target_quantity - received_quantity, 0) FROM mtghub_buylist_entries WHERE id = :id');
        $statement->execute(['id' => (int) $id]);

        return (int) $statement->fetchColumn();
    }

    private function params(array $data): array
    {
        return [
            'card_id' => (int) ($data['card_id'] ?? 0),
            'set_name' => ($data['set_name'] ?? '') !== '' ? trim((string) $data['set_name']) : null,
            'accepted_condition' => ($data['accepted_condition'] ?? '') !== '' ? trim((string) $data['accepted_condition']) : null,
            'cash_offer' => number_format((float) ($data['cash_offer'] ?? 0), 2, '.', ''),
            'credit_offer' => number_format((float) ($data['credit_offer'] ?? 0), 2, '.', ''),
            'target_quantity' => max(0, (int) ($data['target_quantity'] ?? 0)),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'admin_notes' => ($data['admin_notes'] ?? '') !== '' ? trim((string) $data['admin_notes']) : null,
        ];
    }
}
