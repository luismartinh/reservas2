-- MySQL Workbench Synchronization
-- Generated: 2025-11-07 18:24
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: martinh

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `cabanas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(45) NOT NULL,
  `checkin` TIME NOT NULL,
  `checkout` TIME NOT NULL,
  `max_pax` SMALLINT(6) NOT NULL,
  `activa` SMALLINT(6) NOT NULL,
  `caracteristicas` JSON NULL DEFAULT NULL,
  `config` JSON NULL,
  `numero` SMALLINT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) ,
  UNIQUE INDEX `numero_UNIQUE` (`numero` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `reservas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATETIME NOT NULL,
  `desde` DATETIME NOT NULL,
  `hasta` DATETIME NOT NULL,
  `id_locador` INT(11) NOT NULL,
  `pax` SMALLINT(6) NOT NULL,
  `id_estado` INT(11) NOT NULL,
  `obs` MEDIUMTEXT NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_reservas_3_idx` (`id_locador` ASC) ,
  INDEX `fk_reservas_1_idx` (`id_estado` ASC) ,
  CONSTRAINT `fk_reservas_3`
    FOREIGN KEY (`id_locador`)
    REFERENCES `locadores` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_reservas_1`
    FOREIGN KEY (`id_estado`)
    REFERENCES `estados` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `tarifas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATETIME NOT NULL,
  `descr` VARCHAR(45) NOT NULL,
  `inicio` DATETIME NOT NULL,
  `fin` DATETIME NOT NULL,
  `valor_dia` DOUBLE NOT NULL,
  `min_dias` SMALLINT(6) NOT NULL,
  `activa` SMALLINT(6) NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `locadores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `denominacion` VARCHAR(100) NOT NULL,
  `documento` VARCHAR(45) NOT NULL,
  `email` VARCHAR(45) NOT NULL,
  `telefono` VARCHAR(45) NOT NULL,
  `domicilio` VARCHAR(100) NULL DEFAULT NULL,
  `documentos` JSON NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `documento_UNIQUE` (`documento` ASC) ,
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `request_reservas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATETIME NOT NULL,
  `desde` DATETIME NOT NULL,
  `hasta` DATETIME NOT NULL,
  `denominacion` VARCHAR(100) NOT NULL,
  `email` VARCHAR(45) NOT NULL,
  `pax` SMALLINT(6) NOT NULL,
  `hash` VARCHAR(45) NOT NULL,
  `total` DOUBLE NOT NULL,
  `id_estado` INT(11) NOT NULL,
  `id_reserva` INT(11) NULL DEFAULT NULL,
  `obs` MEDIUMTEXT NULL DEFAULT NULL,
  `fecha_request_pago` DATETIME NULL DEFAULT NULL,
  `registro_pagos` JSON NULL DEFAULT NULL,
  `pagado` DOUBLE NULL DEFAULT NULL,
  `email_token` varchar(64) DEFAULT NULL,
  `email_token_expira` datetime DEFAULT NULL,
  `codigo_reserva` VARCHAR(45) NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_request_reservas_1_idx` (`id_reserva` ASC) ,
  INDEX `fk_request_reservas_2_idx` (`id_estado` ASC) ,
  UNIQUE INDEX `uk_reques_reservas_3_idx` (`email` ASC, `codigo_reserva` ASC) ,
  CONSTRAINT `fk_request_reservas_1`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `reservas` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_request_reservas_2`
    FOREIGN KEY (`id_estado`)
    REFERENCES `estados` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `request_responses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_request` INT(11) NOT NULL,
  `fecha` DATETIME NOT NULL,
  `response` MEDIUMTEXT NOT NULL,
  `is_response` SMALLINT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_request_responses_1_idx` (`id_request` ASC) ,
  CONSTRAINT `fk_request_responses_1`
    FOREIGN KEY (`id_request`)
    REFERENCES `request_reservas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `request_cabanas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cabana` INT(11) NOT NULL,
  `id_request` INT(11) NOT NULL,
  `valor` DOUBLE NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_request_cabanas_1_idx` (`id_cabana` ASC) ,
  INDEX `fk_request_cabanas_2_idx` (`id_request` ASC) ,
  UNIQUE INDEX `ux_request_cabanas_1` (`id_cabana` ASC, `id_request` ASC) ,
  CONSTRAINT `fk_request_cabanas_1`
    FOREIGN KEY (`id_cabana`)
    REFERENCES `cabanas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_request_cabanas_2`
    FOREIGN KEY (`id_request`)
    REFERENCES `request_reservas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `reserva_cabanas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cabana` INT(11) NOT NULL,
  `id_reserva` INT(11) NOT NULL,
  `valor` DOUBLE NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_reserva_cabanas_1_idx` (`id_cabana` ASC) ,
  INDEX `fk_reserva_cabanas_2_idx` (`id_reserva` ASC) ,
  UNIQUE INDEX `ux_reserva_cabanas_1` (`id_cabana` ASC, `id_reserva` ASC) ,
  CONSTRAINT `fk_reserva_cabanas_1`
    FOREIGN KEY (`id_cabana`)
    REFERENCES `cabanas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_reserva_cabanas_2`
    FOREIGN KEY (`id_reserva`)
    REFERENCES `reservas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `estados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(45) NOT NULL,
  `slug` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) ,
  UNIQUE INDEX `slug_UNIQUE` (`slug` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `cabanas_tarifas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_cabana` INT(11) NOT NULL,
  `id_tarifa` INT(11) NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_cabanas_tarifas_1_idx` (`id_cabana` ASC) ,
  INDEX `fk_cabanas_tarifas_2_idx` (`id_tarifa` ASC) ,
  UNIQUE INDEX `uk_cabanas_tarifas_idx` (`id_cabana` ASC, `id_tarifa` ASC) ,
  CONSTRAINT `fk_cabanas_tarifas_1`
    FOREIGN KEY (`id_cabana`)
    REFERENCES `cabanas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_cabanas_tarifas_2`
    FOREIGN KEY (`id_tarifa`)
    REFERENCES `tarifas` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (1,'Falta verificar email',  'pendiente-email-verificar');
INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (2,'Email verificado', 'pendiente-email-verificado');
INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (3,'Pendiente de pago', 'pendiente-email-contestado');
INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (4,'Reservado sin verificar', 'confirmado-verificar-pago');
INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (5,'Confirmado', 'confirmado');
INSERT INTO `estados` (`id`, `descr`, `slug`) VALUES (6,'Rechazado', 'rechazado');

INSERT INTO `parametros_generales` (`clave`, `descr`, `valor`) VALUES ('RESERVA_CFG', 'Parametros de reserva', '{\n\"max_horas_venc\":{\n	\"request_reserva\":48,\n	\"confirmar_pago\":48\n}\n}');
UPDATE `parametros_generales` SET `valor` = '{\n    \"max_horas_venc\": {\n        \"confirmar_pago\": 48,\n        \"request_reserva\": 48\n    },\n    \"max_reintentos\": {\n        \"request_reserva\": 5\n    }\n}' WHERE (`clave` = 'RESERVA_CFG');
UPDATE `parametros_generales` SET `valor` = '{\n    \"max_horas_venc\": {\n        \"confirmar_pago\": 48,\n        \"request_reserva\": 48,\n		\"email_token_expira\": 48\n    },\n    \"max_reintentos\": {\n        \"request_reserva\": 5\n    }\n}' WHERE (`clave` = 'RESERVA_CFG');
INSERT INTO `notif_tablas` (`tabla`, `enabled`) VALUES ('request_reservas', '1');

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
