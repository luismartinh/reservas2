-- MySQL Workbench Synchronization
-- Generated: 2025-02-27 13:51
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: martinh

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


CREATE TABLE IF NOT EXISTS `info_personal` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `denominacion` VARCHAR(100) NOT NULL,
  `doc` VARCHAR(25) NOT NULL,
  `domicilio` VARCHAR(100) NULL DEFAULT NULL,
  `email` VARCHAR(45) NULL DEFAULT NULL,
  `telefono` VARCHAR(85) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `denominacion_UNIQUE` (`denominacion` ASC) ,
  UNIQUE INDEX `doc_UNIQUE` (`doc` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


CREATE TABLE IF NOT EXISTS `proveedor` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(45) NOT NULL,
  `id_info_personal` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_info_personal_idx` (`id_info_personal` ASC) ,
  UNIQUE INDEX `descr_UNIQUE` (`descr` ASC) ,
  CONSTRAINT `fk_info_personal`
    FOREIGN KEY (`id_info_personal`)
    REFERENCES `bt5`.`info_personal` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


START TRANSACTION;

-- Intentar insertar solo si no existe
INSERT INTO info_personal (`denominacion`, `doc`) 
SELECT 'Proveedor de prueba', '12345678' 
WHERE NOT EXISTS (
    SELECT 1 FROM info_personal WHERE denominacion = 'Proveedor de prueba' OR doc = '12345678'
);


-- Obtener el ID, ya sea insertado o existente
SET @idinfo_personal = (SELECT id FROM info_personal WHERE denominacion = 'Proveedor de prueba' OR doc = '12345678'LIMIT 1);


INSERT IGNORE INTO proveedor (`descr`, `id_info_personal`) 
VALUES ('Proveedor de prueba', @idinfo_personal);

SHOW ERRORS;
COMMIT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

