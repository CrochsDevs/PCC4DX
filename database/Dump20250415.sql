-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: pcc_auth_system
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `centers`
--

DROP TABLE IF EXISTS `centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centers` (
  `center_id` int(11) NOT NULL AUTO_INCREMENT,
  `center_code` varchar(10) NOT NULL,
  `center_name` varchar(100) NOT NULL,
  `center_type` enum('Headquarters','Regional') NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `region` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`center_id`),
  UNIQUE KEY `center_code` (`center_code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `centers`
--

LOCK TABLES `centers` WRITE;
/*!40000 ALTER TABLE `centers` DISABLE KEYS */;
INSERT INTO `centers` VALUES (1,'HQ','PCC Headquarters','Headquarters','images/headquarters.png','National',1),(2,'CLSU','Central Luzon State University','Regional','images/clsu-pic.png','Region III',1),(3,'CMU','Central Mindanao University','Regional','images/cmu.png','Region XII',1),(4,'CSU','Cagayan State University','Regional','images/csu.png','Region II',1),(5,'DMMMSU','Don Mariano Marcos Memorial State University','Regional','images/dmmmsu-pic.png','Region I',1),(6,'GP','Gene Pool','Regional','images/genepool.jpg','Region III',1),(7,'LCSF','La Carlota Stock Farm','Regional','images/lcsf.png','Region VI',1),(8,'NIZ','National Impact Zone','Regional','images/niz.jpg','Region III',1),(9,'MLPC','Mindanao Livestock Production Center','Regional','images/mlpc-pic.png','Region XII',1),(10,'MMSU','Mariano Marcos State University','Regional','images/mmsu2.png','Region I',1),(11,'USF','Ubay Stock Farm','Regional','images/usf.jpg','Region VII',1),(12,'UPLB','University of the Philippines Los Baños','Regional','images/uplb.png','Region IV-A',1),(13,'USM','University of Southern Mindanao','Regional','images/usm.jpg','Region XII',1),(14,'VSU','Visayas State University','Regional','images/vsu.jpg','Region VIII',1),(15,'WVSU','West Visayas State University','Regional','images/wvsu.jpg','Region VI',1);
/*!40000 ALTER TABLE `centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `milk_production`
--

DROP TABLE IF EXISTS `milk_production`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `milk_production` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `volume` decimal(10,2) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `center_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `milk_production_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `milk_production`
--

LOCK TABLES `milk_production` WRITE;
/*!40000 ALTER TABLE `milk_production` DISABLE KEYS */;
INSERT INTO `milk_production` VALUES (1,'2025-04-15',123.00,132.00,6,'CLSU','2025-04-15 08:43:38','Pending'),(2,'2025-04-15',12.00,21231.00,6,'CLSU','2025-04-15 08:54:34','Pending');
/*!40000 ALTER TABLE `milk_production` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(255) NOT NULL,
  `herd_code` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `province` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `center_code` varchar(16) NOT NULL,
  `coop_type` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,'Partner 1','HC001','Person 1','09171525785','San Isidro','Bayombong','Laguna',1,'CSU','Cooperations'),(2,'Partner 2','HC002','Person 2','09173758304','Malinta','Cabanatuan','Nueva Ecija',0,'DMMMSU','Cooperations'),(3,'Partner 3','HC003','Person 3','09171326937','San Jose','Ilagan','Isabela',0,'GP','Family_Module'),(4,'Partner 4','HC004','Person 4','09171589485','Barangay Uno','Bayombong','Isabela',1,'UPLB','Associations'),(5,'Partner 5','HC005','Person 5','09178592550','Malinta','Bayombong','Nueva Ecija',1,'GP','Family_Module'),(6,'Partner 6','HC006','Person 6','09174864574','Barangay Uno','Bayombong','Laguna',1,'CLSU','LGU'),(7,'Partner 7','HC007','Person 7','09176191820','Poblacion','Cabanatuan','Ilocos Norte',1,'MLPC','Cooperations'),(8,'Partner 8','HC008','Person 8','09176357353','Barangay Uno','Ilagan','Nueva Ecija',1,'GP','Cooperations'),(9,'Partner 9','HC009','Person 9','09171484519','Malinta','Cabanatuan','Laguna',1,'CLSU','Cooperations'),(10,'Partner 10','HC010','Person 10','09173772046','Barangay Uno','Cabanatuan','Nueva Ecija',1,'HQ','Corporation'),(11,'Partner 11','HC011','Person 11','09172340231','San Isidro','Cabanatuan','Isabela',1,'GP','Associations'),(12,'Partner 12','HC012','Person 12','09172398427','San Jose','Ilagan','Nueva Ecija',0,'LCSF','SCU'),(13,'Partner 13','HC013','Person 13','09179160757','San Isidro','Ilagan','Nueva Ecija',1,'USM','Corporation'),(14,'Partner 14','HC014','Person 14','09171647833','San Jose','Ilagan','Nueva Ecija',0,'USF','Cooperations'),(15,'Partner 15','HC015','Person 15','09178209452','San Jose','Munoz','Isabela',0,'MMSU','SCU'),(16,'Partner 16','HC016','Person 16','09179506249','Poblacion','Munoz','Nueva Ecija',1,'VSU','Associations'),(17,'Partner 17','HC017','Person 17','09177176091','Barangay Uno','Ilagan','Laguna',0,'DMMMSU','Family_Module'),(18,'Partner 18','HC018','Person 18','09172286391','Malinta','Bayombong','Pampanga',0,'MMSU','Family_Module'),(19,'Partner 19','HC019','Person 19','09171392711','Poblacion','Cabanatuan','Ilocos Norte',1,'CLSU','Family_Module'),(20,'Partner 20','HC020','Person 20','09172515133','San Isidro','Cabanatuan','Laguna',1,'CLSU','LGU'),(21,'Partner 21','HC021','Person 21','09177361225','Malinta','Munoz','Pampanga',1,'USF','LGU'),(22,'Partner 22','HC022','Person 22','09175097844','San Isidro','Cabanatuan','Isabela',0,'VSU','Cooperations'),(23,'Partner 23','HC023','Person 23','09174480659','San Jose','San Fernando','Isabela',1,'CSU','Family_Module'),(24,'Partner 24','HC024','Person 24','09173347400','San Isidro','Bayombong','Ilocos Norte',1,'MLPC','LGU'),(25,'Partner 25','HC025','Person 25','09175944547','Malinta','Cabanatuan','Nueva Ecija',0,'WVSU','Family_Module'),(26,'Partner 26','HC026','Person 26','09173954248','Barangay Uno','Bayombong','Pampanga',0,'CMU','Cooperations'),(27,'Partner 27','HC027','Person 27','09173580522','Barangay Uno','Ilagan','Pampanga',1,'USF','Corporation'),(28,'Partner 28','HC028','Person 28','09177613484','Malinta','Munoz','Isabela',0,'DMMMSU','Family_Module'),(29,'Partner 29','HC029','Person 29','09178508447','Poblacion','Bayombong','Isabela',0,'VSU','Family_Module'),(30,'Partner 30','HC030','Person 30','09174476271','Poblacion','Cabanatuan','Pampanga',0,'WVSU','LGU'),(31,'Partner 31','HC031','Person 31','09179559959','Barangay Uno','Ilagan','Isabela',0,'LCSF','Corporation'),(32,'Partner 32','HC032','Person 32','09175289542','San Isidro','San Fernando','Laguna',1,'USF','Family_Module'),(33,'Partner 33','HC033','Person 33','09175233035','Barangay Uno','Bayombong','Pampanga',1,'DMMMSU','Cooperations'),(34,'Partner 34','HC034','Person 34','09172541074','Malinta','San Fernando','Laguna',0,'NIZ','Associations'),(35,'Partner 35','HC035','Person 35','09178723784','Barangay Uno','Munoz','Laguna',0,'VSU','Cooperations'),(36,'Partner 36','HC036','Person 36','09174001518','Malinta','Cabanatuan','Nueva Ecija',0,'CMU','Cooperations'),(37,'Partner 37','HC037','Person 37','09178014122','Barangay Uno','Cabanatuan','Isabela',1,'CMU','SCU'),(38,'Partner 38','HC038','Person 38','09179503810','San Isidro','Cabanatuan','Ilocos Norte',1,'CLSU','Corporation'),(39,'Partner 39','HC039','Person 39','09178930166','Barangay Uno','San Fernando','Pampanga',1,'LCSF','Cooperations'),(40,'Partner 40','HC040','Person 40','09178906867','Malinta','San Fernando','Isabela',0,'UPLB','Cooperations'),(41,'Partner 41','HC041','Person 41','09177934031','San Jose','Bayombong','Ilocos Norte',0,'MMSU','Cooperations'),(42,'Partner 42','HC042','Person 42','09178203568','Barangay Uno','Munoz','Laguna',1,'NIZ','Associations'),(43,'Partner 43','HC043','Person 43','09172756843','Poblacion','Munoz','Isabela',1,'USM','SCU'),(44,'Partner 44','HC044','Person 44','09177038807','San Jose','Cabanatuan','Isabela',1,'LCSF','Associations'),(45,'Partner 45','HC045','Person 45','09174040863','Malinta','Cabanatuan','Ilocos Norte',0,'WVSU','LGU'),(46,'Partner 46','HC046','Person 46','09175650020','Barangay Uno','Bayombong','Laguna',1,'DMMMSU','Associations'),(47,'Partner 47','HC047','Person 47','09178897673','Malinta','Bayombong','Pampanga',1,'WVSU','Family_Module'),(48,'Partner 48','HC048','Person 48','09174809379','San Isidro','Munoz','Ilocos Norte',0,'DMMMSU','Associations'),(49,'Partner 49','HC049','Person 49','09174270240','Malinta','Munoz','Pampanga',0,'LCSF','Corporation'),(50,'Partner 50','HC050','Person 50','09177227101','San Jose','Ilagan','Ilocos Norte',0,'HQ','Family_Module');
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `center_code` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL COMMENT 'Stores filename of profile picture',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `center_code` (`center_code`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`center_code`) REFERENCES `centers` (`center_code`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'HQ','administrator','operations@pcc.gov.ph','$2y$10$i3a2g5nOHiw9lDAgwGuWWODL14VeckLHfyQyBycUQ2tFpEpUwYEWa','operation','Operation In Charges',1,'2025-03-31 14:25:16','0938-123-1234','67f8729427ff3_WIN_20250411_09_38_00_Pro.jpg'),(2,'DMMMSU','dmmmsu_admin','dmmmsu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Juan Dela Cruz','Center Manager - DMMMSU',1,NULL,NULL,NULL),(3,'MMSU','mmsu_admin','mmsu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Maria Santos','Center Manager - MMSU',1,NULL,NULL,NULL),(4,'CSU','csu_admin','csu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Roberto Garcia','Center Manager - CSU',1,NULL,'','67ef9eebd93ea_bacteriaxfungi.webp'),(5,'CLSU','clsu_admin','admin@pcc.gov.ph','$2y$10$cTJOLD0u6uR3YoOk54bM1uDXg7IGvWlHW9aaIwYAJlc2rNXRfGL12','lorance Del Rosario','Center Manager - CLSU',1,NULL,'','67ef9bffa7494_power grow.jfif'),(6,'GP','gp_admin','genepool@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Carlos Mendoza','Gene Pool Supervisor',1,NULL,NULL,NULL),(7,'NIZ','niz_admin','niz@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Lourdes Tan','NIZ Coordinator',1,NULL,NULL,NULL),(8,'UPLB','uplb_admin','uplb@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Emmanuel Rivera','Center Manager - UPLB',1,NULL,NULL,NULL),(9,'LCSF','lcsf_admin','lcsf@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Sofia Hernandez','Center Manager - La Carlota',1,NULL,NULL,NULL),(10,'WVSU','wvsu_admin','wvsu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Antonio Lopez','Center Manager - WVSU',1,NULL,NULL,NULL),(11,'USF','usf_admin','usf@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Patricia Gomez','Center Manager - Ubay',1,NULL,NULL,NULL),(12,'VSU','vsu_admin','vsu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Ferdinand Castro','Center Manager - VSU',1,NULL,NULL,NULL),(13,'CMU','cmu_admin','cmu@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Rosalinda Fernando','Center Manager - CMU',1,NULL,NULL,NULL),(14,'MLPC','mlpc_admin','mlpc@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Ricardo Dizon','Center Manager - MLPC',1,NULL,NULL,NULL),(15,'USM','usm_admin','usm@pcc.gov.ph','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Dr. Lorna Ramirez','Center Manager - USM',1,NULL,NULL,NULL);
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

-- Dump completed on 2025-04-15 17:08:33
