<?php

class Card
{
    private PDO $db;
    private ?bool $hasFullTextSearch = null;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function search(array $filters = [], int $limit = 24, int $offset = 0): array
    {
        [$where, $params, $select, $order] = $this->searchParts($filters);
        $sql = "SELECT {$select} FROM cards {$where} {$order} LIMIT :limit OFFSET :offset";

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countSearch(array $filters = []): int
    {
        [$where, $params] = $this->searchParts($filters);
        unset($params['q_fulltext_score']);

        $sql = 'SELECT COUNT(*) FROM cards ' . $where;
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM cards WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $card = $statement->fetch();

        return $card ?: null;
    }

    public function searchForPicker(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $sql = 'SELECT id, card_name, set_name, collector_number
                FROM cards
                WHERE card_name LIKE :name_query
                   OR set_name LIKE :set_query
                   OR collector_number = :collector_number
                ORDER BY
                    CASE
                        WHEN card_name LIKE :name_prefix THEN 0
                        WHEN set_name LIKE :set_prefix THEN 1
                        ELSE 2
                    END,
                    card_name ASC,
                    set_name ASC,
                    collector_number ASC
                LIMIT :limit';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':name_query', '%' . $query . '%');
        $statement->bindValue(':set_query', '%' . $query . '%');
        $statement->bindValue(':collector_number', $query);
        $statement->bindValue(':name_prefix', $query . '%');
        $statement->bindValue(':set_prefix', $query . '%');
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO cards
                    (card_name, set_name, collector_number, rarity, color, type_line, image_url, scryfall_id)
                VALUES
                    (:card_name, :set_name, :collector_number, :rarity, :color, :type_line, :image_url, :scryfall_id)';

        $statement = $this->db->prepare($sql);

        return $statement->execute($this->params($data));
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE cards SET
                    card_name = :card_name,
                    set_name = :set_name,
                    collector_number = :collector_number,
                    rarity = :rarity,
                    color = :color,
                    type_line = :type_line,
                    image_url = :image_url,
                    scryfall_id = :scryfall_id
                WHERE id = :id';

        $params = $this->params($data);
        $params['id'] = $id;

        $statement = $this->db->prepare($sql);

        return $statement->execute($params);
    }

    public function upsertFromScryfall(array $data): int
    {
        $sql = 'INSERT INTO cards
                    (card_name, set_name, collector_number, rarity, color, type_line, image_url, scryfall_id)
                VALUES
                    (:card_name, :set_name, :collector_number, :rarity, :color, :type_line, :image_url, :scryfall_id)
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    card_name = VALUES(card_name),
                    set_name = VALUES(set_name),
                    collector_number = VALUES(collector_number),
                    rarity = VALUES(rarity),
                    color = VALUES(color),
                    type_line = VALUES(type_line),
                    image_url = VALUES(image_url),
                    scryfall_id = VALUES(scryfall_id)';

        $statement = $this->db->prepare($sql);
        $statement->execute($this->params($data));

        $id = (int) $this->db->lastInsertId();

        return $id > 0 ? $id : $this->findImportedId($data);
    }

    public function distinctValues(string $field): array
    {
        $allowed = ['rarity', 'color', 'set_name'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        $statement = $this->db->query("SELECT DISTINCT {$field} FROM cards WHERE {$field} <> '' ORDER BY {$field} ASC");

        return array_column($statement->fetchAll(), $field);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM cards')->fetchColumn();
    }

    private function findImportedId(array $data): int
    {
        if (!empty($data['scryfall_id'])) {
            $statement = $this->db->prepare('SELECT id FROM cards WHERE scryfall_id = :scryfall_id LIMIT 1');
            $statement->execute(['scryfall_id' => $data['scryfall_id']]);
            $id = $statement->fetchColumn();

            if ($id !== false) {
                return (int) $id;
            }
        }

        $statement = $this->db->prepare(
            'SELECT id FROM cards
             WHERE card_name = :card_name AND set_name = :set_name AND collector_number = :collector_number
             LIMIT 1'
        );
        $statement->execute([
            'card_name' => $data['card_name'],
            'set_name' => $data['set_name'],
            'collector_number' => $data['collector_number'],
        ]);

        return (int) $statement->fetchColumn();
    }

    private function searchParts(array $filters): array
    {
        $sql = 'WHERE 1 = 1';
        $params = [];
        $select = '*';
        $order = 'ORDER BY card_name ASC, set_name ASC, collector_number ASC';

        if (!empty($filters['q'])) {
            $query = trim((string) $filters['q']);
            $booleanQuery = $this->booleanSearchQuery($query);

            if ($booleanQuery !== '' && $this->hasFullTextSearchIndex()) {
                $select = '*, MATCH(card_name, set_name, type_line) AGAINST (:q_fulltext_score IN BOOLEAN MODE) AS search_score';
                $sql .= ' AND MATCH(card_name, set_name, type_line) AGAINST (:q_fulltext_filter IN BOOLEAN MODE)';
                $params['q_fulltext_score'] = $booleanQuery;
                $params['q_fulltext_filter'] = $booleanQuery;
                $order = 'ORDER BY search_score DESC, card_name ASC, set_name ASC, collector_number ASC';
            } else {
                $sql .= ' AND (card_name LIKE :q_card_name OR set_name LIKE :q_set_name OR collector_number = :q_collector_number)';
                $prefixQuery = $query . '%';
                $params['q_card_name'] = $prefixQuery;
                $params['q_set_name'] = $prefixQuery;
                $params['q_collector_number'] = $query;
            }
        }

        foreach (['rarity', 'color', 'set_name'] as $field) {
            if (!empty($filters[$field])) {
                $sql .= " AND {$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }

        return [$sql, $params, $select, $order];
    }

    private function hasFullTextSearchIndex(): bool
    {
        if ($this->hasFullTextSearch !== null) {
            return $this->hasFullTextSearch;
        }

        $statement = $this->db->prepare(
            "SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'cards'
               AND index_name = 'ft_cards_search'"
        );
        $statement->execute();

        $this->hasFullTextSearch = (int) $statement->fetchColumn() > 0;

        return $this->hasFullTextSearch;
    }

    private function booleanSearchQuery(string $query): string
    {
        preg_match_all('/[a-z0-9]+/i', $query, $matches);
        $terms = [];

        foreach ($matches[0] as $term) {
            if (mb_strlen($term) >= 3) {
                $terms[] = '+' . $term . '*';
            }
        }

        return implode(' ', array_unique($terms));
    }

    private function params(array $data): array
    {
        return [
            'card_name' => $data['card_name'],
            'set_name' => $data['set_name'],
            'collector_number' => $data['collector_number'],
            'rarity' => $data['rarity'],
            'color' => $data['color'],
            'type_line' => $data['type_line'],
            'image_url' => $data['image_url'] ?: null,
            'scryfall_id' => $data['scryfall_id'] ?: null,
        ];
    }
}
