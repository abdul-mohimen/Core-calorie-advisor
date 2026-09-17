-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: core_calorie_advisor
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `goal` varchar(150) DEFAULT NULL,
  `appt_date` datetime NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fee` decimal(10,2) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `type` enum('training','consultation') NOT NULL DEFAULT 'training',
  PRIMARY KEY (`id`),
  KEY `idx_trainer` (`trainer_id`,`status`),
  KEY `idx_member` (`member_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,2,4,'Muscle Gain','2026-07-11 18:00:00','pending','2026-07-24 11:03:08',NULL,NULL,'training'),(2,9,4,'Weight Loss','2026-07-12 16:00:00','pending','2026-07-24 11:03:08',NULL,NULL,'training'),(3,2,5,'Knee checkup','2026-07-12 15:00:00','accepted','2026-07-24 11:03:08',NULL,NULL,'training'),(4,3,5,'Knee-safe plan review','2026-07-13 11:00:00','pending','2026-07-24 11:03:08',NULL,NULL,'training');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_attempts`
--

DROP TABLE IF EXISTS `auth_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attempt_key` char(64) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attempt_window` (`attempt_key`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_attempts`
--

LOCK TABLES `auth_attempts` WRITE;
/*!40000 ALTER TABLE `auth_attempts` DISABLE KEYS */;
INSERT INTO `auth_attempts` VALUES (3,'7dc46cb250edca79a6c7a4fb923e385c5c373fc4695d1e9f896e33a07f80d862','2026-07-25 17:41:19'),(4,'7dc46cb250edca79a6c7a4fb923e385c5c373fc4695d1e9f896e33a07f80d862','2026-07-25 17:41:30'),(1,'c10292e8d6b4fda28643c9214c2ec80f2a02271eb0a433c19bec3d3e5294fbc4','2026-07-25 05:56:05'),(2,'c10292e8d6b4fda28643c9214c2ec80f2a02271eb0a433c19bec3d3e5294fbc4','2026-07-25 05:56:19');
/*!40000 ALTER TABLE `auth_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `body_scans`
--

DROP TABLE IF EXISTS `body_scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `body_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `body_type` varchar(40) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT 0.0,
  `body_fat_pct` decimal(4,1) DEFAULT 0.0,
  `bmi` decimal(4,1) DEFAULT 0.0,
  `muscle_mass_kg` decimal(5,1) DEFAULT 0.0,
  `verdict` varchar(60) DEFAULT NULL,
  `health_flag` tinyint(1) DEFAULT 0,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_scan` (`user_id`,`scanned_at`),
  CONSTRAINT `body_scans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `body_scans`
--

LOCK TABLES `body_scans` WRITE;
/*!40000 ALTER TABLE `body_scans` DISABLE KEYS */;
INSERT INTO `body_scans` VALUES (1,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 10:07:03'),(2,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:11:19'),(3,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:11:43'),(4,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:11:58'),(5,2,'Goal: gain',0.0,0.0,0.0,0.0,'Muscle Gain Plan',0,'2026-07-25 12:12:04'),(6,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:12:12'),(7,2,'Goal: recomp',0.0,0.0,0.0,0.0,'Recomposition Plan',0,'2026-07-25 12:13:21'),(8,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:13:28'),(9,2,'Goal: recomp',0.0,0.0,0.0,0.0,'Recomposition Plan',0,'2026-07-25 12:17:28'),(10,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:17:35'),(11,2,'Goal: gain',0.0,0.0,0.0,0.0,'Muscle Gain Plan',0,'2026-07-25 12:17:39'),(12,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 12:38:42'),(13,2,'Goal: gain',0.0,0.0,0.0,0.0,'Muscle Gain Plan',0,'2026-07-25 13:05:43'),(14,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 13:24:48'),(15,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 13:51:50'),(16,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 13:51:50'),(17,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 13:52:04'),(18,2,'Goal: gain',0.0,0.0,0.0,0.0,'Muscle Gain Plan',0,'2026-07-25 14:07:43'),(19,2,'Goal: gain',0.0,0.0,0.0,0.0,'Muscle Gain Plan',0,'2026-07-25 14:07:50'),(20,2,'Goal: cut',0.0,0.0,0.0,0.0,'Fat Loss Plan',0,'2026-07-25 14:09:15'),(21,2,'Goal: recomp',0.0,0.0,0.0,0.0,'Recomposition Plan',0,'2026-07-25 14:11:34');
/*!40000 ALTER TABLE `body_scans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `body` varchar(500) NOT NULL,
  `likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
INSERT INTO `community_posts` VALUES (1,2,'hi',0,'2026-07-25 14:13:21');
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disease_plans`
--

DROP TABLE IF EXISTS `disease_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disease_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disease_id` (`disease_id`),
  KEY `workout_id` (`workout_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `disease_plans_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `diseases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disease_plans_ibfk_2` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disease_plans_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disease_plans`
--

LOCK TABLES `disease_plans` WRITE;
/*!40000 ALTER TABLE `disease_plans` DISABLE KEYS */;
INSERT INTO `disease_plans` VALUES (1,4,9,5),(2,2,6,5),(3,3,6,8),(4,1,6,5);
/*!40000 ALTER TABLE `disease_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diseases`
--

DROP TABLE IF EXISTS `diseases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diseases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `precautions` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diseases`
--

LOCK TABLES `diseases` WRITE;
/*!40000 ALTER TABLE `diseases` DISABLE KEYS */;
INSERT INTO `diseases` VALUES (1,'Heart Disease','No high intensity. Heart rate monitor lazmi. Doctor approval required.'),(2,'Diabetes Type 2','Low-impact steady cardio. Sugar check before/after workout.'),(3,'High Blood Pressure','Avoid heavy lifting aur breath-holding. Steady breathing.'),(4,'Knee Pain','No jumps, no deep squats, no running. Joint-safe only.');
/*!40000 ALTER TABLE `diseases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercises`
--

DROP TABLE IF EXISTS `exercises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercises` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workout_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `seconds` int(11) NOT NULL DEFAULT 30,
  `kcal` int(11) NOT NULL DEFAULT 6,
  `anim_mode` varchar(30) DEFAULT 'idle',
  `sort_order` int(11) DEFAULT 0,
  `target_muscle` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_workout` (`workout_id`),
  KEY `idx_target_muscle` (`target_muscle`),
  CONSTRAINT `exercises_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercises`
--

LOCK TABLES `exercises` WRITE;
/*!40000 ALTER TABLE `exercises` DISABLE KEYS */;
INSERT INTO `exercises` VALUES (1,1,'Crunches',30,6,'crunch',1,'core'),(2,1,'Russian Twists',30,7,'twist',2,'core'),(3,1,'Leg Raises',30,6,'legraise',3,'core'),(4,1,'Plank',30,5,'plank',4,'core'),(5,1,'Mountain Climbers',30,9,'mountain',5,'full-body'),(6,1,'Bicycle Crunches',30,8,'crunch',6,'core'),(7,2,'Push-Ups',40,10,'pushup',1,'chest'),(8,2,'Shoulder Press',30,7,'press',2,'shoulders'),(9,2,'Bicep Curls',30,6,'curl',3,'arms'),(10,2,'Plank',30,5,'plank',4,'core'),(11,3,'Squats',40,10,'squat',1,'legs'),(12,3,'Lunges',40,10,'squat',2,'legs'),(13,3,'High Knees',30,9,'highknees',3,'legs'),(14,3,'Wall Sit',30,6,'squat',4,'legs'),(15,4,'Jumping Jacks',30,9,'jumpingjack',1,'full-body'),(16,4,'Squats',30,8,'squat',2,'legs'),(17,4,'Push-Ups',30,8,'pushup',3,'chest'),(18,4,'Mountain Climbers',30,9,'mountain',4,'full-body'),(19,4,'Plank',30,5,'plank',5,'core'),(20,5,'Bicep Curls',40,8,'curl',1,'arms'),(21,5,'Shoulder Press',40,9,'press',2,'shoulders'),(22,5,'Bent-Over Rows',30,7,'curl',3,'arms'),(23,5,'Squat Press',30,9,'press',4,'shoulders'),(24,6,'Tree Pose',40,4,'yoga',1,'flexibility'),(25,6,'Warrior Hold',40,5,'yoga',2,'flexibility'),(26,6,'Child Pose',30,3,'yoga',3,'flexibility'),(27,6,'Deep Breathing',30,2,'yoga',4,'flexibility'),(28,7,'Burpees',30,11,'squat',1,'legs'),(29,7,'High Knees',30,9,'highknees',2,'legs'),(30,7,'Jumping Jacks',30,9,'jumpingjack',3,'full-body'),(31,7,'Mountain Climbers',30,9,'mountain',4,'full-body'),(32,7,'Squat Jumps',30,10,'squat',5,'legs'),(33,8,'Plank',60,10,'plank',1,'core'),(34,8,'Side Plank',30,5,'plank',2,'core'),(35,8,'Plank',45,8,'plank',3,'core'),(36,9,'Seated Leg Extensions',40,5,'legraise',1,'core'),(37,9,'Wall Push-Ups',40,6,'wallpushup',2,'full-body'),(38,9,'Glute Bridge Hold',30,5,'plank',3,'core'),(39,9,'Deep Breathing',30,2,'yoga',4,'flexibility'),(40,10,'Jumping Jacks',40,9,'jumpingjack',1,NULL),(41,10,'Squats',45,10,'squat',2,NULL),(42,10,'Push-Ups',40,10,'pushup',3,NULL),(43,10,'Mountain Climbers',40,9,'mountain',4,NULL),(44,10,'Crunches',40,7,'crunch',5,NULL),(45,10,'Lunges',45,10,'squat',6,NULL),(46,10,'High Knees',35,9,'highknees',7,NULL),(47,10,'Plank',45,6,'plank',8,NULL),(48,11,'Crunches',40,7,'crunch',1,NULL),(49,11,'Bicycle Crunches',40,8,'crunch',2,NULL),(50,11,'Russian Twists',40,7,'twist',3,NULL),(51,11,'Leg Raises',40,6,'legraise',4,NULL),(52,11,'Plank',45,5,'plank',5,NULL),(53,11,'Mountain Climbers',40,9,'mountain',6,NULL),(54,12,'Plank',45,5,'plank',1,NULL),(55,12,'Side Plank',40,5,'plank',2,NULL),(56,12,'Russian Twists',40,7,'twist',3,NULL),(57,12,'Leg Raises',40,6,'legraise',4,NULL),(58,12,'Crunches',40,7,'crunch',5,NULL),(59,12,'Mountain Climbers',40,9,'mountain',6,NULL),(60,13,'Bent-Over Rows',45,8,'curl',1,NULL),(61,13,'Superman Hold',40,5,'plank',2,NULL),(62,13,'Reverse Crunch',40,6,'legraise',3,NULL),(63,13,'Shoulder Press',40,7,'press',4,NULL),(64,13,'Russian Twists',40,7,'twist',5,NULL),(65,14,'Squats',45,10,'squat',1,NULL),(66,14,'Lunges',45,10,'squat',2,NULL),(67,14,'Wall Sit',45,6,'squat',3,NULL),(68,14,'High Knees',35,9,'highknees',4,NULL),(69,14,'Squat Jumps',40,11,'squat',5,NULL),(70,15,'Squat Jumps',40,11,'squat',1,NULL),(71,15,'Jumping Jacks',40,9,'jumpingjack',2,NULL),(72,15,'High Knees',35,9,'highknees',3,NULL),(73,15,'Burpees',40,12,'squat',4,NULL),(74,15,'Mountain Climbers',40,9,'mountain',5,NULL),(75,16,'Squats',45,10,'squat',1,NULL),(76,16,'Lunges',45,10,'squat',2,NULL),(77,16,'Glute Bridge Hold',40,5,'plank',3,NULL),(78,16,'Wall Sit',45,6,'squat',4,NULL),(79,16,'Squat Jumps',40,11,'squat',5,NULL),(80,17,'Push-Ups',40,10,'pushup',1,NULL),(81,17,'Shoulder Press',40,7,'press',2,NULL),(82,17,'Bicep Curls',40,6,'curl',3,NULL),(83,17,'Bent-Over Rows',40,8,'curl',4,NULL),(84,17,'Plank',45,5,'plank',5,NULL),(85,18,'Push-Ups',40,10,'pushup',1,NULL),(86,18,'Bicep Curls',40,6,'curl',2,NULL),(87,18,'Shoulder Press',40,7,'press',3,NULL),(88,18,'Wide Push-Ups',40,10,'pushup',4,NULL),(89,18,'Plank',45,5,'plank',5,NULL),(90,19,'Shoulder Press',45,8,'press',1,NULL),(91,19,'Bent-Over Rows',45,8,'curl',2,NULL),(92,19,'Bicep Curls',40,6,'curl',3,NULL),(93,19,'Push-Ups',40,10,'pushup',4,NULL),(94,20,'Jumping Jacks',40,9,'jumpingjack',1,NULL),(95,20,'High Knees',35,9,'highknees',2,NULL),(96,20,'Burpees',40,12,'squat',3,NULL),(97,20,'Mountain Climbers',40,9,'mountain',4,NULL),(98,20,'Squat Jumps',40,11,'squat',5,NULL),(99,21,'Warm Up March',40,5,'warmup',1,NULL),(100,21,'Jumping Jacks',40,8,'jumpingjack',2,NULL),(101,21,'High Knees',35,8,'highknees',3,NULL),(102,21,'Squats',45,8,'squat',4,NULL),(103,22,'Burpees',20,7,'squat',1,NULL),(104,22,'Mountain Climbers',20,6,'mountain',2,NULL),(105,22,'Jumping Jacks',20,5,'jumpingjack',3,NULL),(106,22,'High Knees',20,5,'highknees',4,NULL),(107,22,'Squat Jumps',20,7,'squat',5,NULL),(108,23,'Squats',45,10,'squat',1,NULL),(109,23,'Push-Ups',40,10,'pushup',2,NULL),(110,23,'Jumping Jacks',40,9,'jumpingjack',3,NULL),(111,23,'Shoulder Press',40,7,'press',4,NULL),(112,23,'Mountain Climbers',40,9,'mountain',5,NULL),(113,24,'Jumping Jacks',30,9,'jumpingjack',1,NULL),(114,24,'Squats',30,8,'squat',2,NULL),(115,24,'Squat Jumps',30,10,'squat',3,NULL),(116,24,'High Knees',30,9,'highknees',4,NULL),(117,25,'Deep Breathing',30,2,'yoga',1,NULL),(118,25,'Seated Leg Extensions',40,5,'legraise',2,NULL),(119,25,'Wall Push-Ups',40,6,'pushup',3,NULL),(120,25,'Glute Bridge Hold',30,5,'plank',4,NULL),(121,26,'Tree Pose',40,4,'yoga',1,NULL),(122,26,'Warrior Hold',40,5,'yoga',2,NULL),(123,26,'Child Pose',30,3,'yoga',3,NULL),(124,26,'Deep Breathing',30,2,'yoga',4,NULL),(125,27,'Deep Breathing',30,2,'yoga',1,NULL),(126,27,'Warrior Hold',45,6,'yoga',2,NULL),(127,27,'Child Pose',30,3,'yoga',3,NULL),(128,28,'Deep Breathing',30,2,'yoga',1,NULL),(129,28,'Child Pose',40,4,'yoga',2,NULL),(130,28,'Glute Bridge Hold',30,5,'plank',3,NULL),(131,29,'Deep Breathing',30,2,'yoga',1,NULL),(132,29,'Tree Pose',45,5,'yoga',2,NULL),(133,29,'Warrior Hold',40,5,'yoga',3,NULL),(134,30,'Deep Breathing',40,3,'yoga',1,NULL),(135,30,'Child Pose',40,4,'yoga',2,NULL),(136,31,'Tree Pose',30,3,'yoga',1,NULL),(137,31,'Warrior Hold',30,4,'yoga',2,NULL),(138,31,'Child Pose',30,3,'yoga',3,NULL),(139,31,'Deep Breathing',30,2,'yoga',4,NULL),(140,32,'Jumping Jacks',30,9,'jumpingjack',1,NULL),(141,32,'Squats',30,8,'squat',2,NULL),(142,32,'Warmup',40,5,'warmup',3,NULL),(143,33,'Deep Breathing',40,3,'yoga',1,NULL),(144,33,'Child Pose',40,4,'yoga',2,NULL),(145,34,'Deep Breathing',30,2,'yoga',1,NULL),(146,34,'Child Pose',30,3,'yoga',2,NULL),(147,35,'Deep Breathing',30,2,'yoga',1,NULL),(148,35,'Child Pose',30,3,'yoga',2,NULL),(149,36,'Deep Breathing',30,2,'yoga',1,NULL),(150,36,'Child Pose',30,3,'yoga',2,NULL),(151,37,'Deep Breathing',40,3,'yoga',1,NULL),(152,37,'Child Pose',30,3,'yoga',2,NULL);
/*!40000 ALTER TABLE `exercises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `message` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_logs`
--

DROP TABLE IF EXISTS `food_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `food_name` varchar(150) NOT NULL,
  `kcal` int(11) NOT NULL,
  `protein` decimal(6,1) DEFAULT 0.0,
  `carbs` decimal(6,1) DEFAULT 0.0,
  `fats` decimal(6,1) DEFAULT 0.0,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_day` (`user_id`,`logged_at`),
  CONSTRAINT `food_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_logs`
--

LOCK TABLES `food_logs` WRITE;
/*!40000 ALTER TABLE `food_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `food_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foods`
--

DROP TABLE IF EXISTS `foods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `kcal` int(11) NOT NULL,
  `protein` decimal(5,1) DEFAULT 0.0,
  `carbs` decimal(5,1) DEFAULT 0.0,
  `fats` decimal(5,1) DEFAULT 0.0,
  `serving` varchar(60) DEFAULT '100g',
  `image` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foods`
--

LOCK TABLES `foods` WRITE;
/*!40000 ALTER TABLE `foods` DISABLE KEYS */;
INSERT INTO `foods` VALUES (1,'Grilled Chicken Breast',165,31.0,0.0,3.6,'100g','https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=500&q=60&auto=format&fit=crop'),(2,'Brown Rice Bowl',112,2.6,23.5,0.9,'100g','https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=500&q=60&auto=format&fit=crop'),(3,'Boiled Eggs',155,13.0,1.1,11.0,'100g','https://images.unsplash.com/photo-1482049016688-2d3e1b311543?w=500&q=60&auto=format&fit=crop'),(4,'Fresh Salad',33,2.0,6.0,0.4,'100g','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500&q=60&auto=format&fit=crop'),(5,'Salmon Fillet',208,20.0,0.0,13.0,'100g','https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=500&q=60&auto=format&fit=crop'),(6,'Oatmeal + Banana',158,4.0,32.0,2.0,'1 bowl','https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=500&q=60&auto=format&fit=crop'),(7,'Greek Yogurt',59,10.0,3.6,0.4,'100g','https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=60&auto=format&fit=crop'),(8,'Mixed Nuts',607,20.0,21.0,54.0,'100g','https://images.unsplash.com/photo-1536591375315-2963e59e5f0d?w=500&q=60&auto=format&fit=crop'),(9,'Beef Kebab',290,26.0,3.0,19.0,'100g','https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=500&q=60&auto=format&fit=crop'),(10,'Daal Chawal',180,7.0,30.0,3.5,'1 plate','https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=500&q=60&auto=format&fit=crop'),(11,'Protein Shake',120,24.0,3.0,1.5,'1 scoop','https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=500&q=60&auto=format&fit=crop'),(12,'Apple',52,0.3,14.0,0.2,'100g','https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=500&q=60&auto=format&fit=crop');
/*!40000 ALTER TABLE `foods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issue_reports`
--

DROP TABLE IF EXISTS `issue_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issue_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `target_type` enum('doctor','trainer','hospital','general') DEFAULT 'general',
  `target_id` int(11) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `status` enum('open','reviewed','actioned') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `idx_status` (`status`,`created_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `issue_reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issue_reports`
--

LOCK TABLES `issue_reports` WRITE;
/*!40000 ALTER TABLE `issue_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `issue_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('info','warning','system') DEFAULT 'info',
  `title` varchar(150) NOT NULL,
  `body` varchar(400) DEFAULT NULL,
  `link` varchar(200) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'','🎉 ELITE plan activated','Aap ka CCA Elite subscription active ho gaya hai (sandbox). Tamam PRO features ab unlocked hain.','pages/workouts.php',1,'2026-07-25 02:49:19'),(2,2,'info','📦 Order Placed Successfully','Aap ka order \"Forge Iron Kettlebell 16kg\" secure processing me chala gaya hai via Credit/Debit Card.','pages/receipt.php?order_id=2',1,'2026-07-25 02:57:00'),(3,2,'info','📦 Order Placed Successfully','Aap ka order \"Pro Resistance Band Set\" secure processing me chala gaya hai via Gemini Pay.','pages/receipt.php?order_id=3',1,'2026-07-25 03:30:02'),(4,1,'system','🛒 New shop order #3','Mohimen Khan ne \"Pro Resistance Band Set\" khareeda — $24.99 via Gemini Pay.','pages/receipt.php?order_id=3',0,'2026-07-25 03:30:02');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_requests`
--

DROP TABLE IF EXISTS `payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `admin_note` varchar(300) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_status` (`status`),
  CONSTRAINT `payout_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_requests`
--

LOCK TABLES `payout_requests` WRITE;
/*!40000 ALTER TABLE `payout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `setting_key` varchar(60) NOT NULL,
  `setting_value` varchar(300) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES ('commission_rate','20','2026-07-24 11:39:13'),('currency','USD','2026-07-24 11:39:13'),('default_doctor_rate','100.00','2026-07-24 11:39:13'),('default_trainer_rate','50.00','2026-07-24 11:39:13'),('payouts_enabled','1','2026-07-24 11:39:13'),('top_rated_commission_rate','10','2026-07-24 11:39:13'),('top_rated_threshold','4.5','2026-07-24 11:39:13');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medication` varchar(200) NOT NULL,
  `dosage` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_patient` (`patient_id`,`is_active`),
  KEY `idx_doctor` (`doctor_id`),
  CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
INSERT INTO `prescriptions` VALUES (1,5,3,'Calcium + Vitamin D3','500mg + 1000IU daily','For knee joint support. Take with meals.',1,'2026-07-24 11:39:14'),(2,5,3,'Glucosamine Sulfate','1500mg daily','Cartilage repair support. 3 month course.',1,'2026-07-24 11:39:14');
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `remind_time` varchar(20) DEFAULT NULL,
  `done` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
INSERT INTO `reminders` VALUES (1,3,'💧 Paani piyo (glass 8/10)','6:00 PM',0),(2,3,'🚶 Joint-safe walk 20 min','7:00 PM',0),(3,3,'💊 Calcium tablet','9:00 PM',0);
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,2,4,5,'Best coach! 3 mahine me transformation ho gayi.','2026-07-24 11:03:08','approved'),(2,9,4,5,'Bohat professional aur motivating.','2026-07-24 11:03:08','approved'),(3,2,6,4,'HIIT sessions zabardast hain.','2026-07-24 11:03:08','approved');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_cart`
--

DROP TABLE IF EXISTS `shop_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cart` (`user_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `shop_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shop_cart_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `shop_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_cart`
--

LOCK TABLES `shop_cart` WRITE;
/*!40000 ALTER TABLE `shop_cart` DISABLE KEYS */;
INSERT INTO `shop_cart` VALUES (1,1,1,4,'2026-07-25 02:51:00'),(7,1,3,2,'2026-07-25 03:28:26');
/*!40000 ALTER TABLE `shop_cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_items`
--

DROP TABLE IF EXISTS `shop_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(300) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_items`
--

LOCK TABLES `shop_items` WRITE;
/*!40000 ALTER TABLE `shop_items` DISABLE KEYS */;
INSERT INTO `shop_items` VALUES (1,'CCA Adjustable Dumbbells (Pair)','Premium 24kg adjustable dumbbell set. Fast select dials allow weights adjustments from 2kg to 24kg instantly.',149.99,'https://images.unsplash.com/photo-1638536532686-d610adfc8e5c?w=500&q=60&auto=format&fit=crop','Equipment'),(2,'Pro Resistance Band Set','Heavy-duty latex bands with anti-snap technology. Includes 5 colored bands, handles, ankle straps and a carry bag.',24.99,'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=500&q=60&auto=format&fit=crop','Equipment'),(3,'CCA Whey Protein Iso-Forge','Premium grass-fed whey isolate. 25g protein per scoop, zero sugar, chocolate fudge flavor for clean muscle recovery.',59.99,'https://images.unsplash.com/photo-1593095948071-474c5cc2989d?w=500&q=60&auto=format&fit=crop','Supplements'),(4,'Premium Non-Slip Yoga Mat','Eco-friendly high-density TPE mat. 6mm thick cushioning with alignment lines for optimal yoga and core workouts.',34.99,'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=500&q=60&auto=format&fit=crop','Equipment'),(5,'Core Calorie Advisor Steel Shaker Bottle','Double-wall vacuum insulated stainless steel shaker. Keeps shakes ice cold for 24 hours. Leak-proof leak guard lid.',19.99,'https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=500&q=60&auto=format&fit=crop','Accessories'),(6,'Forge Iron Kettlebell 16kg','Solid cast-iron kettlebell with powder coat finish. Wide textured handle for ultimate grip and conditioning loops.',49.99,'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=500&q=60&auto=format&fit=crop','Equipment'),(7,'CCA Athletic Compression Shirt','Ultra-breathable dry-fit material with ergonomic flat seams. Drives heat away and maintains muscle warmth.',29.99,'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=500&q=60&auto=format&fit=crop','Apparel'),(8,'CCA Gym Training Gloves','Breathable mesh backing with padded leather palms and integrated wrist wrap support. Prevents calluses.',15.99,'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=500&q=60&auto=format&fit=crop','Accessories');
/*!40000 ALTER TABLE `shop_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_orders`
--

DROP TABLE IF EXISTS `shop_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Gemini Pay',
  `address` varchar(300) DEFAULT NULL,
  `status` enum('pending','completed','canceled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `shop_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shop_orders_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `shop_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_orders`
--

LOCK TABLES `shop_orders` WRITE;
/*!40000 ALTER TABLE `shop_orders` DISABLE KEYS */;
INSERT INTO `shop_orders` VALUES (1,2,1,1,149.99,'Gemini Pay',NULL,'completed','2026-07-25 02:42:39'),(2,2,6,1,49.99,'Credit/Debit Card','House no 79/90, street no 8, sector 13, block a','completed','2026-07-25 02:57:00'),(3,2,2,1,24.99,'Gemini Pay','House 4B, Street 12, DHA Phase 6, Karachi','completed','2026-07-25 03:30:02');
/*!40000 ALTER TABLE `shop_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_wishlist`
--

DROP TABLE IF EXISTS `shop_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_wish` (`user_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `shop_wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shop_wishlist_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `shop_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_wishlist`
--

LOCK TABLES `shop_wishlist` WRITE;
/*!40000 ALTER TABLE `shop_wishlist` DISABLE KEYS */;
INSERT INTO `shop_wishlist` VALUES (6,3,2,'2026-07-25 12:43:36'),(7,2,3,'2026-07-25 13:07:11'),(8,2,4,'2026-07-25 13:07:13');
/*!40000 ALTER TABLE `shop_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan` enum('pro','elite') NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'stripe',
  `provider_customer_id` varchar(191) DEFAULT NULL,
  `provider_subscription_id` varchar(191) DEFAULT NULL,
  `status` enum('pending','active','past_due','canceled') NOT NULL DEFAULT 'pending',
  `current_period_end` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_subscription` (`provider_subscription_id`),
  KEY `idx_subscription_user` (`user_id`,`status`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (1,2,'elite','sandbox',NULL,NULL,'active','2026-08-25 07:49:19','2026-07-25 07:49:19','2026-07-25 07:49:19');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainer_profiles`
--

DROP TABLE IF EXISTS `trainer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(120) NOT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(300) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 4.5,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 50.00,
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 100.00,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `trainer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_profiles`
--

LOCK TABLES `trainer_profiles` WRITE;
/*!40000 ALTER TABLE `trainer_profiles` DISABLE KEYS */;
INSERT INTO `trainer_profiles` VALUES (1,4,'Strength Trainer','Bodyweight aur barbell strength ka 8 saal ka tajurba.','https://images.unsplash.com/photo-1567013127542-490d757e51fc?w=500&q=60&auto=format&fit=crop',4.9,50.00,100.00),(2,6,'HIIT & Cardio','Fat burn HIIT specialist — 500+ transformations.','https://images.unsplash.com/photo-1571731956672-f2b94d7dd0cb?w=500&q=60&auto=format&fit=crop',4.8,50.00,100.00),(3,7,'Bodybuilding','Competition prep aur muscle gain coach.','https://images.unsplash.com/photo-1548690312-e3b507d8c110?w=500&q=60&auto=format&fit=crop',4.7,50.00,100.00),(4,5,'Sports Doctor','Disease-safe workout plans approve karti hain.','https://images.unsplash.com/photo-1594824476967-48c8b964273f?w=500&q=60&auto=format&fit=crop',5.0,50.00,100.00),(5,8,'Physiotherapist','Joint-safe rehab aur knee/back recovery expert.','https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=500&q=60&auto=format&fit=crop',4.8,50.00,100.00);
/*!40000 ALTER TABLE `trainer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('payment','commission','payout','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_type` varchar(30) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` varchar(300) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `stripe_session_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_type_status` (`type`,`status`),
  KEY `idx_created` (`created_at`),
  UNIQUE KEY `uq_transactions_stripe_session` (`stripe_session_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_progress`
--

DROP TABLE IF EXISTS `user_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `workout_id` (`workout_id`),
  CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_progress`
--

LOCK TABLES `user_progress` WRITE;
/*!40000 ALTER TABLE `user_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('member','patient','trainer','doctor','admin') NOT NULL DEFAULT 'member',
  `plan` enum('free','pro','elite') NOT NULL DEFAULT 'free',
  `disease` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`),
  KEY `idx_plan` (`plan`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'CCA Admin','admin@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','admin','elite',NULL,'2026-07-24 11:03:08'),(2,'Mohimen Khan','member@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','member','elite',NULL,'2026-07-24 11:03:08'),(3,'Usman Tariq','patient@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','patient','free','Diabetes Type 2','2026-07-24 11:03:08'),(4,'Coach Hamza Raza','trainer@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','trainer','free',NULL,'2026-07-24 11:03:08'),(5,'Dr. Sana Malik','doctor@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','doctor','free',NULL,'2026-07-24 11:03:08'),(6,'Coach Zara Ahmed','zara@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','trainer','free',NULL,'2026-07-24 11:03:08'),(7,'Coach Bilal Khan','bilal@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','trainer','free',NULL,'2026-07-24 11:03:08'),(8,'Dr. Ahmed Shah','ahmed@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','doctor','free',NULL,'2026-07-24 11:03:08'),(9,'Fatima Noor','fatima@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','member','free',NULL,'2026-07-24 11:03:08'),(10,'CCA Pro Access','pro@corecalorieadvisor.com','$2y$10$y7v4jvwjcSv.9ukPA9ve8eo/mKEkOU67PXoBh7oVK/D.mbzq09kpO','member','elite',NULL,'2026-07-24 11:03:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vitals_logs`
--

DROP TABLE IF EXISTS `vitals_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vitals_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_sugar` decimal(5,1) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `notes` varchar(300) DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`logged_at`),
  CONSTRAINT `vitals_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vitals_logs`
--

LOCK TABLES `vitals_logs` WRITE;
/*!40000 ALTER TABLE `vitals_logs` DISABLE KEYS */;
INSERT INTO `vitals_logs` VALUES (1,3,72,95.0,'120/80',78.5,24.8,'Morning check ÔÇö feeling good','2026-07-23 11:39:14'),(2,3,68,88.0,'118/76',78.2,24.7,'Post-walk vitals','2026-07-21 11:39:14'),(3,3,75,102.0,'125/82',79.0,25.0,'Slightly elevated sugar after lunch','2026-07-19 11:39:14'),(4,3,70,90.0,'119/78',78.0,24.6,'Normal range','2026-07-17 11:39:14');
/*!40000 ALTER TABLE `vitals_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES (1,4,0.00,'2026-07-24 11:39:14'),(2,6,0.00,'2026-07-24 11:39:14'),(3,7,0.00,'2026-07-24 11:39:14'),(4,5,0.00,'2026-07-24 11:39:14'),(5,8,0.00,'2026-07-24 11:39:14');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warnings`
--

DROP TABLE IF EXISTS `warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `severity` enum('notice','warning','severe') DEFAULT 'warning',
  `reason` varchar(500) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_target` (`target_user_id`,`created_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `warnings_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warnings_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warnings`
--

LOCK TABLES `warnings` WRITE;
/*!40000 ALTER TABLE `warnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_logs`
--

DROP TABLE IF EXISTS `workout_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workout_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `kcal_burned` int(11) DEFAULT 0,
  `duration_sec` int(11) DEFAULT 0,
  `target_seconds` int(11) NOT NULL DEFAULT 0,
  `blocks_completed` int(11) NOT NULL DEFAULT 0,
  `rest_added_seconds` int(11) NOT NULL DEFAULT 0,
  `skips_used` int(11) NOT NULL DEFAULT 0,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `workout_id` (`workout_id`),
  KEY `idx_user` (`user_id`,`completed_at`),
  CONSTRAINT `workout_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workout_logs_ibfk_2` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_logs`
--

LOCK TABLES `workout_logs` WRITE;
/*!40000 ALTER TABLE `workout_logs` DISABLE KEYS */;
INSERT INTO `workout_logs` (`id`, `user_id`, `workout_id`, `kcal_burned`, `duration_sec`, `target_seconds`, `blocks_completed`, `rest_added_seconds`, `skips_used`, `completed_at`) VALUES (1,2,1,41,255,250,5,30,0,'2026-07-23 11:03:08'),(2,2,4,39,225,220,4,30,0,'2026-07-22 11:03:08'),(3,2,8,23,135,130,3,15,0,'2026-07-21 11:03:08'),(4,2,1,41,255,250,5,30,0,'2026-07-19 11:03:08'),(5,2,9,5,73,70,2,15,0,'2026-07-25 10:08:36'),(6,2,6,6,102,100,2,15,0,'2026-07-25 10:10:36');
/*!40000 ALTER TABLE `workout_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_routines`
--

DROP TABLE IF EXISTS `workout_routines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workout_routines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `exercises_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_member` (`member_id`),
  CONSTRAINT `workout_routines_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_routines`
--

LOCK TABLES `workout_routines` WRITE;
/*!40000 ALTER TABLE `workout_routines` DISABLE KEYS */;
/*!40000 ALTER TABLE `workout_routines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workouts`
--

DROP TABLE IF EXISTS `workouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `tag` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(300) DEFAULT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `category` varchar(60) DEFAULT NULL,
  `subcategory` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_free` (`is_free`),
  KEY `idx_cat` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workouts`
--

LOCK TABLES `workouts` WRITE;
/*!40000 ALTER TABLE `workouts` DISABLE KEYS */;
INSERT INTO `workouts` VALUES (1,'Instant Six Pack','instant-six-pack','Abs · Free','6 killer ab exercises, back to back with short rests. 30 din roz karo aur shredded core forge karo.','https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=60&auto=format&fit=crop',1,'strength','abs-core'),(2,'Upper Body Blast','upper-body-blast','Strength · Free','Chest, shoulders aur arms — pure upper body firepower.','https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=60&auto=format&fit=crop',1,'strength','upper-body'),(3,'Lower Body Power','lower-body-power','Legs · Pro','Squats, lunges aur explosive leg work — powerful legs ke liye.','https://images.unsplash.com/photo-1434608519344-49d77a699e1d?w=600&q=60&auto=format&fit=crop',0,'strength','lower-body'),(4,'Full Body Burn','full-body-burn','Cardio · Free','Head-to-toe circuit jo calories tezi se jalata hai.','https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=60&auto=format&fit=crop',1,'hiit-cardio','cardio'),(5,'Dumbbell Strength','dumbbell-strength','Weights · Pro','Classic dumbbell moves — serious muscle gain.','https://images.unsplash.com/photo-1594381898411-846e7d193883?w=600&q=60&auto=format&fit=crop',0,'strength','upper-body'),(6,'Yoga & Stretch','yoga-stretch','Recovery · Free','Calm poses aur deep stretches — warrior monk recovery.','https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=600&q=60&auto=format&fit=crop',1,'yoga-stretching','yoga'),(7,'Fat Burn HIIT','fat-burn-hiit','HIIT · Pro','Maximum intensity intervals. Forge me fat melt karo.','https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=600&q=60&auto=format&fit=crop',0,'hiit-cardio','cardio'),(8,'Plank Challenge','plank-challenge','Core · Free','Hold the line. 3 brutal holds me iron core.','https://images.unsplash.com/photo-1566241142559-40e1dab266c6?w=600&q=60&auto=format&fit=crop',1,'strength','abs-core'),(9,'Knee-Safe Strength','knee-safe-strength','Medical · Free','Doctor-approved: no jumps, no deep squats. Knee pain safe.','https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&q=60&auto=format&fit=crop',1,'warmup-recovery','recovery'),(10,'Full Body','full-body','Full Body ┬À Free','The complete full-body circuit ÔÇö every major muscle group hit in one flowing 10-minute session. Your daily foundation.','https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800&q=70&auto=format&fit=crop',1,'strength','full-body'),(11,'Insane Six Pack','insane-six-pack','Abs ┬À Free','Six relentless ab movements back-to-back ÔÇö carve a shredded, rock-solid core.','https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&q=70&auto=format&fit=crop',1,'strength','abs-core'),(12,'Complex Core','complex-core','Core ┬À Pro','Advanced anti-rotation and stability work for a bulletproof midsection.','https://images.unsplash.com/photo-1544033527-b192daee1f5b?w=800&q=70&auto=format&fit=crop',0,'strength','abs-core'),(13,'Strong Back','strong-back','Back ┬À Pro','Rows, holds and posterior-chain work to build a powerful, pain-free back.','https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=800&q=70&auto=format&fit=crop',0,'strength','abs-core'),(14,'Complex Lower Body','complex-lower-body','Legs ┬À Pro','Compound squats, lunges and holds that forge unbreakable legs.','https://images.unsplash.com/photo-1434608519344-49d77a699e1d?w=800&q=70&auto=format&fit=crop',0,'strength','lower-body'),(15,'Explosive Power Jumps','explosive-power-jumps','Power ┬À Pro','Plyometric jumps and explosive drives for athletic, fast-twitch power.','https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=800&q=70&auto=format&fit=crop',0,'strength','lower-body'),(16,'Amazing Butt','amazing-butt','Glutes ┬À Free','Glute-focused squats and bridges that build and sculpt your posterior.','https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&q=70&auto=format&fit=crop',1,'strength','lower-body'),(17,'Complex Upper Body','complex-upper-body','Upper ┬À Pro','Pushes, pulls and presses combined into a complete upper-body builder.','https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=800&q=70&auto=format&fit=crop',0,'strength','upper-body'),(18,'Chest & Arms','chest-arms','Arms ┬À Free','Push-ups and curls stacked to pump the chest, biceps and triceps.','https://images.unsplash.com/photo-1532029837206-abbe2b7620e3?w=800&q=70&auto=format&fit=crop',1,'strength','upper-body'),(19,'Shoulders and Upper Back','shoulders-upper-back','Delts ┬À Pro','Overhead presses and rows for capped shoulders and a thick upper back.','https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=800&q=70&auto=format&fit=crop',0,'strength','upper-body'),(20,'HIIT','hiit','HIIT ┬À Free','High-intensity intervals that spike your heart rate and torch calories fast.','https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=800&q=70&auto=format&fit=crop',1,'hiit-cardio','cardio'),(21,'Light Cardio','light-cardio','Cardio ┬À Pro','Low-impact steady cardio to warm the engine and build your aerobic base.','https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=800&q=70&auto=format&fit=crop',0,'hiit-cardio','cardio'),(22,'Tabata','tabata','Tabata ┬À Pro','Classic 20-on / 10-off protocol ÔÇö brutal, efficient, unforgettable.','https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=800&q=70&auto=format&fit=crop',0,'hiit-cardio','cardio'),(23,'Cardio-Strength Intervals','cardio-strength-intervals','Hybrid ┬À Pro','Alternating cardio bursts and strength holds for total-body conditioning.','https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?w=800&q=70&auto=format&fit=crop',0,'hiit-cardio','cardio'),(24,'Plyometrics','plyometrics','Cardio ┬À Free','Explosive jump training to build speed, strength, and raw athletic power.','https://images.unsplash.com/photo-1601422407692-ec4eeec1d9b3?w=800&q=70&auto=format&fit=crop',1,'hiit-cardio','special'),(25,'Joint Friendly','joint-friendly','Cardio ┬À Pro','Low-impact cardio circuit designed to protect your joints while burning fat.','https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&q=70&auto=format&fit=crop',0,'hiit-cardio','special'),(26,'Full Body Flexibility','full-body-flexibility','Yoga ┬À Free','A complete stretching routine to unlock stiffness and improve full-body range of motion.','https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=70&auto=format&fit=crop',1,'yoga-stretching','yoga'),(27,'For Runners','for-runners','Stretch ┬À Pro','Targeted leg and hip release flow to improve recovery and stride length.','https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=800&q=70&auto=format&fit=crop',0,'yoga-stretching','yoga'),(28,'Healthy Back','healthy-back','Back ┬À Free','Gentle spine-opening stretches to relieve tension and support lower back health.','https://images.unsplash.com/photo-1599447421416-3414500d18a5?w=800&q=70&auto=format&fit=crop',1,'yoga-stretching','yoga'),(29,'Morning Yoga','morning-yoga','Yoga ┬À Pro','An energizing morning flow to wake up the body, stimulate circulation, and focus the mind.','https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800&q=70&auto=format&fit=crop',0,'yoga-stretching','yoga'),(30,'Yoga for Sleep','yoga-for-sleep','Calm ┬À Free','Restorative, deeply relaxing poses to calm the nervous system and prepare for deep rest.','https://images.unsplash.com/photo-1524863479829-916d8e77f114?w=800&q=70&auto=format&fit=crop',1,'yoga-stretching','yoga'),(31,'More Yoga','more-yoga','Yoga ┬À Pro','Vinyasa-inspired strength-building flows to level up your posture and core balance.','https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&q=70&auto=format&fit=crop',0,'yoga-stretching','yoga'),(32,'Warm Up','warm-up','Warmup ┬À Free','Gentle movements and dynamic stretches to prime the muscles for intense exercise.','https://images.unsplash.com/photo-1517838277536-f5f99be501cd?w=800&q=70&auto=format&fit=crop',1,'warmup-recovery','recovery'),(33,'Cool Down','cool-down','Cooldown ┬À Free','Post-workout static stretches to lower heart rate and jumpstart muscle repair.','https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&q=70&auto=format&fit=crop',1,'warmup-recovery','recovery'),(34,'Full Body Rolling','full-body-rolling','Rolling ┬À Pro','Foam rolling techniques to release myofascial knots and increase circulation.','https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=800&q=70&auto=format&fit=crop',0,'warmup-recovery','rolling'),(35,'Back Rolling','back-rolling','Rolling ┬À Free','Focused foam rolling for the upper and lower back to ease tension and improve alignment.','https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&q=70&auto=format&fit=crop',1,'warmup-recovery','rolling'),(36,'Legs Rolling','legs-rolling','Rolling ┬À Pro','Deep myofascial release for the hamstrings, quadriceps, and IT band.','https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=800&q=70&auto=format&fit=crop',0,'warmup-recovery','rolling'),(37,'Neck Release','neck-release','Stretching ┬À Free','Gentle stretches and holds to alleviate chronic neck, upper traps, and shoulder stiffness.','https://images.unsplash.com/photo-1594737625785-a6cbdabd333c?w=800&q=70&auto=format&fit=crop',1,'warmup-recovery','recovery');
/*!40000 ALTER TABLE `workouts` ENABLE KEYS */;
UNLOCK TABLES;

-- Demo portal access: each portal account can exercise premium-only flows in
-- a local installation. Remove these accounts before deploying publicly.
UPDATE `users` SET `plan` = 'elite'
WHERE `email` IN (
  'member@corecalorieadvisor.com',
  'patient@corecalorieadvisor.com',
  'trainer@corecalorieadvisor.com',
  'doctor@corecalorieadvisor.com',
  'admin@corecalorieadvisor.com',
  'pro@corecalorieadvisor.com'
);

--
-- Dumping events for database 'core_calorie_advisor'
--

--
-- Dumping routines for database 'core_calorie_advisor'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-30 11:15:57
