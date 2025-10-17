-- MySQL Workbench Synchronization
-- Generated: 2025-02-27 13:51
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: martinh

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


CREATE TABLE IF NOT EXISTS proveedor_rubro (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_proveedor` INT NOT NULL,
  `id_rubro` INT NOT NULL,
  `created_at` DATETIME NULL,
  `created_by` INT NULL,
  `updated_at` DATETIME NULL,
  `updated_by` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unico` (`id_proveedor` ASC, `id_rubro` ASC),
  INDEX `fk_proveedor_rubro_1_idx` (`id_rubro` ASC),
  CONSTRAINT `fk_proveedor_rubro_1`
    FOREIGN KEY (`id_rubro`)
    REFERENCES `rubro` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_proveedor_rubro_2`
    FOREIGN KEY (`id_proveedor`)
    REFERENCES `proveedor` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);






SET @idProveedor = (SELECT id FROM proveedor WHERE descr = 'Proveedor de prueba' LIMIT 1);
SET @idRubro = (SELECT id FROM rubro WHERE `descr` = 'rubro de prueba' LIMIT 1);


INSERT IGNORE INTO proveedor_rubro (`id_proveedor`, `id_rubro`) VALUES (@idProveedor, @idRubro);

SHOW ERRORS;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

