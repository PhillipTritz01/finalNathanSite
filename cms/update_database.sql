-- Start transaction
START TRANSACTION;

-- Create new portfolio_media table
CREATE TABLE IF NOT EXISTS `portfolio_media` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `portfolio_item_id` int(11) NOT NULL,
    `media_url` varchar(255) NOT NULL,
    `media_type` enum('image','video','audio') NOT NULL,
    `display_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `portfolio_item_id` (`portfolio_item_id`),
    CONSTRAINT `portfolio_media_ibfk_1` FOREIGN KEY (`portfolio_item_id`) REFERENCES `portfolio_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate existing data from portfolio_items to portfolio_media
INSERT INTO `portfolio_media` (`portfolio_item_id`, `media_url`, `media_type`, `display_order`)
SELECT 
    id,
    image_url,
    CASE 
        WHEN image_url LIKE '%.jpg' OR image_url LIKE '%.jpeg' OR image_url LIKE '%.png' OR image_url LIKE '%.gif' OR image_url LIKE '%.webp' THEN 'image'
        WHEN image_url LIKE '%.mp4' OR image_url LIKE '%.webm' OR image_url LIKE '%.ogg' THEN 'video'
        WHEN image_url LIKE '%.mp3' OR image_url LIKE '%.wav' THEN 'audio'
        ELSE 'image'
    END as media_type,
    0
FROM `portfolio_items`
WHERE `image_url` IS NOT NULL;

-- Modify portfolio_items table
ALTER TABLE `portfolio_items`
    DROP COLUMN `image_url`,
    ADD COLUMN `category` varchar(50) DEFAULT 'general' AFTER `description`;

-- Update existing records to have a default category
UPDATE `portfolio_items` SET `category` = 'general' WHERE `category` IS NULL;

-- Commit the transaction
COMMIT; 