-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: fun_frozen_food
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Nugget','2026-03-30 04:18:58'),(2,'Sosis','2026-03-30 04:18:58'),(3,'Bakso','2026-03-30 04:18:58'),(4,'Dimsum','2026-03-30 04:18:58'),(5,'Siomay','2026-03-30 04:18:58'),(6,'Kentang Goreng','2026-03-30 04:18:58'),(7,'Ayam Beku','2026-03-30 04:18:58'),(8,'Ikan & Seafood','2026-03-30 04:18:58'),(9,'Lumpia','2026-03-30 04:18:58'),(10,'Lainnya','2026-03-30 04:18:58');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `online_order_items`
--

DROP TABLE IF EXISTS `online_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty` int NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `online_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `online_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `online_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `online_order_items`
--

LOCK TABLES `online_order_items` WRITE;
/*!40000 ALTER TABLE `online_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `online_orders`
--

DROP TABLE IF EXISTS `online_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_address` text NOT NULL,
  `payment_method` enum('qris','bank') NOT NULL DEFAULT 'qris',
  `proof_image` varchar(255) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `admin_note` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `online_orders`
--

LOCK TABLES `online_orders` WRITE;
/*!40000 ALTER TABLE `online_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `image` varchar(255) DEFAULT NULL,
  `description` text,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `low_stock_alert` int NOT NULL DEFAULT '5',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Nugget Ayam So Good 500gr',32000.00,47,'pack','product_1774821733_69c9a165a27c4.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-02 00:43:38'),(2,1,'Nugget Ayam Fiesta 400gr',28000.00,44,'pack','product_1774821843_69c9a1d3266fb.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-02 00:47:27'),(3,2,'Sosis Ayam Bernardi 360gr',25000.00,60,'pack','product_1774821876_69c9a1f47c4f1.png',NULL,1,5,'2026-03-30 04:18:58','2026-03-30 05:04:36'),(4,2,'Sosis Sapi Kimbo 375gr',30000.00,40,'pack','product_1774821906_69c9a212b4b77.png',NULL,1,5,'2026-03-30 04:18:58','2026-03-30 05:05:06'),(5,3,'Bakso Sapi Fiesta 500gr',35000.00,33,'pack','product_1774821951_69c9a23f62790.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-02 00:43:36'),(6,3,'Bakso Ikan Abalone 500gr',40000.00,29,'pack','product_1774821985_69c9a26109e08.png',NULL,1,5,'2026-03-30 04:18:58','2026-03-30 05:06:25'),(7,4,'Dimsum Ayam Cedea 500gr',45000.00,23,'pack','product_1774822019_69c9a28382558.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-02 00:43:38'),(8,5,'Siomay Udang Premium 300gr',38000.00,20,'pack','product_1774822054_69c9a2a66281e.png',NULL,1,5,'2026-03-30 04:18:58','2026-03-30 05:07:34'),(9,6,'Kentang Goreng McCain 1kg',55000.00,39,'pack','product_1774822101_69c9a2d5680b9.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-01 20:52:28'),(10,7,'Ayam Fillet Beku 1kg',65000.00,28,'kg','product_1774822189_69c9a32dd5056.png',NULL,1,3,'2026-03-30 04:18:58','2026-04-01 21:07:54'),(11,8,'Udang Beku Medium 500gr',70000.00,20,'pack','product_1774822227_69c9a3537ed37.png',NULL,1,3,'2026-03-30 04:18:58','2026-03-30 05:10:27'),(12,9,'Lumpia Basah Ayam 10pcs',22000.00,47,'pack','product_1774822289_69c9a39176446.png',NULL,1,5,'2026-03-30 04:18:58','2026-04-02 00:47:27');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'store_name','Fun Frozen Food'),(2,'store_address','Jl. Contoh No. 1, Kota Anda'),(3,'store_phone','085806140244'),(4,'store_logo','logo_1774822425.png'),(5,'currency','Rp'),(6,'tax_percent','0'),(7,'receipt_footer','Terima kasih telah berbelanja!'),(8,'receipt_paper_size','58'),(16,'bank_name',''),(17,'bank_account_number',''),(18,'bank_account_holder',''),(19,'qris_image','qris_1775063697_ce924a76a966fc3d.jpg');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_history`
--

DROP TABLE IF EXISTS `stock_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `type` enum('in','out','adjustment') NOT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `stock_before` int NOT NULL DEFAULT '0',
  `stock_after` int NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_stock_product` (`product_id`),
  CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_history`
--

LOCK TABLES `stock_history` WRITE;
/*!40000 ALTER TABLE `stock_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_items`
--

DROP TABLE IF EXISTS `transaction_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `qty` int NOT NULL DEFAULT '1',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_item_transaction` (`transaction_id`),
  KEY `fk_item_product` (`product_id`),
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_item_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_items`
--

LOCK TABLES `transaction_items` WRITE;
/*!40000 ALTER TABLE `transaction_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaction_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `payment_method` enum('tunai','transfer') NOT NULL DEFAULT 'tunai',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `note` text,
  `cashier_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_transaction_cashier` (`cashier_id`),
  CONSTRAINT `fk_transaction_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrator','admin','2026-03-30 04:18:58'),(2,'kasir1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Kasir 1','kasir','2026-03-30 04:18:58');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-08 14:22:33
