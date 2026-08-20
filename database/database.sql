CREATE DATABASE IF NOT EXISTS ssrini_handicrafts
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ssrini_handicrafts;


-- 1. ADMINS


CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager') NOT NULL DEFAULT 'admin',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
) ENGINE=InnoDB;

-- 2. CUSTOMERS

CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    pincode VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_customer_phone (phone),
    INDEX idx_customer_email (email)
) ENGINE=InnoDB;

-- 3. CATEGORIES
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR (120) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 4. PRODUCTS 

CREATE TABLE IF NOT EXISTS products(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    product_code VARCHAR (100) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price  DECIMAL (10,2) NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_product_category (category_id),
    INDEX idx_product_name (name),
    INDEX idx_product_code (product_code),

    CONSTRAINT fk_products_category
    FOREIGN KEY (category_id)
    REFERENCES categories(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;


-- 5. ORDERS

CREATE TABLE IF NOT EXISTS orders(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,


    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_method ENUM(
        'cod',
        'online'
    ) NOT NULL DEFAULT 'cod',

    payment_status ENUM(
        'pending',
        'paid',
        'failed'
    ) NOT NULL DEFAULT 'pending',
    order_status ENUM(
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    shipping_address TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order_customer (customer_id),
    INDEX idx_order_status (order_status),
    INDEX idx_order_created (created_at),

    CONSTRAINT fk_orders_customer
    FOREIGN KEY (customer_id)
    REFERENCES customers(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
)   ENGINE=InnoDB;

-- 6. ORDER ITEMS

CREATE TABLE IF NOT EXISTS order_items ( 
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,

    product_name VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id),

    CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. INVOICES 

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NULL,

    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_method ENUM(
        'cod',
        'online'
    )
    NOT NULL DEFAULT 'cod',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,


    INDEX idx_invoice_order (order_id),
    INDEX idx_invoice_customer (customer_id),

    CONSTRAINT fk_invoices_order
    FOREIGN KEY (order_id)
    REFERENCES orders(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

    CONSTRAINT fk_invoices_customer
    FOREIGN KEY (customer_id)
    REFERENCES customers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;


-- 8. REVIEWS

CREATE TABLE IF NOT EXISTS reviews(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    product_id INT UNSIGNED NULL,

    rating INT UNSIGNED NOT NULL,
    review_text TEXT NULL,

    status ENUM(
        'pending',
        'approved',
        'hidden'
    ) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON  UPDATE CURRENT_TIMESTAMP,


    INDEX idx_reviews_customer(customer_id),
    INDEX idx_reviews_products (product_id),
    INDEX idx_reviews_status (status),

    CONSTRAINT chk_review_rating
    CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT fk_reviews_customer
    FOREIGN KEY (customer_id)
    REFERENCES customers(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
    CONSTRAINT fk_reviews_product
    FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON UPDATE CASCADE 
    ON DELETE SET NULL 
    ) ENGINE=InnoDB;

-- 9. NOTIFICATIONS

    CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,

    reference_id INT UNSIGNED NULL,

    is_read BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notifications_read (is_read),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

-- 10. ACTIVITY LOGS

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    admin_id INT UNSIGNED NULL,

    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activity_admin (admin_id),
    INDEX idx_activity_created (created_at),

    CONSTRAINT fk_activity_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. FILTER SETTINGS

CREATE TABLE IF NOT EXISTS filter_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    min_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_price DECIMAL(10,2) NOT NULL DEFAULT 100000.00,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- 12. FRONT PAGE CONTENT 

CREATE TABLE IF NOT EXISTS front_page_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    hero_image VARCHAR(255) NULL,

    catchy_headline VARCHAR(255) NULL,
    store_headline VARCHAR(255) NULL,

    introduction_headline VARCHAR(255) NULL,
    introduction_description TEXT NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 13. ABOUT CONTENT 

CREATE TABLE IF NOT EXISTS about_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    company_description TEXT NULL,

    facebook_url VARCHAR(255) NULL,
    instagram_url VARCHAR(255) NULL,
    twitter_url VARCHAR(255) NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 14. STORE SETTINGS


CREATE TABLE IF NOT EXISTS store_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    store_name VARCHAR(150) NOT NULL DEFAULT 'Ssrini Handicrafts',

    email VARCHAR(150) NULL,
    phone VARCHAR(20) NULL,

    address TEXT NULL,

    whatsapp_number VARCHAR(20) NULL,

    currency VARCHAR(10) NOT NULL DEFAULT 'INR',

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

