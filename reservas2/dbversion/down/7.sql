-- Reversión de la versión 7 mediante el actualizador personalizado.
-- Destructiva: elimina referencia, calificacion, id_localidad y ambos catálogos.
-- MySQL realiza COMMIT implícito en DDL. Respaldar antes de ejecutar.
-- DBVRESION es actualizado a 6 por UpdateDBForm, no por este archivo.

ALTER TABLE `locadores`
  DROP FOREIGN KEY `fk_locadores_localidad`,
  DROP INDEX `idx_locadores_localidad`,
  DROP COLUMN `id_localidad`,
  DROP COLUMN `calificacion`;

ALTER TABLE `request_reservas` DROP COLUMN `referencia`;

DROP TABLE `localidades`;
DROP TABLE `provincias`;
