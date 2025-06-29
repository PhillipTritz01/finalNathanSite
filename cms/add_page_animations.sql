-- Create page_settings table to store animation settings for each page
CREATE TABLE IF NOT EXISTS page_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_name TEXT NOT NULL UNIQUE,
    refresh_animation TEXT DEFAULT 'fade',
    gallery_animation TEXT DEFAULT 'rotate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings for each page
INSERT OR IGNORE INTO page_settings (page_name, refresh_animation, gallery_animation) VALUES
('home', 'fade', 'rotate'),
('about', 'slideUp', 'rotate'),
('contact', 'slideDown', 'rotate');

-- Create trigger to update the updated_at column
CREATE TRIGGER IF NOT EXISTS update_page_settings_timestamp 
    AFTER UPDATE ON page_settings
BEGIN
    UPDATE page_settings SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END; 