-- MySQL Workbench Synchronization
-- Generated: 2025-11-06 13:04
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: martinh

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

drop table cabanas_tarifas;
drop table request_responses;
drop table request_reservas;
drop table request_cabanas;
drop table reserva_cabanas;
drop table reservas;
drop table locadores;
drop table tarifas;
drop table cabanas;
drop table estados;

DELETE FROM `parametros_generales` WHERE (`clave` = 'RESERVA_CFG');
DELETE FROM `notif_tablas` WHERE (`tabla` = 'request_reservas');

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
