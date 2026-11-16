-- ============================================================
-- Yogitech full database: schema + demo data
-- Import in phpMyAdmin (Import tab) or: mysql -u root < yogitech.sql
-- Logins: lynsaldevar@gmail.com / LynSeller123 (store), buyer@yogitech.test / password123 (demo shopper)
-- Includes tables through migration 013 (payments, product videos).
-- ============================================================
CREATE DATABASE IF NOT EXISTS `yogitech` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `yogitech`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`,`product_id`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `api_slug` varchar(80) DEFAULT NULL COMMENT 'Matching DummyJSON category',
  `icon` varchar(30) NOT NULL DEFAULT 'chip',
  `description` varchar(255) DEFAULT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Smartphones','smartphones','smartphones','phone','Flagships, mid-rangers and budget phones',1),
(2,'Laptops','laptops','laptops','laptop','Ultrabooks, gaming rigs and workstations',2),
(3,'Tablets','tablets','tablets','tablet','Tablets for drawing, study and streaming',3),
(4,'Accessories','accessories','mobile-accessories','plug','Chargers, earbuds, cases, watches and more',4);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `to_email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` varchar(40) NOT NULL,
  `driver` varchar(20) NOT NULL,
  `status` enum('sent','failed','logged') NOT NULL,
  `error` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_logs_user` (`user_id`),
  KEY `idx_email_logs_order` (`order_id`),
  CONSTRAINT `fk_email_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(190) NOT NULL,
  `ran_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'001_create_users_table.sql','2026-11-04 20:34:09'),
(2,'002_create_categories_table.sql','2026-11-04 20:34:09'),
(3,'003_create_products_table.sql','2026-11-04 20:34:09'),
(4,'004_create_cart_and_wishlist_tables.sql','2026-11-04 20:34:09'),
(5,'005_create_vouchers_table.sql','2026-11-04 20:34:09'),
(6,'006_create_orders_tables.sql','2026-11-04 20:34:09'),
(7,'007_create_reviews_table.sql','2026-11-04 20:34:09'),
(8,'008_create_email_logs_table.sql','2026-11-04 20:34:09'),
(9,'009_create_password_resets_table.sql','2026-11-11 19:55:20'),
(10,'010_add_voucher_limits.sql','2026-11-11 19:55:20'),
(11,'011_mask_names_in_tracking.sql','2026-11-11 19:55:20'),
(12,'012_create_payments_table.sql','2026-11-12 00:17:19'),
(13,'013_add_product_videos.sql','2026-11-12 00:17:19');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(180) NOT NULL COMMENT 'Snapshot at time of purchase',
  `thumbnail` varchar(500) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_order` (`order_id`),
  KEY `fk_items_product` (`product_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES
