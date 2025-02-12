-- MySQL dump 10.13  Distrib 8.0.36, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: bt5
-- ------------------------------------------------------
-- Server version	5.7.44

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
-- Table structure for table `acceso`
--

DROP TABLE IF EXISTS `acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `acceso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descr` varchar(80) NOT NULL,
  `acceso` varchar(80) NOT NULL,
  `config` json DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `id_menu` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Acceso_UNIQUE` (`acceso`),
  KEY `fk_acceso_menu_idx` (`id_menu`),
  CONSTRAINT `fk_acceso_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acceso`
--

LOCK TABLES `acceso` WRITE;
/*!40000 ALTER TABLE `acceso` DISABLE KEYS */;
INSERT INTO `acceso` VALUES (6,'Administrar Usuarios','usuario/index',NULL,1,1,'2025-02-07 20:34:20','2025-02-07 22:09:46',2),(7,'Administrar Grupos de acceso','grupo-acceso/index',NULL,1,1,'2025-02-08 11:11:56','2025-02-08 11:11:56',5),(8,'Crear usuarios','usuario/create',NULL,1,1,'2025-02-08 11:16:32','2025-02-08 11:16:32',NULL),(9,'Modificar usuarios','usuario/update',NULL,1,1,'2025-02-08 11:21:12','2025-02-08 11:21:12',NULL),(10,'Eliminar usuarios','usuario/delete',NULL,1,1,'2025-02-08 11:22:24','2025-02-08 11:22:24',NULL),(11,'Crear grupos de acceso','grupo-acceso/create',NULL,1,1,'2025-02-08 11:25:15','2025-02-08 11:25:15',NULL),(12,'Modificar grupos de acceso','grupo-acceso/update',NULL,1,1,'2025-02-08 11:25:55','2025-02-08 11:25:55',NULL),(13,'Eliminar grupos de acceso','grupo-acceso/delete',NULL,1,1,'2025-02-08 11:26:47','2025-02-08 11:26:47',NULL),(14,'Modificar permisos de grupos de acceso','grupo-acceso/view',NULL,1,1,'2025-02-08 11:28:43','2025-02-08 11:28:43',NULL),(15,'Modificar permisos de usuarios','usuario/view',NULL,1,1,'2025-02-08 11:34:01','2025-02-08 11:34:01',NULL),(16,'Modificar mi usuario','usuario/mi-update',NULL,1,1,'2025-02-10 11:27:04','2025-02-10 11:31:28',6),(17,'Ver Auditoria cambios','auditoria/index',NULL,1,1,'2025-02-10 18:32:03','2025-02-10 18:32:03',7),(18,'Administrar Auditoria cambios','auditoria-tabla/index',NULL,1,1,'2025-02-10 19:26:44','2025-02-10 19:26:44',NULL),(19,'Administrar notificaciones','notif-tablas/index',NULL,1,1,'2025-02-11 08:35:34','2025-02-11 08:35:34',8),(20,'Administrar parametros generales','parametros-generales/index',NULL,1,1,'2025-02-11 14:22:18','2025-02-11 14:22:18',9),(21,'Modificar parametros','parametros-generales/update',NULL,1,1,'2025-02-11 14:23:24','2025-02-11 14:23:24',NULL),(22,'Auditoria eliminar todas','auditoria/delete-todas',NULL,1,1,'2025-02-11 17:57:43','2025-02-11 17:57:43',NULL);
/*!40000 ALTER TABLE `acceso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditoria_tablas`
--

DROP TABLE IF EXISTS `auditoria_tablas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_tablas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tabla` varchar(45) NOT NULL,
  `enabled` smallint(1) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tabla_UNIQUE` (`tabla`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_tablas`
--

LOCK TABLES `auditoria_tablas` WRITE;
/*!40000 ALTER TABLE `auditoria_tablas` DISABLE KEYS */;
INSERT INTO `auditoria_tablas` VALUES (1,'grupos_accesos_accesos',1,NULL,NULL,'2025-02-10 19:24:35',1),(2,'acceso',1,NULL,NULL,NULL,NULL),(3,'grupo_acceso',1,NULL,NULL,NULL,NULL),(4,'grupos_accesos_usuarios',1,NULL,NULL,'2025-02-10 19:25:00',1),(5,'usuario',1,NULL,NULL,'2025-02-11 10:08:13',1),(6,'usuarios_accesos',1,NULL,NULL,'2025-02-10 19:48:10',1),(7,'parametros_generales',1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `auditoria_tablas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditorias`
--

DROP TABLE IF EXISTS `auditorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditorias` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tabla` varchar(45) NOT NULL,
  `changes` json NOT NULL,
  `user` varchar(45) NOT NULL,
  `action` varchar(45) NOT NULL,
  `pkId` json NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditorias`
--

LOCK TABLES `auditorias` WRITE;
/*!40000 ALTER TABLE `auditorias` DISABLE KEYS */;
/*!40000 ALTER TABLE `auditorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_acceso`
--

DROP TABLE IF EXISTS `grupo_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_acceso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descr` varchar(45) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `nivel` smallint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Descripcion_UNIQUE` (`descr`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_acceso`
--

LOCK TABLES `grupo_acceso` WRITE;
/*!40000 ALTER TABLE `grupo_acceso` DISABLE KEYS */;
INSERT INTO `grupo_acceso` VALUES (1,'grupo admin',100,100,'2025-02-06 00:32:18','2025-02-07 17:39:58',1),(2,'grupo gerentes',100,100,'2025-02-06 00:39:09','2025-02-07 17:40:14',2),(3,'grupo sysadmin',100,100,NULL,'2025-02-07 17:38:26',0),(6,'grupo usuarios web',1,1,'2025-02-10 12:35:24','2025-02-10 12:35:24',5),(7,'grupo operador',1,1,'2025-02-10 12:41:26','2025-02-10 12:41:26',3),(8,'grupo operador limitado',1,1,'2025-02-10 12:41:59','2025-02-10 12:41:59',4);
/*!40000 ALTER TABLE `grupo_acceso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos_accesos_accesos`
--

DROP TABLE IF EXISTS `grupos_accesos_accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos_accesos_accesos` (
  `id_grupo_acceso` int(11) NOT NULL,
  `id_acceso` int(11) NOT NULL,
  PRIMARY KEY (`id_grupo_acceso`,`id_acceso`),
  KEY `fk_accesos_3` (`id_acceso`),
  KEY `fk_grupos_accesos_3` (`id_grupo_acceso`),
  CONSTRAINT `fk_accesos_3` FOREIGN KEY (`id_acceso`) REFERENCES `acceso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grupos_accesos_3` FOREIGN KEY (`id_grupo_acceso`) REFERENCES `grupo_acceso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos_accesos_accesos`
--

LOCK TABLES `grupos_accesos_accesos` WRITE;
/*!40000 ALTER TABLE `grupos_accesos_accesos` DISABLE KEYS */;
INSERT INTO `grupos_accesos_accesos` VALUES (1,6),(3,6),(1,7),(2,7),(3,7),(7,7),(1,8),(3,8),(1,9),(3,9),(1,10),(3,10),(1,11),(3,11),(1,12),(3,12),(1,13),(3,13),(1,14),(3,14),(1,15),(3,15),(1,16),(2,16),(3,16),(6,16),(8,16),(3,17),(3,18),(3,19),(3,20),(3,21),(3,22);
/*!40000 ALTER TABLE `grupos_accesos_accesos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos_accesos_usuarios`
--

DROP TABLE IF EXISTS `grupos_accesos_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos_accesos_usuarios` (
  `id_grupo_acceso` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_grupo_acceso`,`id_usuario`),
  KEY `fk_usuarios_2` (`id_usuario`),
  KEY `fk_grupos_1` (`id_grupo_acceso`),
  CONSTRAINT `fk_grupos_1` FOREIGN KEY (`id_grupo_acceso`) REFERENCES `grupo_acceso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos_accesos_usuarios`
--

LOCK TABLES `grupos_accesos_usuarios` WRITE;
/*!40000 ALTER TABLE `grupos_accesos_usuarios` DISABLE KEYS */;
INSERT INTO `grupos_accesos_usuarios` VALUES (3,1),(1,2),(2,3),(6,25);
/*!40000 ALTER TABLE `grupos_accesos_usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descr` varchar(85) NOT NULL,
  `label` varchar(45) NOT NULL,
  `menu` varchar(45) NOT NULL,
  `menu_path` varchar(85) NOT NULL,
  `url` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE` (`menu`,`menu_path`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (2,'Administrar Usuarios','Usuarios','0','Seguridad/Usuarios','usuario/index'),(5,'Administrar Grupos de acceso','Grupos de acceso','0','Seguridad/Grupos','grupo-acceso/index'),(6,'Modificar mi perfil','Mi usuario','5','Yo','usuario/mi-update'),(7,'Ver Auditoria cambios','Auditoria','0','Seguridad/Auditoria','auditoria/index'),(8,'Administrar notificaciones','Auditoria','0','Seguridad/Notificaciones','notif-tablas/index'),(9,'Administrar parametros generales','Usuarios','0','Seguridad/Parametros','parametros-generales/index');
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notif_mensajes`
--

DROP TABLE IF EXISTS `notif_mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notif_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msg` text NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notif_mensajes`
--

LOCK TABLES `notif_mensajes` WRITE;
/*!40000 ALTER TABLE `notif_mensajes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notif_mensajes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notif_tablas`
--

DROP TABLE IF EXISTS `notif_tablas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notif_tablas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tabla` varchar(45) NOT NULL,
  `enabled` smallint(1) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tabla_UNIQUE` (`tabla`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notif_tablas`
--

LOCK TABLES `notif_tablas` WRITE;
/*!40000 ALTER TABLE `notif_tablas` DISABLE KEYS */;
INSERT INTO `notif_tablas` VALUES (1,'grupos_accesos_accesos',1,NULL,NULL,'2025-02-11 08:30:14',1),(2,'acceso',1,NULL,NULL,'2025-02-11 08:30:33',1),(3,'grupo_acceso',1,NULL,NULL,NULL,NULL),(4,'grupos_accesos_usuarios',1,NULL,NULL,NULL,NULL),(5,'usuario',1,NULL,NULL,'2025-02-11 09:06:35',1),(6,'usuarios_accesos',1,NULL,NULL,NULL,NULL),(7,'parametros_generales',1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `notif_tablas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_msg` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `leida` smallint(1) DEFAULT NULL,
  `tabla` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notificaciones_msg_idx` (`id_msg`),
  KEY `fk_notificaciones_user_idx` (`id_user`),
  CONSTRAINT `fk_notificaciones_msg` FOREIGN KEY (`id_msg`) REFERENCES `notif_mensajes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notificaciones_user` FOREIGN KEY (`id_user`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametros_generales`
--

DROP TABLE IF EXISTS `parametros_generales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametros_generales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(45) NOT NULL,
  `descr` varchar(255) DEFAULT NULL,
  `valor` json NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave_UNIQUE` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametros_generales`
--

LOCK TABLES `parametros_generales` WRITE;
/*!40000 ALTER TABLE `parametros_generales` DISABLE KEYS */;
INSERT INTO `parametros_generales` VALUES (1,'MAIL_CFG','Configuracion proveedor de mails para el envio','{\"dsn\": \"smtp://2ae2b0f72ef658:3a5db127caecad@sandbox.smtp.mailtrap.io:2525?encryption=tls\"}',NULL,NULL,'2025-02-11 14:33:32',1);
/*!40000 ALTER TABLE `parametros_generales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `nombre` varchar(45) NOT NULL,
  `apellido` varchar(45) NOT NULL,
  `pwd` varchar(45) NOT NULL,
  `id_session` varchar(150) DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `auth_key` varchar(32) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `user_sign_token` varchar(255) DEFAULT NULL,
  `access_token` varchar(255) DEFAULT NULL,
  `locate` varchar(10) DEFAULT NULL,
  `nivel` smallint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Login_UNIQUE` (`login`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'sysadmin','sysadmin nmbre','sysadmin','pRStKx149I-Nly-HsmPEup-jizXgMTHT','1','2025-02-11 20:17:59','172.19.0.1','45679879',1,'OQBcV2aO7jFak7B4aAWHy3jicwe35H-o','$2y$13$w7YtxQrlSX4QukS.K48jU.dvdn7Pl6lML.SAN76M8hHVFvlMT5/8K',NULL,'sysadmin@com.ar',1738802323,1739305079,100,1,'','','',0),(2,'admin','admin','admin','DUCaUKgEREocawljJKp32YbZVMhlOUIe','2','2025-02-10 15:19:18','172.19.0.1','',1,'GXLwis_K2GX1AyAPs8O7Mc4dmBXCdmrw','$2y$13$484iPYcrHH.GOWnDGbdaleGUGxCyEWRc7P/8XSikk.KFPhilCCjLe',NULL,'admin@com.ar',1738854734,1739305067,100,NULL,NULL,NULL,NULL,1),(3,'gerente','usuario2','usuario2','TOI9c7trU4z0IKP8OXCdWbqmtjrdXCp1',NULL,'2025-02-05 10:00:00',NULL,'1234',1,'gAELMiSAa1u5Os4Fy5jWQYk_thYNYmvy','$2y$13$QzD3kJ4yE6xlz0XrjTru2OVRxBOQNFbJhA/9ZB95cJh3oVykgq0WC',NULL,'usus4@com.ar',1738854900,1739033093,100,2,NULL,NULL,NULL,2),(4,'operador','usuario5','us','us',NULL,'2025-02-04 12:00:00',NULL,'',1,NULL,NULL,NULL,'usus6@com.ar',1738855128,1738960463,100,100,NULL,NULL,NULL,3),(5,'webuser','usuario5','us','ffsfsdfdfdsf',NULL,'2025-02-03 20:00:00',NULL,'',1,NULL,NULL,NULL,'usus7@com.ar',1738855602,1738960483,100,100,NULL,NULL,NULL,5),(25,'usuario','pepe','nuevo','XXbAePV8oxAy84cV_y5II6j4rdLnd2Vj','25','2025-02-10 15:38:32','172.19.0.1','',1,'yrfWf1Fh__-4e3Zmk2xJvXW3LtICqy5U','$2y$13$/q0X0Zuur67zykQVKU53QOYOWgZ6jP2b1GYTzKQcmdIucXdxV18dq',NULL,'usuario@com.ar',1738968522,1739202250,1,1,NULL,NULL,NULL,5);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios_accesos`
--

DROP TABLE IF EXISTS `usuarios_accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios_accesos` (
  `id_usuario` int(11) NOT NULL,
  `id_accesos` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_accesos`),
  KEY `fk_accesos_1` (`id_accesos`),
  KEY `fk_usuarios_1` (`id_usuario`),
  CONSTRAINT `fk_accesos_1fk` FOREIGN KEY (`id_accesos`) REFERENCES `acceso` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_1fk` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios_accesos`
--

LOCK TABLES `usuarios_accesos` WRITE;
/*!40000 ALTER TABLE `usuarios_accesos` DISABLE KEYS */;
INSERT INTO `usuarios_accesos` VALUES (5,6),(5,16);
/*!40000 ALTER TABLE `usuarios_accesos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-11 18:15:07
