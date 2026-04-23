# Parrot Canada Visa Consultant - Backend

A secure PHP backend API for the Parrot Canada Visa Consultant website.

## Features

- RESTful API architecture
- Secure authentication system
- Database management with PDO
- File upload handling
- Environment-based configuration
- Security best practices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Composer (recommended)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/kass2024/parrot-web-backend.git
   cd parrot-web-backend
   ```

2. **Set up environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Edit the `.env` file with your actual configuration:
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_NAME=parrot_visa_cms
   DB_USER=your_db_username
   DB_PASS=your_db_password
   
   # Site Configuration
   SITE_URL=https://yourdomain.com/backend/
   SITE_NAME=Parrot Canada Visa Consultant - Admin
   ADMIN_EMAIL=your-email@domain.com
   
   # Security Configuration (IMPORTANT: Change these in production!)
   JWT_SECRET=your_very_secure_jwt_secret_key_here
   APP_KEY=your_very_secure_app_key_here
   ```

3. **Set up the database**
   - Create a MySQL database named `parrot_visa_cms`
   - Import the provided `database.sql` file:
   ```bash
   mysql -u username -p parrot_visa_cms < database.sql
   ```

4. **Set up directories**
   ```bash
   mkdir -p public/backend/uploads
   chmod 755 public/backend/uploads
   ```

5. **Configure web server**

   **For Apache:**
   - Ensure mod_rewrite is enabled
   - Point DocumentRoot to the project directory
   - Create .htaccess if needed

   **For Nginx:**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

## Security Considerations

- **Never commit .env files** to version control
- **Always change JWT_SECRET and APP_KEY** in production
- Use strong database passwords
- Keep PHP and MySQL updated
- Use HTTPS in production
- Regularly backup your database

## API Endpoints

### Authentication
- `GET /auth/login` - Login page
- `POST /auth/login` - Process login
- `GET /auth/logout` - Logout

### Dashboard
- `GET /dashboard` - Main dashboard
- `GET /dashboard/api/stats` - Dashboard statistics

### Gallery
- `GET /gallery` - List gallery items
- `POST /gallery/upload` - Upload image
- `POST /gallery/delete/{id}` - Delete image

### Public API
- `GET /api/menu` - Get menu data
- `GET /api/gallery` - Get public gallery
- `GET /api/content/{section}` - Get content sections

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | localhost |
| `DB_NAME` | Database name | parrot_visa_cms |
| `DB_USER` | Database username | root |
| `DB_PASS` | Database password | (empty) |
| `SITE_URL` | Base URL of the application | http://localhost/backend/ |
| `JWT_SECRET` | Secret for JWT tokens | (must be changed) |
| `APP_KEY` | Application encryption key | (must be changed) |
| `APP_ENV` | Environment (development/production) | development |
| `DEBUG` | Enable debug mode | true |

## File Structure

```
backend/
├── app/
│   ├── config/
│   │   ├── config.php          # Main configuration
│   │   └── database.php        # Database connection
│   ├── controllers/            # API controllers
│   ├── models/                 # Data models
│   └── helpers/                # Helper functions
├── public/
│   └── backend/
│       └── uploads/            # File uploads
├── .env                        # Environment variables (DON'T COMMIT)
├── .env.example               # Environment template
├── .gitignore                 # Git ignore rules
├── database.sql               # Database schema
├── index.php                  # Entry point
└── README.md                  # This file
```

## Development

1. Enable error reporting in development:
   ```env
   DEBUG=true
   APP_ENV=development
   ```

2. Use the test files to verify setup:
   - Visit `/test-db.php` to test database connection
   - Check error logs for issues

## Production Deployment

1. Set production environment:
   ```env
   APP_ENV=production
   DEBUG=false
   ```

2. Ensure all security keys are changed:
   ```env
   JWT_SECRET=your_production_jwt_secret
   APP_KEY=your_production_app_key
   ```

3. Configure HTTPS and security headers
4. Set up regular backups
5. Monitor error logs

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is proprietary to Parrot Canada Visa Consultant.

## Support

For support, contact: admin@parrotvisa.com