(1,1,2,'iPhone X','https://cdn.dummyjson.com/product-images/smartphones/iphone-x/thumbnail.webp',48909.00,1,48909.00),
(2,2,6,'Apple AirPods Max Silver','https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/thumbnail.webp',27799.00,1,27799.00),
(3,3,11,'Apple MacBook Pro 14 Inch Space Grey','https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/thumbnail.webp',106179.00,1,106179.00),
(4,4,15,'iPad Mini 2021 Starlight','https://cdn.dummyjson.com/product-images/tablets/ipad-mini-2021-starlight/thumbnail.webp',25039.00,1,25039.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `tracking_number` varchar(30) NOT NULL,
  `checkout_ref` varchar(30) NOT NULL COMMENT 'Groups orders placed in one checkout',
  `buyer_id` int(10) unsigned NOT NULL,
  `seller_id` int(10) unsigned NOT NULL,
  `status` enum('pending','confirmed','packed','shipped','in_transit','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cod','gcash','card') NOT NULL DEFAULT 'cod',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(12,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `voucher_code` varchar(30) DEFAULT NULL,
  `recipient_name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `region` enum('NCR','Luzon','Visayas','Mindanao') NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `courier` varchar(60) DEFAULT NULL,
  `estimated_from` date DEFAULT NULL,
  `estimated_to` date DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `idx_orders_buyer` (`buyer_id`,`created_at`),
  KEY `idx_orders_seller` (`seller_id`,`status`),
  KEY `idx_orders_checkout` (`checkout_ref`),
  CONSTRAINT `fk_orders_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,'YT261029-36098','YGTAFC4B5C98D','CK26110441E168',3,1,'delivered','cod','paid',48909.00,0.00,0.00,48909.00,NULL,'Juan Dela Cruz','09991234567','123 Sto. Rosario St.','Angeles City','Pampanga','Luzon','2009',NULL,'J&T Express','2026-11-06','2026-11-10','2026-10-30 16:30:00','2026-11-01 14:22:00',NULL,NULL,'2026-10-29 10:15:00','2026-11-01 14:22:00'),
(2,'YT261102-79996','YGTCD5837FF77','CK261104012426',3,1,'pending','gcash','paid',27799.00,0.00,0.00,27799.00,NULL,'Juan Dela Cruz','09991234567','123 Sto. Rosario St.','Angeles City','Pampanga','Luzon','2009',NULL,NULL,'2026-11-06','2026-11-10',NULL,NULL,NULL,NULL,'2026-11-02 19:05:00','2026-11-02 19:05:00'),
(3,'YT261102-40083','YGT14A05E1149','CK261104012426',3,1,'in_transit','gcash','paid',106179.00,0.00,0.00,106179.00,NULL,'Juan Dela Cruz','09991234567','123 Sto. Rosario St.','Angeles City','Pampanga','Luzon','2009',NULL,'LBC Express','2026-11-06','2026-11-10','2026-11-03 15:10:00',NULL,NULL,NULL,'2026-11-02 19:05:00','2026-11-04 18:34:11'),
(4,'YT261104-86430','YGT7B18487DA8','CK261104BBFF74',3,1,'pending','cod','unpaid',25039.00,0.00,0.00,25039.00,NULL,'Juan Dela Cruz','09991234567','123 Sto. Rosario St.','Angeles City','Pampanga','Luzon','2009',NULL,NULL,'2026-11-06','2026-11-10',NULL,NULL,NULL,NULL,'2026-11-04 19:54:11','2026-11-04 19:54:11');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `public_id` char(24) NOT NULL,
  `checkout_ref` varchar(30) NOT NULL,
  `buyer_id` int(10) unsigned NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `method` enum('gcash','card') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','authorized','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `gateway_ref` varchar(120) DEFAULT NULL,
  `payer` varchar(60) DEFAULT NULL,
  `failure` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_id` (`public_id`),
  KEY `idx_payments_checkout` (`checkout_ref`,`status`),
  KEY `fk_payments_buyer` (`buyer_id`),
  CONSTRAINT `fk_payments_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,'ee6403d5f6a99720b8462281','CK261104012426',3,'sandbox','gcash',133978.00,'paid','SBX-EE6403D5F6','GCash 0999â€¢â€¢â€¢4567',NULL,'2026-11-02 19:05:00','2026-11-02 19:05:00');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `external_id` int(10) unsigned DEFAULT NULL COMMENT 'DummyJSON product id when imported',
  `source` enum('manual','dummyjson') NOT NULL DEFAULT 'manual',
  `sku` varchar(60) DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `brand` varchar(80) DEFAULT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL COMMENT 'Selling price in PHP',
  `compare_price` decimal(12,2) DEFAULT NULL COMMENT 'Original price in PHP, shown struck through',
  `api_price_usd` decimal(10,2) DEFAULT NULL COMMENT 'Original USD price from the API',
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `thumbnail` varchar(500) DEFAULT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `video_url` varchar(500) DEFAULT NULL,
  `warranty` varchar(120) DEFAULT NULL,
  `return_policy` varchar(120) DEFAULT NULL,
  `specs` text DEFAULT NULL COMMENT 'JSON object of extra specs',
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sold_count` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('active','draft') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_seller_external` (`seller_id`,`external_id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status_price` (`status`,`price`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,1,1,121,'dummyjson','YT-0121','iPhone 13 Pro','Apple','Triple 12MP camera system, ProMotion 120Hz display and the A15 Bionic chip in a surgical-grade stainless steel frame.',58319.00,64349.00,1099.99,8,'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/1.webp\",\"https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/2.webp\"]','https://www.youtube.com/watch?v=72cAe_2LAFQ','1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.8 kg\",\"Tags\":\"smartphones\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(2,1,1,123,'dummyjson','YT-0123','iPhone X','Apple','Edge-to-edge Super Retina display with Face ID, dual cameras and wireless charging.',48909.00,52649.00,899.99,33,'https://cdn.dummyjson.com/product-images/smartphones/iphone-x/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/smartphones/iphone-x/1.webp\",\"https://cdn.dummyjson.com/product-images/smartphones/iphone-x/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.4 kg\",\"Tags\":\"smartphones\"}',4.67,3,1,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(3,1,1,128,'dummyjson','YT-0128','Realme X','Realme','Pop-up selfie camera, in-display fingerprint sensor and a 48MP rear camera on a budget.',15469.00,17549.00,299.99,41,'https://cdn.dummyjson.com/product-images/smartphones/realme-x/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/smartphones/realme-x/1.webp\",\"https://cdn.dummyjson.com/product-images/smartphones/realme-x/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.8 kg\",\"Tags\":\"smartphones\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(4,1,1,131,'dummyjson','YT-0131','Samsung Galaxy S10','Samsung','Infinity-O Dynamic AMOLED screen, ultrasonic fingerprint reader and triple-lens camera.',38739.00,40949.00,699.99,19,'https://cdn.dummyjson.com/product-images/smartphones/samsung-galaxy-s10/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/smartphones/samsung-galaxy-s10/1.webp\",\"https://cdn.dummyjson.com/product-images/smartphones/samsung-galaxy-s10/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.7 kg\",\"Tags\":\"smartphones\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(5,1,1,133,'dummyjson','YT-0133','Vivo V9','Vivo','24MP AI selfie camera, 6.3-inch FullView display and Snapdragon 626 performance.',16039.00,17549.00,299.99,4,'https://cdn.dummyjson.com/product-images/smartphones/vivo-v9/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/smartphones/vivo-v9/1.webp\",\"https://cdn.dummyjson.com/product-images/smartphones/vivo-v9/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"Low Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.2 kg\",\"Tags\":\"smartphones\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(6,1,4,100,'dummyjson','YT-0100','Apple AirPods Max Silver','Apple','Over-ear headphones with active noise cancellation, transparency mode and spatial audio.',27799.00,32169.00,549.99,58,'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/1.webp\",\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/2.webp\"]','https://www.youtube.com/watch?v=FXI_-OesT3A','1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.8 kg\",\"Tags\":\"mobile accessories\"}',4.50,2,1,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(7,1,4,103,'dummyjson','YT-0103','Apple MagSafe Battery Pack','Apple','Snaps onto MagSafe iPhones for extra battery on the go; charges up to 15W.',5469.00,5849.00,99.99,32,'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-magsafe-battery-pack/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-magsafe-battery-pack/1.webp\",\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-magsafe-battery-pack/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.7 kg\",\"Tags\":\"mobile accessories\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(8,1,4,104,'dummyjson','YT-0104','Apple Watch Series 4 Gold','Apple','Fall detection, ECG app and a 30% larger display in a gold aluminum case.',19629.00,20469.00,349.99,6,'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-watch-series-4-gold/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-watch-series-4-gold/1.webp\",\"https://cdn.dummyjson.com/product-images/mobile-accessories/apple-watch-series-4-gold/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"2 kg\",\"Tags\":\"mobile accessories\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(9,1,4,105,'dummyjson','YT-0105','Beats Flex Wireless Earphones','Beats','Magnetic earbuds with 12 hours of playback and Fast Fuel charging.',2489.00,2919.00,49.99,88,'https://cdn.dummyjson.com/product-images/mobile-accessories/beats-flex-wireless-earphones/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/mobile-accessories/beats-flex-wireless-earphones/1.webp\",\"https://cdn.dummyjson.com/product-images/mobile-accessories/beats-flex-wireless-earphones/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.2 kg\",\"Tags\":\"mobile accessories\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(10,1,4,110,'dummyjson','YT-0110','Selfie Lamp with iPhone','Generic','Clip-on LED ring light with three brightness levels for vlogs and video calls.',709.00,879.00,14.99,120,'https://cdn.dummyjson.com/product-images/mobile-accessories/selfie-lamp-with-iphone/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/mobile-accessories/selfie-lamp-with-iphone/1.webp\",\"https://cdn.dummyjson.com/product-images/mobile-accessories/selfie-lamp-with-iphone/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.7 kg\",\"Tags\":\"mobile accessories\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(11,1,2,78,'dummyjson','YT-0078','Apple MacBook Pro 14 Inch Space Grey','Apple','M-series silicon, Liquid Retina XDR display, up to 18 hours of battery life and a 1080p FaceTime camera.',106179.00,116999.00,1999.99,23,'https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/1.webp\",\"https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/2.webp\"]','https://www.youtube.com/watch?v=9tobL8U7dQo','1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.5 kg\",\"Tags\":\"laptops\"}',4.50,2,1,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(12,1,2,79,'dummyjson','YT-0079','Asus Zenbook Pro Dual Screen Laptop','Asus','ScreenPad Plus secondary touchscreen, OLED main display and RTX graphics for creators.',97189.00,105299.00,1799.99,45,'https://cdn.dummyjson.com/product-images/laptops/asus-zenbook-pro-dual-screen-laptop/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/laptops/asus-zenbook-pro-dual-screen-laptop/1.webp\",\"https://cdn.dummyjson.com/product-images/laptops/asus-zenbook-pro-dual-screen-laptop/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.8 kg\",\"Tags\":\"laptops\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(13,1,2,80,'dummyjson','YT-0080','Huawei Matebook X Pro','Huawei','3K FullView touchscreen, 13.9-inch body under 1.4 kg and pop-up webcam for privacy.',72319.00,81899.00,1399.99,3,'https://cdn.dummyjson.com/product-images/laptops/huawei-matebook-x-pro/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/laptops/huawei-matebook-x-pro/1.webp\",\"https://cdn.dummyjson.com/product-images/laptops/huawei-matebook-x-pro/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"Low Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.1 kg\",\"Tags\":\"laptops\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(14,1,2,82,'dummyjson','YT-0082','New DELL XPS 13 9300 Laptop','Dell','InfinityEdge 4-sided display, 11th Gen Intel Core and machined aluminum chassis.',82219.00,87749.00,1499.99,12,'https://cdn.dummyjson.com/product-images/laptops/new-dell-xps-13-9300-laptop/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/laptops/new-dell-xps-13-9300-laptop/1.webp\",\"https://cdn.dummyjson.com/product-images/laptops/new-dell-xps-13-9300-laptop/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.7 kg\",\"Tags\":\"laptops\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(15,1,3,159,'dummyjson','YT-0159','iPad Mini 2021 Starlight','Apple','8.3-inch Liquid Retina display, A15 Bionic, USB-C and Apple Pencil (2nd gen) support.',25039.00,29249.00,499.99,46,'https://cdn.dummyjson.com/product-images/tablets/ipad-mini-2021-starlight/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/tablets/ipad-mini-2021-starlight/1.webp\",\"https://cdn.dummyjson.com/product-images/tablets/ipad-mini-2021-starlight/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"1.7 kg\",\"Tags\":\"tablets\"}',4.50,2,1,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(16,1,3,160,'dummyjson','YT-0160','Samsung Galaxy Tab S8 Plus Grey','Samsung','12.4-inch Super AMOLED, S Pen included and DeX mode for a desktop-like workflow.',31979.00,35099.00,599.99,10,'https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-s8-plus-grey/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-s8-plus-grey/1.webp\",\"https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-s8-plus-grey/2.webp\"]','https://www.youtube.com/watch?v=Qn8eGdVO6fI','1 year warranty','30 days return policy','{\"Availability\":\"In Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"2 kg\",\"Tags\":\"tablets\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10'),
(17,1,3,161,'dummyjson','YT-0161','Samsung Galaxy Tab White','Samsung','Slim 10.5-inch tablet for streaming, notes and online classes with long battery life.',17999.00,20469.00,349.99,2,'https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-white/thumbnail.webp','[\"https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-white/1.webp\",\"https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-white/2.webp\"]',NULL,'1 year warranty','30 days return policy','{\"Availability\":\"Low Stock\",\"Minimum order\":\"1 pc(s)\",\"Dimensions\":\"7.1 × 14.6 × 0.8 cm\",\"Weight\":\"0.2 kg\",\"Tags\":\"tablets\"}',4.50,2,0,'active','2026-11-04 20:34:10','2026-11-04 20:34:10');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL for reviews imported from the API',
  `order_id` int(10) unsigned DEFAULT NULL,
  `reviewer_name` varchar(120) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_user_product` (`user_id`,`product_id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `fk_reviews_order` (`order_id`),
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,1,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(2,1,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(3,2,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(4,2,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(5,3,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(6,3,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(7,4,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(8,4,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(9,5,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(10,5,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(11,6,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(12,6,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(13,7,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(14,7,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(15,8,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(16,8,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(17,9,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(18,9,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(19,10,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(20,10,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(21,11,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(22,11,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(23,12,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(24,12,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(25,13,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(26,13,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(27,14,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(28,14,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(29,15,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(30,15,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(31,16,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(32,16,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(33,17,NULL,NULL,'Paolo Reyes',5,'Works exactly as described. Fast delivery!','2026-09-12 18:00:00'),
(34,17,NULL,NULL,'Maria Santos',4,'Great value for the price.','2026-09-20 22:30:00'),
(35,2,3,1,'Juan Dela Cruz',5,'Arrived in 3 days, sealed box and complete inclusions. Battery is great.','2026-11-02 20:34:11');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `shipment_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `status` varchar(30) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_events_order` (`order_id`,`created_at`),
  CONSTRAINT `fk_events_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `shipment_events` WRITE;
/*!40000 ALTER TABLE `shipment_events` DISABLE KEYS */;
INSERT INTO `shipment_events` VALUES
(1,1,'pending','Order placed','Waiting for Yogitech Official to confirm your order.',NULL,'2026-10-29 10:15:00'),
(2,1,'confirmed','Order confirmed','Yogitech Official confirmed your order and is preparing it.','Angeles City','2026-10-29 13:40:00'),
(3,1,'packed','Parcel packed','Your parcel is packed and waiting for courier pickup.','Angeles City','2026-10-30 09:10:00'),
(4,1,'shipped','Picked up by J&T Express','Your parcel is on its way. Tracking no. YGTAFC4B5C98D','J&T Express — Angeles City hub','2026-10-30 16:30:00'),
(5,1,'in_transit','In transit','Parcel arrived at a sorting facility.','J&T Express — San Fernando, Pampanga hub','2026-10-31 06:20:00'),
(6,1,'out_for_delivery','Out for delivery','A rider is bringing your parcel today. Keep your phone nearby.','Angeles City, Pampanga','2026-11-01 08:05:00'),
(7,1,'delivered','Delivered','Parcel delivered to the recipient.','Angeles City, Pampanga','2026-11-01 14:22:00'),
(8,1,'payment','Cash on delivery collected','Collected ₱48,909.00 from the recipient.',NULL,'2026-11-01 14:22:00'),
(9,2,'pending','Order placed','Waiting for Yogitech Official to confirm your order.',NULL,'2026-11-02 19:05:00'),
(10,2,'payment','Payment received','Paid ₱27,799.00 via GCash (sandbox).',NULL,'2026-11-02 19:05:00'),
(11,3,'pending','Order placed','Waiting for Neon Circuit PH to confirm your order.',NULL,'2026-11-02 19:05:00'),
(12,3,'payment','Payment received','Paid ₱106,179.00 via GCash (sandbox).',NULL,'2026-11-02 19:05:00'),
(13,3,'confirmed','Order confirmed','Neon Circuit PH confirmed your order and is preparing it.','Makati City','2026-11-02 20:00:00'),
(14,3,'packed','Parcel packed','Your parcel is packed and waiting for courier pickup.','Makati City','2026-11-03 09:30:00'),
(15,3,'shipped','Picked up by LBC Express','Your parcel is on its way. Tracking no. YGT14A05E1149','LBC Express — Makati City hub','2026-11-03 15:10:00'),
(16,3,'in_transit','In transit','Parcel arrived at a sorting facility.','LBC Express — Mexico, Pampanga hub','2026-11-04 18:34:11'),
(17,4,'pending','Order placed','Waiting for Neon Circuit PH to confirm your order.',NULL,'2026-11-04 19:54:11');
/*!40000 ALTER TABLE `shipment_events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('buyer','seller') NOT NULL DEFAULT 'buyer',
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `auth_version` int(10) unsigned NOT NULL DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL,
  `store_name` varchar(120) DEFAULT NULL,
  `store_description` varchar(500) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `region` enum('NCR','Luzon','Visayas','Mindanao') DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'seller','Yogi Admin','lynsaldevar@gmail.com','$2y$10$NbIMHm26sHy2wFgigXG/b.Xtjfx79Kmnj52zLIiWxZYcFq2AGGuOi',2,'09171234567','Yogitech Official','Authorized gadgets with local warranty.','MacArthur Highway','Angeles City','Pampanga','Luzon','2009',NULL,'2026-11-04 20:34:09','2026-11-04 20:34:09'),
(3,'buyer','Juan Dela Cruz','buyer@yogitech.test','$2y$10$DecDSJAhhwXX9i6nehysIO8IxOh9CiDBCNPDx9xMfth3K8t2F8RZ.',1,'09991234567',NULL,NULL,'123 Sto. Rosario St.','Angeles City','Pampanga','Luzon','2009',NULL,'2026-11-04 20:34:09','2026-11-04 20:34:09');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `voucher_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_redemptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `checkout_ref` varchar(30) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `released_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_ref` (`checkout_ref`),
  KEY `idx_redemptions_voucher` (`voucher_id`,`released_at`),
  KEY `idx_redemptions_user` (`user_id`,`voucher_id`,`released_at`),
  CONSTRAINT `fk_redemptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_redemptions_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `voucher_redemptions` WRITE;
/*!40000 ALTER TABLE `voucher_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_redemptions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `description` varchar(160) NOT NULL,
  `type` enum('percent','fixed','shipping') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_spend` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `per_user_limit` int(10) unsigned DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES
(1,'YOGI10','10% off, up to ₱1,000','percent',10.00,3000.00,1000.00,1000,3,NULL,1,'2026-11-04 20:34:09'),
(2,'CYBER500','₱500 off orders ₱15,000 and up','fixed',500.00,15000.00,NULL,200,1,NULL,1,'2026-11-04 20:34:09'),
(3,'FREESHIP','Free shipping, up to ₱200','shipping',200.00,0.00,NULL,NULL,5,NULL,1,'2026-11-04 20:34:09'),
(4,'NEON2077','20% off, up to ₱3,000 (min ₱20,000)','percent',20.00,20000.00,3000.00,100,1,NULL,1,'2026-11-04 20:34:09');
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist_user_product` (`user_id`,`product_id`),
  KEY `fk_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

