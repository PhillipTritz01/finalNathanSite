-- Start transaction
START TRANSACTION;

-- Drop unused tables
DROP TABLE IF EXISTS `cinematography_items`;
DROP TABLE IF EXISTS `commercial_items`;
DROP TABLE IF EXISTS `content`;
DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `photography_items`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `videography_items`;

-- Commit the transaction
COMMIT; 