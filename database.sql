CREATE DATABASE IF NOT EXISTS yellow_hauz_pos;
USE yellow_hauz_pos;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('cashier', 'admin') NOT NULL DEFAULT 'cashier',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-solid fa-utensils',
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu Items Table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(500),
    temperature ENUM('hot', 'iced', 'both', 'blended iced') DEFAULT 'both',
    is_best_seller BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    quantity INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_name (name),
    INDEX idx_price (price),
    INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tables Table (for table management)
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT UNIQUE NOT NULL,
    capacity INT DEFAULT 4,
    status ENUM('available', 'occupied', 'reserved', 'cleaning') DEFAULT 'available',
    current_order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table_number (table_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    table_id INT NULL,
    customer_name VARCHAR(255),
    order_type ENUM('dine_in', 'take_away', 'delivery') NOT NULL DEFAULT 'dine_in',
    payment_method ENUM('cash', 'card', 'gcash') NOT NULL DEFAULT 'cash',
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5, 2) DEFAULT 12.00,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    cashier_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_cashier_id (cashier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_menu_item_id (menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Data

-- Insert Default Admin User (password: admin123)
INSERT INTO users (employee_id, username, password, full_name, role) VALUES
('ADMIN001', 'admin', 'admin123', 'System Administrator', 'admin'),
('CASHIER001', 'cashier', 'admin123', 'Sheila Mae Aledro', 'cashier');

-- Insert Default Categories
INSERT INTO categories (name, icon, sort_order) VALUES
('Coffee', 'fa-solid fa-mug-saucer', 1),
('On The Rocks', 'fa-solid fa-glass-water', 2),
('Blended', 'fa-solid fa-blender', 3),
('Hot Drinks', 'fa-solid fa-fire', 4),
('Milk Tea', 'fa-solid fa-leaf', 5),
('Food', 'fa-solid fa-cookie', 6);

-- Insert Default Menu Items
INSERT INTO menu_items (category_id, name, description, price, image_url, temperature, is_best_seller, is_available, quantity) VALUES
(1, 'Espresso', 'Pure and intense espresso shot', 100.00, 'https://plus.unsplash.com/premium_photo-1669687924558-386bff1a0469?q=80&w=688&auto=format&fit=crop', 'hot', FALSE, TRUE, 50),
(1, 'Cappuccino', 'Espresso with steamed milk and foam', 170.00, 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=500&q=80', 'hot', TRUE, TRUE, 45),
(1, 'Spanish Latte', 'Sweet and creamy latte with condensed milk', 200.00, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=500&q=80', 'iced', FALSE, TRUE, 40),
(1, 'Flat White', 'Velvety smooth microfoam espresso', 170.00, 'https://images.unsplash.com/photo-1727080409436-356bdc609899?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'hot', FALSE, TRUE, 35),
(1, 'Macchiato', 'Espresso marked with a dollop of foam', 110.00, 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?w=500&q=80', 'hot', FALSE, TRUE, 30),
(1, 'Americano Hot', 'Espresso diluted with hot water', 140.00, 'https://images.unsplash.com/photo-1599659236990-34cc97c7e363?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'hot', FALSE, TRUE, 55),
(1, 'Cortado', 'Equal parts espresso and steamed milk', 150.00, 'https://images.unsplash.com/photo-1519532059956-a63a37af5deb?w=500&q=80', 'hot', FALSE, TRUE, 25),
(1, 'Latte', 'Smooth espresso with steamed milk', 170.00, 'https://images.unsplash.com/photo-1610889556528-9a770e32642f?w=200&q=80', 'hot', FALSE, TRUE, 60);

INSERT INTO menu_items (category_id, name, description, price, image_url, temperature, is_best_seller, is_available, quantity) VALUES
(2, 'Iced Caramel', 'Espresso sweetened with rich caramel syrup served over ice', 210.00, 'https://plus.unsplash.com/premium_photo-1669687924558-386bff1a0469?q=80&w=688&auto=format&fit=crop', 'iced', FALSE, TRUE, 40),
(2, 'Spanish Latte', 'Creamy espresso with condensed milk poured over ice', 210.00, 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=500&q=80', 'iced', TRUE, TRUE, 35),
(2, 'Iced Dirty Matcha', 'Earthy matcha latte with a bold espresso shot over ice', 260.00, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=500&q=80', 'iced', FALSE, TRUE, 25),
(2, 'Iced Yh Mocha', 'Espresso blended with chocolate and chilled milk over ice', 210.00, 'https://images.unsplash.com/photo-1727080409436-356bdc609899?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'iced', FALSE, TRUE, 30),
(2, 'Iced Almond Creme', 'Smooth espresso with almond syrup and creamy milk over ice', 210.00, 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?w=500&q=80', 'iced', FALSE, TRUE, 28),
(2, 'Iced Strawberry Matcha', 'Fruity strawberry and earthy matcha layered over ice', 210.00, 'https://images.unsplash.com/photo-1599659236990-34cc97c7e363?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'iced', FALSE, TRUE, 22),
(2, 'Iced Americano', 'Bold espresso diluted with cold water and served over ice', 150.00, 'https://images.unsplash.com/photo-1519532059956-a63a37af5deb?w=500&q=80', 'iced', FALSE, TRUE, 45),
(2, 'Iced Latte', 'Espresso and fresh milk poured over ice for a refreshing sip', 180.00, 'https://images.unsplash.com/photo-1610889556528-9a770e32642f?w=200&q=80', 'iced', FALSE, TRUE, 38),
(2, 'Iced Cappuccino', 'Chilled espresso with frothy milk foam served over ice', 180.00, 'https://images.unsplash.com/photo-1599659236990-34cc97c7e363?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'iced', FALSE, TRUE, 32),
(2, 'Iced Nutty Coffee', 'Espresso with a rich nutty flavor served cold over ice', 210.00, 'https://images.unsplash.com/photo-1519532059956-a63a37af5deb?w=500&q=80', 'iced', FALSE, TRUE, 26),
(2, 'Iced Toasted Mallows', 'Espresso with toasted marshmallow sweetness chilled over ice', 210.00, 'https://images.unsplash.com/photo-1610889556528-9a770e32642f?w=200&q=80', 'iced', FALSE, TRUE, 20),
(2, 'Milk Coffee Jelly', 'Smooth coffee jelly cubes in sweetened milk for a fun chewy treat', 220.00, 'https://images.unsplash.com/photo-1610889556528-9a770e32642f?w=200&q=80', 'iced', FALSE, TRUE, 18),
(2, 'Iced Dark Chocolate', 'Rich dark chocolate blended with espresso and chilled milk over ice', 210.00, 'https://images.unsplash.com/photo-1610889556528-9a770e32642f?w=200&q=80', 'iced', FALSE, TRUE, 24);

INSERT INTO menu_items (category_id, name, description, price, image_url, temperature, is_best_seller, is_available, quantity) VALUES
(3, 'Coffee Tea or Me', 'A delightful ice cream blend of coffee and tea swirled together over ice', 230.00, 'https://plus.unsplash.com/premium_photo-1669687924558-386bff1a0469?q=80&w=688&auto=format&fit=crop', 'blended iced', FALSE, TRUE, 15),
(3, 'Coffeecat', 'A playful ice cream blended coffee with a smooth and sweet creamy finish', 230.00, 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=500&q=80', 'blended iced', TRUE, TRUE, 20),
(3, 'Coffeelandia', 'A dreamy ice cream blended coffee with rich and velvety creamy layers', 230.00, 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=500&q=80', 'blended iced', FALSE, TRUE, 18),
(3, 'Coffeefornication', 'An indulgent ice cream blended coffee loaded with bold and irresistible flavors', 230.00, 'https://images.unsplash.com/photo-1727080409436-356bdc609899?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'blended iced', FALSE, TRUE, 12),
(3, 'Coffeemate', 'A smooth and creamy ice cream blended coffee perfect for any time of day', 230.00, 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?w=500&q=80', 'blended iced', FALSE, TRUE, 16),
(3, 'Coffeeright', 'A perfectly balanced ice cream blended coffee that hits all the right notes', 230.00, 'https://images.unsplash.com/photo-1599659236990-34cc97c7e363?fm=jpg&q=60&w=3000&auto=format&fit=crop', 'blended iced', FALSE, TRUE, 14),
(3, 'Coffeeteria', 'A bold and refreshing ice cream blended coffee served chilled to perfection', 230.00, 'https://images.unsplash.com/photo-1519532059956-a63a37af5deb?w=500&q=80', 'blended iced', FALSE, TRUE, 22);


-- Insert Default Tables
INSERT INTO tables (table_number, capacity, status) VALUES
(1, 4, 'available'),
(2, 4, 'available'),
(3, 6, 'available'),
(4, 4, 'available'),
(5, 2, 'available'),
(6, 8, 'available'),
(7, 4, 'available'),
(8, 4, 'available');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('tax_rate', '12', 'number', 'Default tax rate percentage'),
('currency', 'PHP', 'string', 'Currency symbol'),
('shop_name', 'Coffee at Yellow Hauz', 'string', 'Shop name for receipts'),
('shop_address', 'Yellow Hauz, Philippines', 'string', 'Shop address'),
('shop_phone', '+63 912 345 6789', 'string', 'Shop contact number'),
('receipt_footer', 'Thank you for visiting Coffee at Yellow Hauz!', 'string', 'Footer message for receipts'),
('business_hours', '07:00-22:00', 'string', 'Operating hours');
