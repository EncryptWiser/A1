-- STOCKer Database
-- BCA Semester V Mini Project
-- Run this in phpMyAdmin: Import > Choose File > STOCKer_erp/database.sql

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS `stocker_erp`;
CREATE DATABASE `stocker_erp` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `stocker_erp`;

-- Users
CREATE TABLE `users` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      varchar(60)  NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name`     varchar(120) NOT NULL,
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE `categories` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at`  timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers
CREATE TABLE `suppliers` (
  `id`             int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email`          varchar(150) DEFAULT NULL,
  `phone`          varchar(30)  DEFAULT NULL,
  `address`        varchar(500) DEFAULT NULL,
  `created_at`     timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products
CREATE TABLE `products` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku`           varchar(50)      NOT NULL,
  `name`          varchar(150)     NOT NULL,
  `category_id`   int(10) UNSIGNED DEFAULT NULL,
  `supplier_id`   int(10) UNSIGNED DEFAULT NULL,
  `description`   varchar(1000)    DEFAULT NULL,
  `cost_price`    decimal(12,2)    NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2)    NOT NULL DEFAULT 0.00,
  `quantity`      int(10) UNSIGNED NOT NULL DEFAULT 0,
  `reorder_level` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `image`         varchar(300)     DEFAULT NULL,
  `is_archived`   tinyint(1)       NOT NULL DEFAULT 0,
  `created_at`    timestamp        NOT NULL DEFAULT current_timestamp(),
  `updated_at`    timestamp        NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sku` (`sku`),
  KEY `idx_product_name` (`name`),
  KEY `idx_product_qty` (`quantity`),
  KEY `fk_product_category` (`category_id`),
  KEY `fk_product_supplier` (`supplier_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock Movements
