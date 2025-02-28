-- MySQL Workbench Synchronization
-- Generated: 2025-02-27 13:23
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: martinh

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS  rubro (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS  subrubro (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS  rubro_subrubro (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_rubro` INT(11) NOT NULL,
  `id_subrubro` INT(11) NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `updated_by` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_rubro_idx` (`id_rubro` ASC) ,
  INDEX `fk_subrubro_idx` (`id_subrubro` ASC) ,
  CONSTRAINT `fk_rubro`
    FOREIGN KEY (`id_rubro`)
    REFERENCES  .`rubro` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_subrubro`
    FOREIGN KEY (`id_subrubro`)
    REFERENCES  .`subrubro` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;






START TRANSACTION;

-- Insertar en rubro solo si no existe
INSERT INTO rubro (`descr`) 
SELECT 'rubro de prueba' 
WHERE NOT EXISTS (SELECT 1 FROM rubro WHERE `descr` = 'rubro de prueba');

-- Obtener el ID del rubro (ya existente o recién insertado)
SET @idRubro = (SELECT id FROM rubro WHERE `descr` = 'rubro de prueba' LIMIT 1);

-- Insertar en subrubro solo si no existe
INSERT INTO subrubro (`descr`) 
SELECT 'subrubro de prueba' 
WHERE NOT EXISTS (SELECT 1 FROM subrubro WHERE `descr` = 'subrubro de prueba');

-- Obtener el ID del subrubro (ya existente o recién insertado)
SET @idSubrubro = (SELECT id FROM subrubro WHERE `descr` = 'subrubro de prueba' LIMIT 1);

-- Insertar en rubro_subrubro solo si no existe
INSERT IGNORE INTO rubro_subrubro (`id_rubro`, `id_subrubro`) VALUES (@idRubro, @idSubrubro);

SHOW ERRORS;

COMMIT;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
