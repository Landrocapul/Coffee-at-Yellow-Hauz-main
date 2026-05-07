# Coffee at Yellow Hauz POS System

A modern, feature-rich Point of Sale (POS) system for coffee shops, built with PHP and MySQL. This system provides a complete solution for managing orders, tables, menu items, sales tracking, and business analytics.

## Features

- **User Authentication**: Secure login system with role-based access (Admin and Cashier)
- **Menu Management**: Browse and manage menu items by category
- **Order Processing**: Create and manage customer orders with cart functionality
- **Table Management**: Track table availability and occupancy
- **Ticket System**: View and manage active and completed orders
- **Sales Reporting**: Comprehensive sales analytics with charts and filters
- **Product Analytics**: Track product performance and revenue
- **Settings Management**: Configure business settings and manage users
- **Responsive Design**: Beautiful vintage-themed UI that works on all devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache, Nginx, or PHP's built-in server)
- Modern web browser with JavaScript enabled

## Installation

### 1. Database Setup

Import the database schema using the provided SQL file:

```bash
mysql -u root -p < database.sql
```

Or use phpMyAdmin to import `database.sql`.

This will create:
- Database: `yellow_hauz_pos`
- Tables: `users`, `categories`, `menu_items`, `tables`, `orders`, `order_items`, `settings`
- Default users and sample data

### 2. Configuration

Edit `db.php` to configure your database connection:

```php
define('DB_HOST', 'localhost');      // Your database host
define('DB_NAME', 'yellow_hauz_pos'); // Your database name
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
```

### 3. Deploy Files

Upload all PHP files to your web server directory (e.g., `htdocs` or `public_html`).

### 4. Set Permissions

Ensure the web server has write permissions to the session storage directory if needed.

## Default Login Credentials

The system comes with two default users:

### Admin Account
- **Employee ID**: `ADMIN001`
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: Administrator (full access)

### Cashier Account
- **Employee ID**: `CASHIER001`
- **Username**: `cashier`
- **Password**: `admin123`
- **Role**: Cashier (limited access)

**⚠️ Important**: Change these default passwords after first login!

## Usage

### For Cashiers

1. **Login**: Use your credentials to access the system
2. **Menu**: Browse items by category and add to cart
3. **Orders**: Select table, customer name, and order type
4. **Payment**: Choose payment method (Cash, Card, or GCash)
5. **Complete**: Print receipt and complete the order

### For Administrators

1. **Access Settings**: Manage users and configure business settings
2. **Manage Menu**: Add, edit, or delete menu items and categories
3. **View Reports**: Access sales reports and analytics
4. **Track Orders**: Monitor all orders and table status
5. **User Management**: Add or remove staff accounts

## File Structure

```
coffee-at-yellow-hauz/
├── database.sql          # Database schema and default data
├── db.php                # Database connection and helper functions
├── index.php             # Login page
├── menu.php              # Main POS interface
├── table.php             # Table management
├── ticket.php            # Order tickets
├── items.php             # Menu item management
├── sales.php             # Sales reports
├── analysis.php          # Product analytics
├── settings.php          # System settings
├── logout.php            # Logout handler
├── api.php               # AJAX API endpoints
└── README.md             # This file
```

## API Endpoints

The system includes a RESTful API for AJAX operations:

- `GET api.php?action=get_menu_items` - Get menu items
- `GET api.php?action=get_categories` - Get categories
- `GET api.php?action=get_tables` - Get tables
- `POST api.php?action=add_to_cart` - Add item to cart
- `POST api.php?action=create_order` - Create new order
- `GET api.php?action=get_active_orders` - Get active orders
- `POST api.php?action=update_order_status` - Update order status

## Database Schema

### Users Table
- Stores staff accounts with authentication credentials
- Supports role-based access control

### Categories Table
- Menu categories (Coffee, Food, etc.)
- Custom icons and sorting

### Menu Items Table
- Product details with images and pricing
- Availability and best-seller flags

### Tables Table
- Physical table management
- Status tracking (available, occupied, reserved)

### Orders Table
- Customer orders with payment details
- Status tracking (pending, processing, completed)

### Order Items Table
- Line items for each order
- Quantity and pricing

### Settings Table
- System configuration
- Business information and preferences

## Security Features

- Password hashing using `password_hash()`
- Session-based authentication
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Role-based access control

## Customization

### Changing the Theme

The system uses Tailwind CSS with custom colors defined in each file. To customize:

1. Edit the `tailwind.config` object in any PHP file
2. Modify the `brand` color palette
3. Update the `vintage` colors for the paper look

### Adding New Menu Categories

1. Log in as Admin
2. Go to Settings → Manage Food Items
3. Click "Add Category"
4. Enter category name and icon (Font Awesome class)

### Modifying Tax Rate

1. Log in as Admin
2. Go to Settings
3. Update the "Tax Rate (%)" field
4. Click "Save Settings"

## Troubleshooting

### Database Connection Issues

If you see "Database connection failed":

1. Check `db.php` configuration
2. Verify MySQL is running
3. Ensure database credentials are correct
4. Check if the database `yellow_hauz_pos` exists

### Session Issues

If you're logged out frequently:

1. Check PHP session configuration
2. Verify session save path is writable
3. Check server time settings

### Images Not Displaying

If menu item images don't load:

1. Ensure image URLs are accessible
2. Check for CORS issues if using external images
3. Verify image URLs in the database

## Support

For issues or questions:

1. Check this README first
2. Review the database schema in `database.sql`
3. Examine error logs in your web server
4. Verify PHP and MySQL versions meet requirements

## License

This project is provided as-is for use by Coffee at Yellow Hauz.

## Credits

- **Design**: Coffee at Yellow Hauz
- **Development**: PHP/MySQL POS System
- **UI Framework**: Tailwind CSS
- **Icons**: Font Awesome
- **Charts**: Chart.js

## Version History

- **v1.0.0** - Initial release with full POS functionality

---

**© 2024 Coffee at Yellow Hauz. All rights reserved.**