CREATE TABLE `stock_movements` (
  `id`            bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    int(10) UNSIGNED    NOT NULL,
  `movement_type` enum('IN','OUT')    NOT NULL,
  `quantity`      int(10) UNSIGNED    NOT NULL,
  `notes`         varchar(500)        DEFAULT NULL,
  `created_by`    int(10) UNSIGNED    DEFAULT NULL,
  `created_at`    timestamp           NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_movement_product` (`product_id`),
  KEY `idx_movement_date` (`created_at`),
  KEY `fk_movement_user` (`created_by`),
  CONSTRAINT `fk_movement_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movement_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Demo admin account: admin / Admin@123
INSERT INTO `users` (`username`, `password_hash`, `full_name`)
VALUES ('admin', '$2y$10$Y/UrjjJCThqUP4IiEZ3cKOgaKSfTBqnCqGlirYEBd9E2b.pBSNVta', 'STOCKer Admin');

-- 5 Categories
INSERT INTO `categories` (`name`, `description`) VALUES
  ('Electronics',  'Electronic devices, peripherals and accessories'),
  ('Stationery',   'Office and school writing supplies'),
  ('Groceries',    'Packaged food and daily use consumables'),
  ('Furniture',    'Office chairs, desks and storage units'),
  ('Clothing',     'Uniforms, workwear and garments');

-- 5 Suppliers
INSERT INTO `suppliers` (`name`, `contact_person`, `email`, `phone`, `address`) VALUES
  ('Global Traders',    'Ramesh Kumar',  'ramesh@globaltraders.in',   '+91 98765 43210', '12 MG Road, Mumbai'),
  ('Tech Supply Co.',   'Priya Sharma',  'priya@techsupply.in',       '+91 91234 56789', '5 IT Park, Pune'),
  ('Office Essentials', 'Arjun Mehta',   'arjun@offessentials.in',    '+91 99887 76655', '8 Civil Lines, Delhi'),
  ('FreshMart Pvt Ltd', 'Sunita Verma',  'sunita@freshmart.in',       '+91 93456 12340', '22 Market Road, Ahmedabad'),
  ('Bright Stationery', 'Kabir Nair',    'kabir@brightstationery.in', '+91 87654 32109', '3 Paper Lane, Chennai');

-- 5 Products per category = 25 products
-- Electronics (category_id=1, supplier_id=2)
-- Stationery  (category_id=2, supplier_id=5)
-- Groceries   (category_id=3, supplier_id=4)
-- Furniture   (category_id=4, supplier_id=1)
-- Clothing    (category_id=5, supplier_id=3)
INSERT INTO `products` (`sku`, `name`, `category_id`, `supplier_id`, `description`, `cost_price`, `selling_price`, `quantity`, `reorder_level`) VALUES
  -- Electronics
  ('ELEC-001', 'USB-C Hub 7-in-1',        1, 2, '7-port USB-C hub with HDMI, SD card and USB 3.0 slots',     850.00,  1299.00,  35, 10),
  ('ELEC-002', 'Wireless Keyboard',        1, 2, 'Slim 2.4 GHz wireless keyboard with numeric pad',           600.00,   999.00,  22,  8),
  ('ELEC-003', 'Bluetooth Mouse',          1, 2, 'Ergonomic Bluetooth mouse, 1600 DPI, silent click',          450.00,   749.00,  18,  8),
  ('ELEC-004', '32GB USB Flash Drive',     1, 2, 'USB 3.0 flash drive, 32 GB, cap-less design',               220.00,   399.00,  60, 15),
  ('ELEC-005', 'HDMI Cable 1.8m',          1, 2, 'High-speed HDMI 2.0 cable, 4K support, 1.8 m',              150.00,   299.00,   7,  5),
  -- Stationery
  ('STAT-001', 'A4 Copier Paper (500 sh)', 2, 5, '75 GSM A4 white copier paper, 500 sheets per ream',         180.00,   250.00, 110, 20),
  ('STAT-002', 'Ballpoint Pen Set (10)',   2, 5, 'Blue ink ballpoint pens, pack of 10',                        55.00,    90.00, 180, 50),
  ('STAT-003', 'Sticky Notes 3x3 (5 Pads)',2, 5, 'Yellow self-stick notes, 100 sheets per pad, 5 pads',        95.00,   150.00,  75, 20),
  ('STAT-004', 'Stapler Heavy Duty',       2, 5, '24/6 heavy duty stapler with 1000 staples included',        320.00,   499.00,  30, 10),
  ('STAT-005', 'Whiteboard Marker Set',    2, 3, 'Dry-erase markers, 4 colours, chisel tip',                   85.00,   140.00,  50, 15),
  -- Groceries
  ('GROC-001', 'Refined Sugar 1 kg',       3, 4, 'White refined sugar, 1 kg sealed packet',                    42.00,    58.00,  70, 30),
  ('GROC-002', 'Basmati Rice 5 kg',        3, 4, 'Extra long grain basmati rice, 5 kg bag',                   310.00,   420.00,  45, 15),
  ('GROC-003', 'Refined Sunflower Oil 1L', 3, 4, 'Refined sunflower cooking oil, 1 litre pet bottle',         130.00,   175.00,  60, 20),
  ('GROC-004', 'Whole Wheat Atta 5 kg',    3, 4, 'Stone-ground whole wheat flour, 5 kg pack',                 220.00,   295.00,  38, 15),
  ('GROC-005', 'Toor Dal 1 kg',            3, 4, 'Split pigeon peas (toor dal), 1 kg sealed pack',             95.00,   130.00,  55, 20),
  -- Furniture
  ('FURN-001', 'Ergonomic Office Chair',   4, 1, 'Mesh back office chair with lumbar support and armrests',  3200.00,  4999.00,   8,  3),
  ('FURN-002', 'Wooden Study Table',       4, 1, '120x60 cm wooden study/office desk with cable slot',       4500.00,  6999.00,   5,  2),
  ('FURN-003', 'Steel 3-Drawer Pedestal',  4, 1, 'Mobile steel pedestal, 3 drawers with key lock',           2200.00,  3499.00,   6,  2),
  ('FURN-004', 'Bookshelf 5-Tier',         4, 1, 'Engineered wood 5-tier bookshelf, 180x80 cm',              2800.00,  4299.00,   4,  2),
  ('FURN-005', 'Whiteboard 4x3 ft',        4, 1, 'Magnetic dry-erase whiteboard with aluminium frame',       1800.00,  2799.00,   3,  2),
  -- Clothing
  ('CLTH-001', 'Office Formal Shirt (M)',  5, 3, 'White full-sleeve formal shirt, cotton blend, size M',      380.00,   599.00,  25, 10),
  ('CLTH-002', 'Office Formal Shirt (L)',  5, 3, 'White full-sleeve formal shirt, cotton blend, size L',      380.00,   599.00,  20, 10),
  ('CLTH-003', 'Black Formal Trousers (M)',5, 3, 'Slim-fit formal trousers, polycotton, size M',              520.00,   799.00,  15,  8),
  ('CLTH-004', 'Black Formal Trousers (L)',5, 3, 'Slim-fit formal trousers, polycotton, size L',              520.00,   799.00,  12,  8),
  ('CLTH-005', 'Safety Jacket Hi-Vis',     5, 3, 'High-visibility yellow safety jacket with reflective tape', 350.00,   549.00,   9,  5);

-- Stock movements: opening IN for all 25 products + realistic OUT transactions
INSERT INTO `stock_movements` (`product_id`, `movement_type`, `quantity`, `notes`, `created_by`) VALUES
  -- Opening stock (IN)
  ( 1, 'IN',  35, 'Opening stock',                   1),
  ( 2, 'IN',  22, 'Opening stock',                   1),
  ( 3, 'IN',  18, 'Opening stock',                   1),
  ( 4, 'IN',  60, 'Opening stock',                   1),
  ( 5, 'IN',  10, 'Opening stock',                   1),
  ( 6, 'IN', 110, 'Opening stock',                   1),
  ( 7, 'IN', 180, 'Opening stock',                   1),
  ( 8, 'IN',  75, 'Opening stock',                   1),
  ( 9, 'IN',  30, 'Opening stock',                   1),
  (10, 'IN',  50, 'Opening stock',                   1),
  (11, 'IN',  70, 'Opening stock',                   1),
  (12, 'IN',  45, 'Opening stock',                   1),
  (13, 'IN',  60, 'Opening stock',                   1),
  (14, 'IN',  38, 'Opening stock',                   1),
  (15, 'IN',  55, 'Opening stock',                   1),
  (16, 'IN',   8, 'Opening stock',                   1),
  (17, 'IN',   5, 'Opening stock',                   1),
  (18, 'IN',   6, 'Opening stock',                   1),
  (19, 'IN',   4, 'Opening stock',                   1),
  (20, 'IN',   3, 'Opening stock',                   1),
  (21, 'IN',  25, 'Opening stock',                   1),
  (22, 'IN',  20, 'Opening stock',                   1),
  (23, 'IN',  15, 'Opening stock',                   1),
  (24, 'IN',  12, 'Opening stock',                   1),
  (25, 'IN',   9, 'Opening stock',                   1),
  -- Restock purchases (IN)
  ( 1, 'IN',  10, 'Restock — Purchase Order #101',   1),
  ( 6, 'IN',  50, 'Restock — Purchase Order #102',   1),
  (11, 'IN',  30, 'Restock — Purchase Order #103',   1),
  (15, 'IN',  20, 'Restock — Purchase Order #104',   1),
  -- Sales / issue (OUT)
  ( 1, 'OUT',  8, 'Issued to IT dept',               1),
  ( 2, 'OUT',  4, 'Sold to customer — Invoice #201', 1),
  ( 3, 'OUT',  5, 'Sold to customer — Invoice #202', 1),
  ( 4, 'OUT', 15, 'Sold to customer — Invoice #203', 1),
  ( 5, 'OUT',  3, 'Issued to conference room',       1),
  ( 6, 'OUT', 20, 'Monthly office consumption',      1),
  ( 7, 'OUT', 30, 'Sold to customer — Invoice #204', 1),
  ( 8, 'OUT', 10, 'Issued to admin dept',            1),
  (11, 'OUT', 10, 'Canteen monthly issue',            1),
  (12, 'OUT',  8, 'Sold to customer — Invoice #205', 1),
  (13, 'OUT', 10, 'Canteen monthly issue',            1),
  (16, 'OUT',  1, 'Sold to customer — Invoice #206', 1),
  (21, 'OUT',  5, 'Staff uniform issue',              1),
  (22, 'OUT',  3, 'Staff uniform issue',              1),
  (25, 'OUT',  2, 'Issued to warehouse staff',        1);
