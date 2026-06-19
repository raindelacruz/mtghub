USE mtghub;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'cards'
      AND index_name = 'ft_cards_search'
);

SET @sql = IF(
    @index_exists = 0,
    'ALTER TABLE cards ADD FULLTEXT KEY ft_cards_search (card_name, set_name, type_line)',
    'SELECT ''ft_cards_search already exists'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;
