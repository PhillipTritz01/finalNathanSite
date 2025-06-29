-- =====================================================
-- Photography CMS SQLite Database Setup Script
-- Database: cmstest.db
-- =====================================================
-- This script will create a fresh SQLite database with all necessary tables

-- Enable foreign key constraints (important for SQLite)
PRAGMA foreign_keys = ON;

-- =====================================================
-- Create portfolio_items table
-- =====================================================
CREATE TABLE IF NOT EXISTS portfolio_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    category TEXT NOT NULL DEFAULT 'clients' CHECK (category IN ('clients', 'fineart', 'portraits', 'travel')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for portfolio_items
CREATE INDEX IF NOT EXISTS idx_portfolio_category ON portfolio_items(category);
CREATE INDEX IF NOT EXISTS idx_portfolio_created ON portfolio_items(created_at);

-- =====================================================
-- Create portfolio_media table for multiple media files
-- =====================================================
CREATE TABLE IF NOT EXISTS portfolio_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    portfolio_item_id INTEGER NOT NULL,
    media_url TEXT NOT NULL,
    media_type TEXT NOT NULL DEFAULT 'image' CHECK (media_type IN ('image', 'video', 'audio')),
    display_order INTEGER DEFAULT 0,
    alt_text TEXT DEFAULT NULL,
    file_size INTEGER DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
);

-- Create indexes for portfolio_media
CREATE INDEX IF NOT EXISTS idx_media_portfolio_item ON portfolio_media(portfolio_item_id);
CREATE INDEX IF NOT EXISTS idx_media_type ON portfolio_media(media_type);
CREATE INDEX IF NOT EXISTS idx_media_display_order ON portfolio_media(display_order);

-- =====================================================
-- Create users table for admin authentication
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT DEFAULT NULL,
    role TEXT DEFAULT 'admin' CHECK (role IN ('admin', 'editor')),
    last_login DATETIME NULL DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for users
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- =====================================================
-- Create gallery_settings table (for gallery configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS gallery_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_name TEXT NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string' CHECK (setting_type IN ('string', 'integer', 'boolean', 'json')),
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create index for gallery_settings
CREATE INDEX IF NOT EXISTS idx_gallery_setting_name ON gallery_settings(setting_name);

-- =====================================================
-- Create trigger to update updated_at timestamps
-- =====================================================
-- Trigger for portfolio_items
CREATE TRIGGER IF NOT EXISTS update_portfolio_items_updated_at
    AFTER UPDATE ON portfolio_items
    FOR EACH ROW
BEGIN
    UPDATE portfolio_items SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger for portfolio_media
CREATE TRIGGER IF NOT EXISTS update_portfolio_media_updated_at
    AFTER UPDATE ON portfolio_media
    FOR EACH ROW
BEGIN
    UPDATE portfolio_media SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger for users
CREATE TRIGGER IF NOT EXISTS update_users_updated_at
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger for gallery_settings
CREATE TRIGGER IF NOT EXISTS update_gallery_settings_updated_at
    AFTER UPDATE ON gallery_settings
    FOR EACH ROW
BEGIN
    UPDATE gallery_settings SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- Insert default admin user
-- Username: admin
-- Password: admin123
-- =====================================================
INSERT OR IGNORE INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@photosite.local', 'admin');

-- =====================================================
-- Insert default gallery settings
-- =====================================================
INSERT OR IGNORE INTO gallery_settings (setting_name, setting_value, setting_type, description) VALUES
('gallery_title', 'Photography Portfolio', 'string', 'Main title for the gallery'),
('items_per_page', '12', 'integer', 'Number of items to show per page in galleries'),
('enable_lightbox', 'true', 'boolean', 'Enable lightbox for image viewing'),
('thumbnail_quality', '85', 'integer', 'JPEG quality for thumbnails (1-100)'),
('max_upload_size', '10485760', 'integer', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', '["jpg","jpeg","png","gif","webp","mp4","mov","avi"]', 'json', 'Allowed file extensions for uploads'),
('gallery_layout', 'grid', 'string', 'Default gallery layout (grid, masonry, list)'),
('show_exif_data', 'false', 'boolean', 'Show EXIF data for images'),
('watermark_enabled', 'false', 'boolean', 'Enable watermark on images'),
('auto_optimize_images', 'true', 'boolean', 'Automatically optimize uploaded images');

-- =====================================================
-- Sample portfolio data (optional - you can remove this section)
-- =====================================================
-- Insert sample portfolio items
INSERT OR IGNORE INTO portfolio_items (title, description, category) VALUES
('Sample Portrait Session', 'A beautiful portrait photography session showcasing natural lighting techniques.', 'portraits'),
('Wedding Photography', 'Capturing precious moments on the most important day.', 'clients'),
('Fine Art Collection', 'Artistic expressions through photography.', 'fineart'),
('Travel Adventures', 'Stunning landscapes from around the world.', 'travel');

-- =====================================================
-- Create views for common queries
-- =====================================================
-- View for portfolio items with their first media
CREATE VIEW IF NOT EXISTS portfolio_with_thumbnail AS
SELECT 
    pi.*,
    pm.media_url as thumbnail_url,
    pm.media_type as thumbnail_type
FROM portfolio_items pi
LEFT JOIN portfolio_media pm ON (
    pi.id = pm.portfolio_item_id 
    AND pm.id = (
        SELECT MIN(id) 
        FROM portfolio_media pm2 
        WHERE pm2.portfolio_item_id = pi.id
    )
)
ORDER BY pi.created_at DESC;

-- =====================================================
-- Create view for portfolio items with media count
-- =====================================================
CREATE VIEW IF NOT EXISTS portfolio_with_media_count AS
SELECT 
    pi.*,
    COUNT(pm.id) as media_count,
    MIN(pm.media_url) as first_media_url
FROM portfolio_items pi
LEFT JOIN portfolio_media pm ON pi.id = pm.portfolio_item_id
GROUP BY pi.id
ORDER BY pi.created_at DESC;

-- =====================================================
-- Database setup complete!
-- =====================================================
-- Default login credentials:
-- Username: admin
-- Password: admin123
-- 
-- Database file: cmstest.db (will be created in your cms folder)
-- 
-- Remember to:
-- 1. Change the default admin password after first login
-- 2. Make sure the cms folder is writable for SQLite database file
-- 3. Set proper file permissions on your uploads directory
-- ===================================================== 