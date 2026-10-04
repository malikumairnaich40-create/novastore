-- Modern Multi-Category E-Commerce Platform Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.x / MariaDB
-- Default Database: ecommerce_db

CREATE DATABASE IF NOT EXISTS `ecommerce_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ecommerce_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'United States',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `categories`
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-layer-group',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categories_slug` (`slug`),
  KEY `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `products`
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `sku` VARCHAR(60) DEFAULT NULL,
  `short_description` VARCHAR(350) DEFAULT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `sale_price` DECIMAL(10,2) DEFAULT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NOT NULL,
  `gallery_images` TEXT DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_products_slug` (`slug`),
  UNIQUE KEY `idx_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_price` (`price`),
  KEY `idx_products_featured` (`featured`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cart`
-- --------------------------------------------------------
CREATE TABLE `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(100) NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_session` (`session_id`),
  KEY `idx_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `orders`
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cod', 'card', 'paypal') NOT NULL DEFAULT 'cod',
  `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
  `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `shipping_name` VARCHAR(150) NOT NULL,
  `shipping_email` VARCHAR(150) NOT NULL,
  `shipping_phone` VARCHAR(30) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `shipping_state` VARCHAR(100) NOT NULL,
  `shipping_zip` VARCHAR(20) NOT NULL,
  `order_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`order_status`),
  KEY `idx_orders_payment_status` (`payment_status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `order_items`
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `product_image` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_items_order` (`order_id`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- DUMMY DATA SEEDING
-- --------------------------------------------------------

-- Default Password for Admin: admin123 ($2y$10$p3oppyexnplZni4/ET4sUOc3yXox2rs4r0EmTkkSaYT3llVTmynyi)
-- Default Password for Customers: customer123 ($2y$10$QZRxqWwhA6ajwkYXWqZ.punew9iUB./i8p4Y4NA3WSttm8ifxKIjS)

INSERT INTO `users` (`id`, `role`, `name`, `email`, `password_hash`, `phone`, `address`, `city`, `state`, `zip_code`, `country`) VALUES
(1, 'admin', 'Store Administrator', 'admin@ecommerce.com', '$2y$10$p3oppyexnplZni4/ET4sUOc3yXox2rs4r0EmTkkSaYT3llVTmynyi', '+1 (555) 019-2834', '742 Evergreen Terrace', 'Springfield', 'Oregon', '97477', 'United States'),
(2, 'customer', 'Alex Johnson', 'customer@ecommerce.com', '$2y$10$QZRxqWwhA6ajwkYXWqZ.punew9iUB./i8p4Y4NA3WSttm8ifxKIjS', '+1 (555) 432-8765', '124 Conch Street', 'Bikini Bottom', 'California', '90210', 'United States'),
(3, 'customer', 'Sarah Miller', 'sarah@ecommerce.com', '$2y$10$QZRxqWwhA6ajwkYXWqZ.punew9iUB./i8p4Y4NA3WSttm8ifxKIjS', '+1 (555) 890-1234', '456 Grand Avenue Apt 3B', 'New York', 'New York', '10001', 'United States');

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `icon`, `status`) VALUES
(1, 'Electronics & Gadgets', 'electronics-gadgets', 'Latest smartphones, noise-cancelling headphones, laptops, and smart wearables.', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 'fa-laptop', 'active'),
(2, 'Fashion & Apparel', 'fashion-apparel', 'Premium designer clothing, streetwear, casual essentials, and accessories.', 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=600&auto=format&fit=crop&q=80', 'fa-shirt', 'active'),
(3, 'Home & Living', 'home-living', 'Modern ergonomic furniture, luxury decor, ambient lighting, and kitchenware.', 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&auto=format&fit=crop&q=80', 'fa-couch', 'active'),
(4, 'Beauty & Personal Care', 'beauty-personal-care', 'Organic skincare serums, botanical fragrances, and personal grooming tools.', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&auto=format&fit=crop&q=80', 'fa-spa', 'active'),
(5, 'Sports & Fitness', 'sports-fitness', 'Pro workout gear, outdoor adventure essentials, and athletic smart trackers.', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=600&auto=format&fit=crop&q=80', 'fa-dumbbell', 'active'),
(6, 'Watches & Jewelry', 'watches-jewelry', 'Luxury automatic timepieces, minimalist chronograph watches, and fine jewelry.', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&auto=format&fit=crop&q=80', 'fa-clock', 'active');

-- Seed Products
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `sku`, `short_description`, `description`, `price`, `sale_price`, `stock_quantity`, `featured`, `image`, `gallery_images`, `status`) VALUES
(1, 1, 'Aura ANC Wireless Headphones', 'aura-anc-wireless-headphones', 'TECH-ANC-001', 'Active noise cancellation with 40-hour ultra battery life and spatial audio.', 'Experience world-class acoustic clarity with the Aura ANC Wireless Headphones. Engineered with custom 45mm neodymium drivers, advanced hybrid active noise cancellation, and plush memory-foam ear cushions designed for all-day comfort. Features Bluetooth 5.3, multi-device multipoint connectivity, and USB-C fast charging that provides 5 hours of playback in just 10 minutes.', 249.99, 199.99, 35, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&auto=format&fit=crop&q=80","https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=800&auto=format&fit=crop&q=80"]', 'active'),

(2, 1, 'Pulse Pro Smart Fitness Watch', 'pulse-pro-smart-fitness-watch', 'TECH-WCH-002', 'Always-on AMOLED display with heart rate, SpO2, GPS tracking, and 7-day battery.', 'The Pulse Pro Smart Fitness Watch is your ultimate companion for peak performance and daily wellness. Featuring an aerospace-grade titanium bezel, vibrant 1.4-inch AMOLED display, integrated dual-frequency GPS, and 50+ sport tracking modes. 5ATM water resistance allows deep swimming and water sports without worry.', 179.99, 149.99, 22, 1, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=800&auto=format&fit=crop&q=80"]', 'active'),

(3, 1, 'SonicBoom Portable Bluetooth Speaker', 'sonicboom-portable-bluetooth-speaker', 'TECH-SPK-003', '360-degree immersive sound, IPX7 waterproof rating, and 24-hour battery.', 'Take your music everywhere with the SonicBoom. Packed with dual passive radiators and precision tweeters, it delivers crisp highs and thumping bass. Completely waterproof and dustproof, making it perfect for pool parties, beach trips, and mountain hikes.', 89.99, 69.99, 45, 0, 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800&auto=format&fit=crop&q=80"]', 'active'),

(4, 2, 'Nordic Minimalist Wool Blend Overcoat', 'nordic-minimalist-wool-blend-overcoat', 'FASH-COT-101', 'Tailored fit premium wool-blend coat designed for effortless winter elegance.', 'Crafted from a heavyweight Italian wool-cashmere blend, this tailored minimalist overcoat offers unparalleled warmth and timeless sophisticated style. Features notch lapels, structured shoulders, deep welt pockets, and a silken inner lining for smooth layering over suits or knitwear.', 289.00, 229.00, 15, 1, 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&auto=format&fit=crop&q=80"]', 'active'),

(5, 2, 'Urban Velocity Streetwear Hoodie', 'urban-velocity-streetwear-hoodie', 'FASH-HOD-102', '450 GSM heavyweight French terry cotton with relaxed drop-shoulder cut.', 'Crafted from 100% pre-shrunk organic ring-spun cotton, this heavyweight French terry hoodie delivers the perfect streetwear drape. Features double-layered hood, ribbed cuffs and hem, and a discreet kangaroo pocket.', 79.50, NULL, 60, 0, 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1509967419530-da38b4704bc6?w=800&auto=format&fit=crop&q=80"]', 'active'),

(6, 2, 'AeroStride Lightweight Knit Runners', 'aerostride-lightweight-knit-runners', 'FASH-SHS-103', 'Breathable engineered knit upper with cloud-cushion responsive EVA sole.', 'Step into weightless comfort with AeroStride Runners. Built with a seamless breathable mesh upper, energy-returning foam midsole, and high-traction rubber pods for superior grip on asphalt or track.', 119.00, 89.00, 18, 1, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=800&auto=format&fit=crop&q=80"]', 'active'),

(7, 3, 'ErgoLuxe Mesh Executive Office Chair', 'ergoluxe-mesh-executive-office-chair', 'HOME-CHR-201', 'Dynamic lumbar support, 4D adjustable armrests, and breathable high-density mesh.', 'Engineered for 12+ hour sitting sessions, the ErgoLuxe provides responsive spinal alignment, synchronous tilt mechanism with 3 locking angles, pneumatic height adjustment, and smooth-glide caster wheels.', 349.99, 299.99, 12, 1, 'https://images.unsplash.com/photo-1580481077195-c9f1388b1f59?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1505797149-43b0069ec26b?w=800&auto=format&fit=crop&q=80"]', 'active'),

(8, 3, 'AromaGlow Ceramic Ultrasonic Diffuser', 'aromaglow-ceramic-ultrasonic-diffuser', 'HOME-DIF-202', 'Handcrafted ceramic stone diffuser with warm ambient LED light and quiet mist.', 'Transform any room into a serene sanctuary. Ultrasonic vibrations disperse essential oils without heat, preserving their natural therapeutic properties. Features auto-shutoff safety timer and gentle ambient mood lighting.', 65.00, 49.00, 40, 0, 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=800&auto=format&fit=crop&q=80"]', 'active'),

(9, 4, 'Botanical Hydrating Face Serum', 'botanical-hydrating-face-serum', 'BEAU-SRM-301', 'Pure hyaluronic acid, Vitamin C, and rosehip oil for glowing radiant skin.', 'Infused with cold-pressed organic botanicals and triple-molecular hyaluronic acid, this weightless serum penetrates deeply to plump fine lines, brighten dull complexions, and lock in 24-hour hydration.', 48.00, 38.00, 85, 1, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1608248597359-bb4f4f728c31?w=800&auto=format&fit=crop&q=80"]', 'active'),

(10, 5, 'HexPro Rubber Grip Dumbbells (Pair 20kg)', 'hexpro-rubber-grip-dumbbells-pair', 'SPRT-DMB-401', 'Commercial-grade anti-roll cast iron hex dumbbells with knurled steel grip.', 'Built for intense home gym sessions or commercial studios. The heavy-duty rubber coating protects your floors and minimizes noise, while the contoured chrome handles ensure a slip-free secure grip.', 139.99, NULL, 4, 0, 'https://images.unsplash.com/photo-1586401100295-7a8096fd231a?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=800&auto=format&fit=crop&q=80"]', 'active'),

(11, 6, 'Vanguard Chronograph Men\'s Leather Watch', 'vanguard-chronograph-mens-leather-watch', 'WAT-CHR-501', 'Sapphire crystal glass, Japanese quartz movement, genuine top-grain leather strap.', 'An icon of masculine craftsmanship. The Vanguard Chronograph features a 42mm surgical-grade stainless steel case, anti-reflective scratch-resistant sapphire crystal, 3 sub-dials, date window, and 50m water resistance.', 215.00, 169.00, 3, 1, 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=800&auto=format&fit=crop&q=80"]', 'active'),

(12, 1, 'HyperVision 4K Ultra Webcam', 'hypervision-4k-ultra-webcam', 'TECH-CAM-004', '4K 60FPS Sony sensor with AI autofocus and dual noise-canceling stereo mics.', 'Elevate your streaming, remote meetings, and content creation. Features auto-light correction, magnetic privacy shutter, and plug-and-play USB 3.0 compatibility with Windows, Mac, and Linux.', 129.99, 99.99, 25, 0, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=800&auto=format&fit=crop&q=80', '["https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=800&auto=format&fit=crop&q=80"]', 'active');

-- Seed Sample Orders
INSERT INTO `orders` (`id`, `user_id`, `order_number`, `subtotal`, `tax_amount`, `shipping_fee`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `shipping_name`, `shipping_email`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_zip`, `order_notes`, `created_at`) VALUES
(1, 2, 'ORD-20261001-8492', 349.98, 28.00, 0.00, 0.00, 377.98, 'card', 'paid', 'delivered', 'Alex Johnson', 'customer@ecommerce.com', '+1 (555) 432-8765', '124 Conch Street', 'Bikini Bottom', 'California', '90210', 'Please ring doorbell upon delivery.', '2026-09-28 14:20:00'),
(2, 3, 'ORD-20261002-3921', 199.99, 16.00, 9.99, 0.00, 225.98, 'cod', 'pending', 'processing', 'Sarah Miller', 'sarah@ecommerce.com', '+1 (555) 890-1234', '456 Grand Avenue Apt 3B', 'New York', 'New York', '10001', 'Leave package with doorman.', '2026-10-02 10:15:00'),
(3, 2, 'ORD-20261004-7128', 169.00, 13.52, 0.00, 0.00, 182.52, 'card', 'paid', 'pending', 'Alex Johnson', 'customer@ecommerce.com', '+1 (555) 432-8765', '124 Conch Street', 'Bikini Bottom', 'California', '90210', '', '2026-10-04 06:45:00');

-- Seed Order Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_image`, `price`, `quantity`, `total_price`) VALUES
(1, 1, 1, 'Aura ANC Wireless Headphones', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80', 199.99, 1, 199.99),
(2, 1, 2, 'Pulse Pro Smart Fitness Watch', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=80', 149.99, 1, 149.99),
(3, 2, 1, 'Aura ANC Wireless Headphones', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80', 199.99, 1, 199.99),
(4, 3, 11, 'Vanguard Chronograph Men\'s Leather Watch', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=800&auto=format&fit=crop&q=80', 169.00, 1, 169.00);
