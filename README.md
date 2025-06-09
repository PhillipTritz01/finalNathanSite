# Photography Website

A modern photography website with a secure CMS for content management.

## Features
- Responsive design
- Portfolio showcase
- Services pages
- Contact form
- Secure CMS for content management
- Image upload support
- REST API endpoint for frontend integration

## Setup
1. Create a MySQL database and user
2. Import the database structure from `cms/database.sql`
3. Configure database credentials in `cms/includes/config.php`
4. Make sure the `cms/uploads` directory is writable by the web server
5. Access the CMS at `/cms/login.php` (default credentials: admin/admin123)

## Security
- Change the default admin password after first login
- Use HTTPS in production
- Regular security updates recommended

## File Structure
- `/cms` - Content Management System
  - `/api` - API endpoints
  - `/includes` - Configuration and utility files
  - `/uploads` - Uploaded media files
- `/services` - Service-specific pages
- Root directory - Main website pages 