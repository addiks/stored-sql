SELECT ENGINE, AUTO_INCREMENT, TABLE_COLLATION, TABLE_COMMENT, CREATE_OPTIONS
FROM information_schema.TABLES
WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lorem_ipsum'