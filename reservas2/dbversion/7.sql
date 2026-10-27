-- Versión 7: referencia de solicitudes, calificación y ubicación de locadores.
-- Preparada el 2026-10-27. Aplicar mediante el actualizador personalizado DBVRESION.
-- Tablas reales: request_reservas y locadores. No utiliza migraciones Yii2.
-- Fuente oficial: Georef (IGN / INDEC), descarga completa API v2.0:
-- https://apis.datos.gob.ar/georef/api/v2.0/provincias.json
-- https://apis.datos.gob.ar/georef/api/v2.0/localidades.json
-- Documentación: https://www.argentina.gob.ar/georef/descarga-de-la-base-completa
-- 24 jurisdicciones (23 provincias y CABA); 4028 registros de localidades de origen.
-- Se unifican 105 repeticiones con igual provincia, nombre normalizado,
-- departamento y localidad censal: quedan 3923 localidades.
-- Homónimos de una provincia se distinguen por departamento en descr.
-- descr VARCHAR(150) conserva los nombres completos; slug VARCHAR(45).
-- Los IDs de provincias son códigos Georef numéricos; IDs de localidades internos.
-- Algunos códigos Georef de localidades exceden INT, se documentan al pie.
-- Slugs ASCII; cuando exceden 45 caracteres se acortan y sufijan con el código.
-- Snapshot SHA256 provincias: 1da5632e8e2e3ba642e53e13159c3696126c4c6e2b8055f71a5aee01b8a9a444
-- Snapshot SHA256 localidades: 1513bfeacbcffe535faa552a23e0af39935c97fc2a5dd7767a4a92621f105a74
-- Ejecutar una sola vez, después de la versión 6. Mantiene activas las FK.
-- MySQL hace COMMIT implícito en DDL: el rollback transaccional del ejecutor
-- no deshace ALTER/CREATE. Respaldar antes y usar down/7.sql para revertir.

SET NAMES utf8mb4;

CREATE TABLE `provincias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `descr` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provincias_descr` (`descr`),
  UNIQUE KEY `uq_provincias_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `localidades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_provincia` INT(11) NOT NULL,
  `descr` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_localidades_provincia_descr` (`id_provincia`, `descr`),
  UNIQUE KEY `uq_localidades_provincia_slug` (`id_provincia`, `slug`),
  CONSTRAINT `fk_localidades_provincia` FOREIGN KEY (`id_provincia`)
    REFERENCES `provincias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `provincias` (`id`, `descr`, `slug`) VALUES
(2, 'Ciudad Autónoma de Buenos Aires', 'ciudad-autonoma-de-buenos-aires'),
(6, 'Buenos Aires', 'buenos-aires'),
(10, 'Catamarca', 'catamarca'),
(14, 'Córdoba', 'cordoba'),
(18, 'Corrientes', 'corrientes'),
(22, 'Chaco', 'chaco'),
(26, 'Chubut', 'chubut'),
(30, 'Entre Ríos', 'entre-rios'),
(34, 'Formosa', 'formosa'),
(38, 'Jujuy', 'jujuy'),
(42, 'La Pampa', 'la-pampa'),
(46, 'La Rioja', 'la-rioja'),
(50, 'Mendoza', 'mendoza'),
(54, 'Misiones', 'misiones'),
(58, 'Neuquén', 'neuquen'),
(62, 'Río Negro', 'rio-negro'),
(66, 'Salta', 'salta'),
(70, 'San Juan', 'san-juan'),
(74, 'San Luis', 'san-luis'),
(78, 'Santa Cruz', 'santa-cruz'),
(82, 'Santa Fe', 'santa-fe'),
(86, 'Santiago del Estero', 'santiago-del-estero'),
(90, 'Tucumán', 'tucuman'),
(94, 'Tierra del Fuego, Antártida e Islas del Atlántico Sur', 'tierra-del-fuego-antartida-e-islas-del-atl-94');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1, 2, 'Agronomía', 'agronomia'),
(2, 2, 'Almagro', 'almagro'),
(3, 2, 'Balvanera', 'balvanera'),
(4, 2, 'Barracas', 'barracas'),
(5, 2, 'Belgrano', 'belgrano'),
(6, 2, 'Boca', 'boca'),
(7, 2, 'Boedo', 'boedo'),
(8, 2, 'Caballito', 'caballito'),
(9, 2, 'Chacarita', 'chacarita'),
(10, 2, 'Ciudad de Buenos Aires', 'ciudad-de-buenos-aires'),
(11, 2, 'Coghlan', 'coghlan'),
(12, 2, 'Colegiales', 'colegiales'),
(13, 2, 'Constitución', 'constitucion'),
(14, 2, 'Flores', 'flores'),
(15, 2, 'Floresta', 'floresta'),
(16, 2, 'Liniers', 'liniers'),
(17, 2, 'Mataderos', 'mataderos'),
(18, 2, 'Monserrat', 'monserrat'),
(19, 2, 'Monte Castro', 'monte-castro'),
(20, 2, 'Nueva Pompeya', 'nueva-pompeya'),
(21, 2, 'Nuñez', 'nunez'),
(22, 2, 'Palermo', 'palermo'),
(23, 2, 'Parque Avellaneda', 'parque-avellaneda'),
(24, 2, 'Parque Chacabuco', 'parque-chacabuco'),
(25, 2, 'Parque Chas', 'parque-chas'),
(26, 2, 'Parque Patricios', 'parque-patricios'),
(27, 2, 'Paternal', 'paternal'),
(28, 2, 'Puerto Madero', 'puerto-madero'),
(29, 2, 'Recoleta', 'recoleta'),
(30, 2, 'Retiro', 'retiro'),
(31, 2, 'Saavedra', 'saavedra'),
(32, 2, 'San Cristóbal', 'san-cristobal'),
(33, 2, 'San Nicolás', 'san-nicolas'),
(34, 2, 'San Telmo', 'san-telmo'),
(35, 2, 'Vélez Sarsfield', 'velez-sarsfield'),
(36, 2, 'Versalles', 'versalles'),
(37, 2, 'Villa Crespo', 'villa-crespo'),
(38, 2, 'Villa del Parque', 'villa-del-parque'),
(39, 2, 'Villa Devoto', 'villa-devoto'),
(40, 2, 'Villa General Mitre', 'villa-general-mitre'),
(41, 2, 'Villa Lugano', 'villa-lugano'),
(42, 2, 'Villa Luro', 'villa-luro'),
(43, 2, 'Villa Ortúzar', 'villa-ortuzar'),
(44, 2, 'Villa Pueyrredón', 'villa-pueyrredon'),
(45, 2, 'Villa Real', 'villa-real'),
(46, 2, 'Villa Riachuelo', 'villa-riachuelo'),
(47, 2, 'Villa Santa Rita', 'villa-santa-rita'),
(48, 2, 'Villa Soldati', 'villa-soldati'),
(49, 2, 'Villa Urquiza', 'villa-urquiza'),
(50, 6, '11 de Septiembre', '11-de-septiembre'),
(51, 6, '12 de Octubre', '12-de-octubre'),
(52, 6, '16 de Julio', '16-de-julio'),
(53, 6, '17 de Agosto', '17-de-agosto'),
(54, 6, '20 de Junio', '20-de-junio'),
(55, 6, '25 de Mayo', '25-de-mayo'),
(56, 6, '30 de Agosto', '30-de-agosto'),
(57, 6, '9 de Abril', '9-de-abril'),
(58, 6, '9 de Julio', '9-de-julio'),
(59, 6, 'Abasto', 'abasto'),
(60, 6, 'Abbott', 'abbott'),
(61, 6, 'Acasusso', 'acasusso'),
(62, 6, 'Acevedo', 'acevedo'),
(63, 6, 'Adolfo Gonzales Chaves', 'adolfo-gonzales-chaves'),
(64, 6, 'Adrogué', 'adrogue'),
(65, 6, 'Aeropuerto Internacional Ezeiza', 'aeropuerto-internacional-ezeiza'),
(66, 6, 'Agustín Mosconi', 'agustin-mosconi'),
(67, 6, 'Agustín Roca', 'agustin-roca'),
(68, 6, 'Agustina', 'agustina'),
(69, 6, 'Alberdi Viejo', 'alberdi-viejo'),
(70, 6, 'Alberti', 'alberti'),
(71, 6, 'Aldo Bonzi', 'aldo-bonzi'),
(72, 6, 'Alejandro Korn', 'alejandro-korn'),
(73, 6, 'Alfredo Demarchi', 'alfredo-demarchi'),
(74, 6, 'Almirante Brown', 'almirante-brown'),
(75, 6, 'Altamirano', 'altamirano'),
(76, 6, 'Álvarez de Toledo', 'alvarez-de-toledo'),
(77, 6, 'Alvarez Jonte', 'alvarez-jonte'),
(78, 6, 'América', 'america'),
(79, 6, 'Andant', 'andant'),
(80, 6, 'Ángel Etcheverry', 'angel-etcheverry'),
(81, 6, 'Antonio Carboni', 'antonio-carboni'),
(82, 6, 'Aparicio', 'aparicio'),
(83, 6, 'Arana', 'arana'),
(84, 6, 'Arboledas', 'arboledas'),
(85, 6, 'Área de Promoción El Triángulo', 'area-de-promocion-el-triangulo'),
(86, 6, 'Área Reserva Cinturón Ecológico', 'area-reserva-cinturon-ecologico'),
(87, 6, 'Arenas Verdes', 'arenas-verdes'),
(88, 6, 'Arenaza', 'arenaza'),
(89, 6, 'Argerich', 'argerich'),
(90, 6, 'Ariel', 'ariel'),
(91, 6, 'Arrecifes', 'arrecifes'),
(92, 6, 'Arribeños', 'arribenos'),
(93, 6, 'Arroyo Corto', 'arroyo-corto'),
(94, 6, 'Arroyo Dulce', 'arroyo-dulce'),
(95, 6, 'Arroyo Venado', 'arroyo-venado'),
(96, 6, 'Arturo Seguí', 'arturo-segui'),
(97, 6, 'Ascensión', 'ascension'),
(98, 6, 'Atalaya', 'atalaya'),
(99, 6, 'Atlántida', 'atlantida'),
(100, 6, 'Avellaneda', 'avellaneda'),
(101, 6, 'Ayacucho', 'ayacucho'),
(102, 6, 'Azcuénaga', 'azcuenaga'),
(103, 6, 'Azopardo', 'azopardo'),
(104, 6, 'Azul', 'azul'),
(105, 6, 'Bahía Blanca', 'bahia-blanca'),
(106, 6, 'Bahía San Blas', 'bahia-san-blas'),
(107, 6, 'Baigorrita', 'baigorrita'),
(108, 6, 'Bajo Hondo', 'bajo-hondo'),
(109, 6, 'Balcarce', 'balcarce'),
(110, 6, 'Balneario Orense', 'balneario-orense'),
(111, 6, 'Balneario Pehuen Co', 'balneario-pehuen-co'),
(112, 6, 'Balneario San Cayetano', 'balneario-san-cayetano'),
(113, 6, 'Balneario Sauce Grande', 'balneario-sauce-grande'),
(114, 6, 'Banderaló', 'banderalo'),
(115, 6, 'Banfield', 'banfield'),
(116, 6, 'Baradero', 'baradero'),
(117, 6, 'Barrio América Unida', 'barrio-america-unida'),
(118, 6, 'Barrio Banco Provincia', 'barrio-banco-provincia'),
(119, 6, 'Barrio Belgrano', 'barrio-belgrano'),
(120, 6, 'Barrio Colinas Verdes', 'barrio-colinas-verdes'),
(121, 6, 'Barrio El Boquerón', 'barrio-el-boqueron'),
(122, 6, 'Barrio El Carmen Este', 'barrio-el-carmen-este'),
(123, 6, 'Barrio El Carmen Oeste', 'barrio-el-carmen-oeste'),
(124, 6, 'Barrio El Casal', 'barrio-el-casal'),
(125, 6, 'Barrio El Coyunco', 'barrio-el-coyunco'),
(126, 6, 'Barrio El Mirador', 'barrio-el-mirador'),
(127, 6, 'Barrio El Taladro', 'barrio-el-taladro'),
(128, 6, 'Barrio Gambier', 'barrio-gambier'),
(129, 6, 'Barrio Kennedy', 'barrio-kennedy'),
(130, 6, 'Barrio La Gloria', 'barrio-la-gloria'),
(131, 6, 'Barrio Las Casuarinas', 'barrio-las-casuarinas'),
(132, 6, 'Barrio Las Golondrinas', 'barrio-las-golondrinas'),
(133, 6, 'Barrio Las Malvinas', 'barrio-las-malvinas'),
(134, 6, 'Barrio Las Margaritas', 'barrio-las-margaritas'),
(135, 6, 'Barrio Las Quintas', 'barrio-las-quintas'),
(136, 6, 'Barrio Lisandro de la Torre y Santa Marta', 'barrio-lisandro-de-la-torre-y-santa-marta'),
(137, 6, 'Barrio Lomas Altas', 'barrio-lomas-altas'),
(138, 6, 'Barrio Los Bosquecitos', 'barrio-los-bosquecitos'),
(139, 6, 'Barrio Los Pioneros (Barrio Tavella)', 'barrio-los-pioneros-barrio-tavella'),
(140, 6, 'Barrio Morabo', 'barrio-morabo'),
(141, 6, 'Barrio Parque General San Martín', 'barrio-parque-general-san-martin'),
(142, 6, 'Barrio Parque Las Acacias', 'barrio-parque-las-acacias'),
(143, 6, 'Barrio Ruta 24 Kilómetro 10', 'barrio-ruta-24-kilometro-10'),
(144, 6, 'Barrio Saavedra', 'barrio-saavedra'),
(145, 6, 'Barrio San Cayetano', 'barrio-san-cayetano'),
(146, 6, 'Barrio Santa Paula', 'barrio-santa-paula'),
(147, 6, 'Barrio Santa Rosa', 'barrio-santa-rosa'),
(148, 6, 'Barrio Universitario', 'barrio-universitario'),
(149, 6, 'Bayauca', 'bayauca'),
(150, 6, 'Béccar', 'beccar'),
(151, 6, 'Belén de Escobar', 'belen-de-escobar'),
(152, 6, 'Bella Vista', 'bella-vista'),
(153, 6, 'Bellocq', 'bellocq'),
(154, 6, 'Benavídez', 'benavidez'),
(155, 6, 'Benitez', 'benitez'),
(156, 6, 'Benito Juárez', 'benito-juarez'),
(157, 6, 'Berazategui', 'berazategui'),
(158, 6, 'Berazategui Oeste', 'berazategui-oeste'),
(159, 6, 'Berdier', 'berdier'),
(160, 6, 'Berisso', 'berisso'),
(161, 6, 'Bermúdez', 'bermudez'),
(162, 6, 'Bernal', 'bernal'),
(163, 6, 'Bernal Oeste', 'bernal-oeste'),
(164, 6, 'Berutti', 'berutti'),
(165, 6, 'Billinghurst', 'billinghurst'),
(166, 6, 'Blancagrande', 'blancagrande'),
(167, 6, 'Blaquier', 'blaquier'),
(168, 6, 'Bocayuva', 'bocayuva'),
(169, 6, 'Bordenave', 'bordenave'),
(170, 6, 'Bosques', 'bosques'),
(171, 6, 'Boulogne Sur Mer', 'boulogne-sur-mer'),
(172, 6, 'Bragado', 'bragado'),
(173, 6, 'Burzaco', 'burzaco'),
(174, 6, 'Cabildo', 'cabildo'),
(175, 6, 'Cacharí', 'cachari'),
(176, 6, 'Cadret', 'cadret'),
(177, 6, 'Camet', 'camet'),
(178, 6, 'Camet Norte', 'camet-norte'),
(179, 6, 'Campana', 'campana'),
(180, 6, 'Campo de Mayo', 'campo-de-mayo'),
(181, 6, 'Campos de Roca', 'campos-de-roca'),
(182, 6, 'Campos Salles', 'campos-salles'),
(183, 6, 'Cañada Seca', 'canada-seca'),
(184, 6, 'Canning (Esteban Echeverría)', 'canning-esteban-echeverria'),
(185, 6, 'Canning (Ezeiza)', 'canning-ezeiza'),
(186, 6, 'Cañuelas', 'canuelas'),
(187, 6, 'Capilla del Señor', 'capilla-del-senor'),
(188, 6, 'Capitán Castro', 'capitan-castro'),
(189, 6, 'Capitán Sarmiento', 'capitan-sarmiento'),
(190, 6, 'Carapachay', 'carapachay'),
(191, 6, 'Cardenal Cagliero', 'cardenal-cagliero'),
(192, 6, 'Carhué', 'carhue'),
(193, 6, 'Cariló', 'carilo'),
(194, 6, 'Carlos Beguerie', 'carlos-beguerie'),
(195, 6, 'Carlos Casares', 'carlos-casares'),
(196, 6, 'Carlos Keen', 'carlos-keen'),
(197, 6, 'Carlos María Naón', 'carlos-maria-naon'),
(198, 6, 'Carlos Salas', 'carlos-salas'),
(199, 6, 'Carlos Spegazzini', 'carlos-spegazzini'),
(200, 6, 'Carlos Tejedor', 'carlos-tejedor');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(201, 6, 'Carlos Tomás Sourigues', 'carlos-tomas-sourigues'),
(202, 6, 'Carmen de Areco', 'carmen-de-areco'),
(203, 6, 'Carmen de Patagones', 'carmen-de-patagones'),
(204, 6, 'Casalins', 'casalins'),
(205, 6, 'Casbas', 'casbas'),
(206, 6, 'Cascada', 'cascada'),
(207, 6, 'Caseros', 'caseros'),
(208, 6, 'Castelar', 'castelar'),
(209, 6, 'Castelli', 'castelli'),
(210, 6, 'Castilla', 'castilla'),
(211, 6, 'Cazón', 'cazon'),
(212, 6, 'Centinela del Mar', 'centinela-del-mar'),
(213, 6, 'Centro Guerrero', 'centro-guerrero'),
(214, 6, 'Cerro de la Gloria', 'cerro-de-la-gloria'),
(215, 6, 'Chacabuco', 'chacabuco'),
(216, 6, 'Chapadmalal', 'chapadmalal'),
(217, 6, 'Chascomús', 'chascomus'),
(218, 6, 'Chasicó', 'chasico'),
(219, 6, 'Chillar', 'chillar'),
(220, 6, 'Chivilcoy', 'chivilcoy'),
(221, 6, 'Churruca', 'churruca'),
(222, 6, 'City Bell', 'city-bell'),
(223, 6, 'Ciudad del Libertador General San Martín', 'ciudad-del-libertador-general-san-martin'),
(224, 6, 'Ciudad Evita', 'ciudad-evita'),
(225, 6, 'Ciudad Jardín El Libertador', 'ciudad-jardin-el-libertador'),
(226, 6, 'Ciudad Jardín Lomas del Palomar', 'ciudad-jardin-lomas-del-palomar'),
(227, 6, 'Ciudadela', 'ciudadela'),
(228, 6, 'Claraz', 'claraz'),
(229, 6, 'Claromecó', 'claromeco'),
(230, 6, 'Claypole', 'claypole'),
(231, 6, 'Club de Campo Los Puentes', 'club-de-campo-los-puentes'),
(232, 6, 'Colón', 'colon'),
(233, 6, 'Colonia Hinojo', 'colonia-hinojo'),
(234, 6, 'Colonia Mauricio', 'colonia-mauricio'),
(235, 6, 'Colonia Nievas', 'colonia-nievas'),
(236, 6, 'Colonia San Martín', 'colonia-san-martin'),
(237, 6, 'Colonia San Miguel', 'colonia-san-miguel'),
(238, 6, 'Colonia San Miguel Arcángel', 'colonia-san-miguel-arcangel'),
(239, 6, 'Colonia San Ricardo', 'colonia-san-ricardo'),
(240, 6, 'Colonia Seré', 'colonia-sere'),
(241, 6, 'Comandante Nicanor Otamendi', 'comandante-nicanor-otamendi'),
(242, 6, 'Comodoro Py', 'comodoro-py'),
(243, 6, 'Conesa', 'conesa'),
(244, 6, 'Copetonas', 'copetonas'),
(245, 6, 'Coronel Boerr', 'coronel-boerr'),
(246, 6, 'Coronel Brandsen', 'coronel-brandsen'),
(247, 6, 'Coronel Charlone', 'coronel-charlone'),
(248, 6, 'Coronel Dorrego', 'coronel-dorrego'),
(249, 6, 'Coronel Martínez de Hoz', 'coronel-martinez-de-hoz'),
(250, 6, 'Coronel Pringles', 'coronel-pringles'),
(251, 6, 'Coronel Seguí', 'coronel-segui'),
(252, 6, 'Coronel Suárez', 'coronel-suarez'),
(253, 6, 'Coronel Vidal', 'coronel-vidal'),
(254, 6, 'Cortines', 'cortines'),
(255, 6, 'Costa Bonita', 'costa-bonita'),
(256, 6, 'Country Club Bosque Real', 'country-club-bosque-real'),
(257, 6, 'Country Club Las Praderas', 'country-club-las-praderas'),
(258, 6, 'Country Los Medanos', 'country-los-medanos'),
(259, 6, 'Crotto', 'crotto'),
(260, 6, 'Crucesita', 'crucesita'),
(261, 6, 'Cuartel V', 'cuartel-v'),
(262, 6, 'Cucullú', 'cucullu'),
(263, 6, 'Curamalal', 'curamalal'),
(264, 6, 'Curarú', 'curaru'),
(265, 6, 'D''Orbigny', 'd-orbigny'),
(266, 6, 'Daireaux', 'daireaux'),
(267, 6, 'Darregueira', 'darregueira'),
(268, 6, 'De Bary', 'de-bary'),
(269, 6, 'De la Canal', 'de-la-canal'),
(270, 6, 'De La Garma', 'de-la-garma'),
(271, 6, 'Del Carril', 'del-carril'),
(272, 6, 'Del Valle', 'del-valle'),
(273, 6, 'Del Viso (José C. Paz)', 'del-viso-jose-c-paz'),
(274, 6, 'Del Viso (Pilar)', 'del-viso-pilar'),
(275, 6, 'Delfín Huergo', 'delfin-huergo'),
(276, 6, 'Diego Gaynor', 'diego-gaynor'),
(277, 6, 'Dique Luján', 'dique-lujan'),
(278, 6, 'Dique N° 1', 'dique-n-1'),
(279, 6, 'Dock Sud', 'dock-sud'),
(280, 6, 'Dolores', 'dolores'),
(281, 6, 'Domselaar', 'domselaar'),
(282, 6, 'Don Bosco', 'don-bosco'),
(283, 6, 'Don Orione', 'don-orione'),
(284, 6, 'Don Torcuato Este', 'don-torcuato-este'),
(285, 6, 'Don Torcuato Oeste', 'don-torcuato-oeste'),
(286, 6, 'Dudignac', 'dudignac'),
(287, 6, 'Dufaur', 'dufaur'),
(288, 6, 'Duggan', 'duggan'),
(289, 6, 'Dunamar', 'dunamar'),
(290, 6, 'El Cazador', 'el-cazador'),
(291, 6, 'El Divisorio', 'el-divisorio'),
(292, 6, 'El Dorado', 'el-dorado'),
(293, 6, 'El Jagüel', 'el-jaguel'),
(294, 6, 'El Libertador', 'el-libertador'),
(295, 6, 'El Marquesado', 'el-marquesado'),
(296, 6, 'El Palomar', 'el-palomar'),
(297, 6, 'El Paraíso', 'el-paraiso'),
(298, 6, 'El Pato', 'el-pato'),
(299, 6, 'El Pensamiento', 'el-pensamiento'),
(300, 6, 'El Perdido', 'el-perdido'),
(301, 6, 'El Remanso', 'el-remanso'),
(302, 6, 'El Retiro', 'el-retiro'),
(303, 6, 'El Talar', 'el-talar'),
(304, 6, 'El Trigo', 'el-trigo'),
(305, 6, 'El Triunfo', 'el-triunfo'),
(306, 6, 'El Tropezón', 'el-tropezon'),
(307, 6, 'Elvira', 'elvira'),
(308, 6, 'Emilio Ayarza', 'emilio-ayarza'),
(309, 6, 'Emilio V. Bunge', 'emilio-v-bunge'),
(310, 6, 'Energía', 'energia'),
(311, 6, 'Ensenada', 'ensenada'),
(312, 6, 'Erezcano', 'erezcano'),
(313, 6, 'Ernestina', 'ernestina'),
(314, 6, 'Escobar', 'escobar'),
(315, 6, 'Espartillar (Adolfo Alsina)', 'espartillar-adolfo-alsina'),
(316, 6, 'Espartillar (Saavedra)', 'espartillar-saavedra'),
(317, 6, 'Espigas', 'espigas'),
(318, 6, 'Estación Arenales', 'estacion-arenales'),
(319, 6, 'Estación Camet', 'estacion-camet'),
(320, 6, 'Estanislao Severo Zeballos', 'estanislao-severo-zeballos'),
(321, 6, 'Esteban Agustín Gascón', 'esteban-agustin-gascon'),
(322, 6, 'Esteban Echeverría', 'esteban-echeverria'),
(323, 6, 'Estela', 'estela'),
(324, 6, 'Ezeiza', 'ezeiza'),
(325, 6, 'Ezpeleta', 'ezpeleta'),
(326, 6, 'Ezpeleta Oeste', 'ezpeleta-oeste'),
(327, 6, 'Faro', 'faro'),
(328, 6, 'Fátima', 'fatima'),
(329, 6, 'Felipe Solá', 'felipe-sola'),
(330, 6, 'Ferré', 'ferre'),
(331, 6, 'Florencio Varela', 'florencio-varela'),
(332, 6, 'Florentino Ameghino', 'florentino-ameghino'),
(333, 6, 'Florida', 'florida'),
(334, 6, 'Florida Oeste', 'florida-oeste'),
(335, 6, 'Fontezuela', 'fontezuela'),
(336, 6, 'Fortín Acha', 'fortin-acha'),
(337, 6, 'Fortín Olavarría', 'fortin-olavarria'),
(338, 6, 'Fortín Tiburcio', 'fortin-tiburcio'),
(339, 6, 'Francisco Álvarez', 'francisco-alvarez'),
(340, 6, 'Francisco Madero', 'francisco-madero'),
(341, 6, 'Franklin', 'franklin'),
(342, 6, 'Frente Mar', 'frente-mar'),
(343, 6, 'Gahan', 'gahan'),
(344, 6, 'Gardey', 'gardey'),
(345, 6, 'Garín', 'garin'),
(346, 6, 'Garré', 'garre'),
(347, 6, 'General Alvear', 'general-alvear'),
(348, 6, 'General Arenales', 'general-arenales'),
(349, 6, 'General Belgrano', 'general-belgrano'),
(350, 6, 'General Conesa', 'general-conesa'),
(351, 6, 'General Daniel Cerri', 'general-daniel-cerri'),
(352, 6, 'General Guido', 'general-guido'),
(353, 6, 'General Juan Madariaga', 'general-juan-madariaga'),
(354, 6, 'General La Madrid', 'general-la-madrid'),
(355, 6, 'General Las Heras', 'general-las-heras'),
(356, 6, 'General Lavalle', 'general-lavalle'),
(357, 6, 'General Mansilla', 'general-mansilla'),
(358, 6, 'General O''Brien', 'general-o-brien'),
(359, 6, 'General Pacheco', 'general-pacheco'),
(360, 6, 'General Pinto', 'general-pinto'),
(361, 6, 'General Pirán', 'general-piran'),
(362, 6, 'General Rivas', 'general-rivas'),
(363, 6, 'General Rodríguez', 'general-rodriguez'),
(364, 6, 'General Rojo', 'general-rojo'),
(365, 6, 'General San Martín', 'general-san-martin'),
(366, 6, 'General Villegas', 'general-villegas'),
(367, 6, 'Gerli (Avellaneda)', 'gerli-avellaneda'),
(368, 6, 'Gerli (Lanús)', 'gerli-lanus'),
(369, 6, 'Germania', 'germania'),
(370, 6, 'Girodias', 'girodias'),
(371, 6, 'Glew', 'glew'),
(372, 6, 'Gobernador Castro', 'gobernador-castro'),
(373, 6, 'Gobernador Julio A. Costa', 'gobernador-julio-a-costa'),
(374, 6, 'Gobernador Udaondo', 'gobernador-udaondo'),
(375, 6, 'Gobernador Ugarte', 'gobernador-ugarte'),
(376, 6, 'Goldney', 'goldney'),
(377, 6, 'Gómez', 'gomez'),
(378, 6, 'González Catán', 'gonzalez-catan'),
(379, 6, 'González Moreno', 'gonzalez-moreno'),
(380, 6, 'Gorchs', 'gorchs'),
(381, 6, 'Gorostiaga', 'gorostiaga'),
(382, 6, 'Goyena', 'goyena'),
(383, 6, 'Grand Bourg', 'grand-bourg'),
(384, 6, 'Gregorio de Laferrere', 'gregorio-de-laferrere'),
(385, 6, 'Grünbein', 'grunbein'),
(386, 6, 'Guaminí', 'guamini'),
(387, 6, 'Guernica', 'guernica'),
(388, 6, 'Guerrico', 'guerrico'),
(389, 6, 'Guillermo Enrique Hudson', 'guillermo-enrique-hudson'),
(390, 6, 'Gunther', 'gunther'),
(391, 6, 'Haedo', 'haedo'),
(392, 6, 'Hale', 'hale'),
(393, 6, 'Henderson', 'henderson'),
(394, 6, 'Herrera Vegas', 'herrera-vegas'),
(395, 6, 'Hilario Ascasubi', 'hilario-ascasubi'),
(396, 6, 'Hinojo', 'hinojo'),
(397, 6, 'Hortensia', 'hortensia'),
(398, 6, 'Huanguelén', 'huanguelen'),
(399, 6, 'Hurlingham', 'hurlingham'),
(400, 6, 'Indio Rico', 'indio-rico');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(401, 6, 'Inés Indart', 'ines-indart'),
(402, 6, 'Ingeniero Adolfo Sourdeaux', 'ingeniero-adolfo-sourdeaux'),
(403, 6, 'Ingeniero Juan Allan', 'ingeniero-juan-allan'),
(404, 6, 'Ingeniero Maschwitz', 'ingeniero-maschwitz'),
(405, 6, 'Ingeniero Moneta', 'ingeniero-moneta'),
(406, 6, 'Ingeniero Pablo Nogués', 'ingeniero-pablo-nogues'),
(407, 6, 'Ingeniero Thompson', 'ingeniero-thompson'),
(408, 6, 'Ingeniero White', 'ingeniero-white'),
(409, 6, 'Inocencio Sosa', 'inocencio-sosa'),
(410, 6, 'Irala', 'irala'),
(411, 6, 'Irene', 'irene'),
(412, 6, 'Irineo Portela', 'irineo-portela'),
(413, 6, 'Isidro Casanova', 'isidro-casanova'),
(414, 6, 'Isla Santiago', 'isla-santiago'),
(415, 6, 'Ituzaingó', 'ituzaingo'),
(416, 6, 'Ituzaingó Centro', 'ituzaingo-centro'),
(417, 6, 'Ituzaingó Sur', 'ituzaingo-sur'),
(418, 6, 'Jeppener', 'jeppener'),
(419, 6, 'Joaquín Gorina', 'joaquin-gorina'),
(420, 6, 'Jorge Born', 'jorge-born'),
(421, 6, 'José B. Casas', 'jose-b-casas'),
(422, 6, 'José C. Paz', 'jose-c-paz'),
(423, 6, 'José Hernández', 'jose-hernandez'),
(424, 6, 'José Ingenieros', 'jose-ingenieros'),
(425, 6, 'José Juan Almeyra', 'jose-juan-almeyra'),
(426, 6, 'José María Ezeiza', 'jose-maria-ezeiza'),
(427, 6, 'José Mármol', 'jose-marmol'),
(428, 6, 'José Melchor Romero', 'jose-melchor-romero'),
(429, 6, 'Juan A. de la Peña', 'juan-a-de-la-pena'),
(430, 6, 'Juan A. Pradere', 'juan-a-pradere'),
(431, 6, 'Juan Anchorena', 'juan-anchorena'),
(432, 6, 'Juan Bautista Alberdi', 'juan-bautista-alberdi'),
(433, 6, 'Juan Cousté', 'juan-couste'),
(434, 6, 'Juan E. Barra', 'juan-e-barra'),
(435, 6, 'Juan F. Ibarra', 'juan-f-ibarra'),
(436, 6, 'Juan F. Salaberry', 'juan-f-salaberry'),
(437, 6, 'Juan José Paso', 'juan-jose-paso'),
(438, 6, 'Juan María Gutiérrez', 'juan-maria-gutierrez'),
(439, 6, 'Juan N. Fernández', 'juan-n-fernandez'),
(440, 6, 'Junín', 'junin'),
(441, 6, 'La Angelita', 'la-angelita'),
(442, 6, 'La Armonía', 'la-armonia'),
(443, 6, 'La Aurora', 'la-aurora'),
(444, 6, 'La Baliza', 'la-baliza'),
(445, 6, 'La Beba', 'la-beba'),
(446, 6, 'La Caleta', 'la-caleta'),
(447, 6, 'La Capilla', 'la-capilla'),
(448, 6, 'La Carreta', 'la-carreta'),
(449, 6, 'La Choza', 'la-choza'),
(450, 6, 'La Colina', 'la-colina'),
(451, 6, 'La Constancia', 'la-constancia'),
(452, 6, 'La Cumbre', 'la-cumbre'),
(453, 6, 'La Delfina', 'la-delfina'),
(454, 6, 'La Gruta', 'la-gruta'),
(455, 6, 'La Invencible', 'la-invencible'),
(456, 6, 'La Larga', 'la-larga'),
(457, 6, 'La Limpia', 'la-limpia'),
(458, 6, 'La Lonja', 'la-lonja'),
(459, 6, 'La Lucila', 'la-lucila'),
(460, 6, 'La Luisa', 'la-luisa'),
(461, 6, 'La Matanza', 'la-matanza'),
(462, 6, 'La Pala', 'la-pala'),
(463, 6, 'La Plata', 'la-plata'),
(464, 6, 'La Reja', 'la-reja'),
(465, 6, 'La Rica', 'la-rica'),
(466, 6, 'La Sofía', 'la-sofia'),
(467, 6, 'La Tablada', 'la-tablada'),
(468, 6, 'La Unión', 'la-union'),
(469, 6, 'La Violeta', 'la-violeta'),
(470, 6, 'Labardén', 'labarden'),
(471, 6, 'Laguna Alsina', 'laguna-alsina'),
(472, 6, 'Laguna Vitel', 'laguna-vitel'),
(473, 6, 'Lanús', 'lanus'),
(474, 6, 'Lanús Este', 'lanus-este'),
(475, 6, 'Lanús Oeste', 'lanus-oeste'),
(476, 6, 'Laplacette', 'laplacette'),
(477, 6, 'Laprida', 'laprida'),
(478, 6, 'Lartigau', 'lartigau'),
(479, 6, 'Las Armas', 'las-armas'),
(480, 6, 'Las Bahamas', 'las-bahamas'),
(481, 6, 'Las Carabelas', 'las-carabelas'),
(482, 6, 'Las Encadenadas', 'las-encadenadas'),
(483, 6, 'Las Flores', 'las-flores'),
(484, 6, 'Las Marianas', 'las-marianas'),
(485, 6, 'Las Martinetas', 'las-martinetas'),
(486, 6, 'Las Toscas', 'las-toscas'),
(487, 6, 'Leandro N. Alem', 'leandro-n-alem'),
(488, 6, 'Lezica y Torrezuri', 'lezica-y-torrezuri'),
(489, 6, 'Líbano', 'libano'),
(490, 6, 'Libertad', 'libertad'),
(491, 6, 'Licenciado Matienzo', 'licenciado-matienzo'),
(492, 6, 'Lima', 'lima'),
(493, 6, 'Lincoln', 'lincoln'),
(494, 6, 'Lisandro Olmos', 'lisandro-olmos'),
(495, 6, 'Llavallol', 'llavallol'),
(496, 6, 'Lobería', 'loberia'),
(497, 6, 'Lobos', 'lobos'),
(498, 6, 'Loma Hermosa', 'loma-hermosa'),
(499, 6, 'Loma Verde (Escobar)', 'loma-verde-escobar'),
(500, 6, 'Loma Verde (General Paz)', 'loma-verde-general-paz'),
(501, 6, 'Lomas de Zamora', 'lomas-de-zamora'),
(502, 6, 'Lomas del Mirador', 'lomas-del-mirador'),
(503, 6, 'Longchamps', 'longchamps'),
(504, 6, 'López', 'lopez'),
(505, 6, 'López Lecube', 'lopez-lecube'),
(506, 6, 'Los Angeles', 'los-angeles'),
(507, 6, 'Los Cachorros', 'los-cachorros'),
(508, 6, 'Los Cardales', 'los-cardales'),
(509, 6, 'Los Hornos', 'los-hornos'),
(510, 6, 'Los Indios', 'los-indios'),
(511, 6, 'Los Naranjos', 'los-naranjos'),
(512, 6, 'Los Pinos', 'los-pinos'),
(513, 6, 'Los Polvorines', 'los-polvorines'),
(514, 6, 'Los Talas', 'los-talas'),
(515, 6, 'Los Toldos', 'los-toldos'),
(516, 6, 'Lucas Monteverde', 'lucas-monteverde'),
(517, 6, 'Luis Guillón', 'luis-guillon'),
(518, 6, 'Luján', 'lujan'),
(519, 6, 'Magdala', 'magdala'),
(520, 6, 'Magdalena', 'magdalena'),
(521, 6, 'Maipú', 'maipu'),
(522, 6, 'Malvinas Argentinas (Almirante Brown)', 'malvinas-argentinas-almirante-brown'),
(523, 6, 'Malvinas Argentinas (Malvinas Argentinas)', 'malvinas-argentinas-malvinas-argentinas'),
(524, 6, 'Manuel B. Gonnet (La Plata)', 'manuel-b-gonnet-la-plata'),
(525, 6, 'Manuel B. Gonnet (9 de Julio)', 'manuel-b-gonnet-9-de-julio'),
(526, 6, 'Manuel J. Cobo', 'manuel-j-cobo'),
(527, 6, 'Manuel Ocampo', 'manuel-ocampo'),
(528, 6, 'Manzanares', 'manzanares'),
(529, 6, 'Manzone', 'manzone'),
(530, 6, 'Maquinista F. Savio Este', 'maquinista-f-savio-este'),
(531, 6, 'Maquinista F. Savio Oeste', 'maquinista-f-savio-oeste'),
(532, 6, 'Mar Chiquita', 'mar-chiquita'),
(533, 6, 'Mar de Cobo', 'mar-de-cobo'),
(534, 6, 'Mar del Plata', 'mar-del-plata'),
(535, 6, 'Mar del Sur', 'mar-del-sur'),
(536, 6, 'Mar del Tuyú', 'mar-del-tuyu'),
(537, 6, 'Marcelino Ugarte', 'marcelino-ugarte'),
(538, 6, 'Marcos Paz', 'marcos-paz'),
(539, 6, 'María Ignacia', 'maria-ignacia'),
(540, 6, 'Mariano Acosta', 'mariano-acosta'),
(541, 6, 'Mariano Benítez', 'mariano-benitez'),
(542, 6, 'Mariano H. Alfonzo', 'mariano-h-alfonzo'),
(543, 6, 'Marisol', 'marisol'),
(544, 6, 'Martín Coronado', 'martin-coronado'),
(545, 6, 'Martínez', 'martinez'),
(546, 6, 'Massey', 'massey'),
(547, 6, 'Matheu', 'matheu'),
(548, 6, 'Mauricio Hirsch', 'mauricio-hirsch'),
(549, 6, 'Máximo Paz', 'maximo-paz'),
(550, 6, 'Mayor Buratovich', 'mayor-buratovich'),
(551, 6, 'Maza', 'maza'),
(552, 6, 'Mechita (Alberti)', 'mechita-alberti'),
(553, 6, 'Mechita (Bragado)', 'mechita-bragado'),
(554, 6, 'Mechongué', 'mechongue'),
(555, 6, 'Médanos', 'medanos'),
(556, 6, 'Mercedes', 'mercedes'),
(557, 6, 'Merlo', 'merlo'),
(558, 6, 'Micaela Cascallares', 'micaela-cascallares'),
(559, 6, 'Ministro Rivadavia', 'ministro-rivadavia'),
(560, 6, 'Mira Pampa', 'mira-pampa'),
(561, 6, 'Miramar', 'miramar'),
(562, 6, 'Moctezuma', 'moctezuma'),
(563, 6, 'Mones Cazón', 'mones-cazon'),
(564, 6, 'Monte Chingolo', 'monte-chingolo'),
(565, 6, 'Monte Grande', 'monte-grande'),
(566, 6, 'Monte Hermoso', 'monte-hermoso'),
(567, 6, 'Moquehuá', 'moquehua'),
(568, 6, 'Morea', 'morea'),
(569, 6, 'Moreno', 'moreno'),
(570, 6, 'Morón', 'moron'),
(571, 6, 'Morse', 'morse'),
(572, 6, 'Muñiz', 'muniz'),
(573, 6, 'Munro', 'munro'),
(574, 6, 'Napaleofú', 'napaleofu'),
(575, 6, 'Navarro', 'navarro'),
(576, 6, 'Necochea', 'necochea'),
(577, 6, 'Necochea - Quequén', 'necochea-quequen'),
(578, 6, 'Nicanor Olivera', 'nicanor-olivera'),
(579, 6, 'Norberto de la Riestra', 'norberto-de-la-riestra'),
(580, 6, 'Norumbega', 'norumbega'),
(581, 6, 'Nueva Plata', 'nueva-plata'),
(582, 6, 'O''Higgins', 'o-higgins'),
(583, 6, 'Obligado', 'obligado'),
(584, 6, 'Ochandío', 'ochandio'),
(585, 6, 'Olascoaga', 'olascoaga'),
(586, 6, 'Olavarría', 'olavarria'),
(587, 6, 'Oliden', 'oliden'),
(588, 6, 'Olivera', 'olivera'),
(589, 6, 'Olivos', 'olivos'),
(590, 6, 'Open Door', 'open-door'),
(591, 6, 'Ordoqui', 'ordoqui'),
(592, 6, 'Orense', 'orense'),
(593, 6, 'Oriente', 'oriente'),
(594, 6, 'Ostende', 'ostende'),
(595, 6, 'Pablo Podestá', 'pablo-podesta'),
(596, 6, 'Parada Robles', 'parada-robles'),
(597, 6, 'Parada Robles - Pavón', 'parada-robles-pavon'),
(598, 6, 'Paraje La Agraria', 'paraje-la-agraria'),
(599, 6, 'Paraje La Ruta', 'paraje-la-ruta'),
(600, 6, 'Pardo', 'pardo');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(601, 6, 'Pasman', 'pasman'),
(602, 6, 'Paso del Rey', 'paso-del-rey'),
(603, 6, 'Pasteur', 'pasteur'),
(604, 6, 'Patricios', 'patricios'),
(605, 6, 'Paula', 'paula'),
(606, 6, 'Pavón (Exaltación de la Cruz)', 'pavon-exaltacion-de-la-cruz'),
(607, 6, 'Pavón (General Lavalle)', 'pavon-general-lavalle'),
(608, 6, 'Pearson', 'pearson'),
(609, 6, 'Pedernales', 'pedernales'),
(610, 6, 'Pedro Luro', 'pedro-luro'),
(611, 6, 'Pehuajó', 'pehuajo'),
(612, 6, 'Pellegrini', 'pellegrini'),
(613, 6, 'Pereyra', 'pereyra'),
(614, 6, 'Pérez Millán', 'perez-millan'),
(615, 6, 'Pergamino', 'pergamino'),
(616, 6, 'Pichincha', 'pichincha'),
(617, 6, 'Piedritas', 'piedritas'),
(618, 6, 'Pieres', 'pieres'),
(619, 6, 'Pigüé', 'pigue'),
(620, 6, 'Pila', 'pila'),
(621, 6, 'Pilar', 'pilar'),
(622, 6, 'Pinamar', 'pinamar'),
(623, 6, 'Piñeyro', 'pineyro'),
(624, 6, 'Pinzón', 'pinzon'),
(625, 6, 'Pipinas', 'pipinas'),
(626, 6, 'Pirovano', 'pirovano'),
(627, 6, 'Pla', 'pla'),
(628, 6, 'Plátanos', 'platanos'),
(629, 6, 'Playa Dorada', 'playa-dorada'),
(630, 6, 'Plomer', 'plomer'),
(631, 6, 'Polvaredas', 'polvaredas'),
(632, 6, 'Pontaut', 'pontaut'),
(633, 6, 'Pontevedra', 'pontevedra'),
(634, 6, 'Porvenir', 'porvenir'),
(635, 6, 'Presidente Derqui', 'presidente-derqui'),
(636, 6, 'Presidente Perón', 'presidente-peron'),
(637, 6, 'Puán', 'puan'),
(638, 6, 'Pueblo Doyle', 'pueblo-doyle'),
(639, 6, 'Pueblo Gouin', 'pueblo-gouin'),
(640, 6, 'Pueblo Nuevo', 'pueblo-nuevo'),
(641, 6, 'Pueblo San Jorge', 'pueblo-san-jorge'),
(642, 6, 'Punta Alta', 'punta-alta'),
(643, 6, 'Punta Indio', 'punta-indio'),
(644, 6, 'Punta Lara', 'punta-lara'),
(645, 6, 'Punta Mogotes', 'punta-mogotes'),
(646, 6, 'Quenumá', 'quenuma'),
(647, 6, 'Quequén', 'quequen'),
(648, 6, 'Quilmes', 'quilmes'),
(649, 6, 'Quilmes Oeste', 'quilmes-oeste'),
(650, 6, 'Rafael Calzada', 'rafael-calzada'),
(651, 6, 'Rafael Castillo', 'rafael-castillo'),
(652, 6, 'Rafael Obligado', 'rafael-obligado'),
(653, 6, 'Ramallo', 'ramallo'),
(654, 6, 'Ramón Biaus', 'ramon-biaus'),
(655, 6, 'Ramón Santamarina', 'ramon-santamarina'),
(656, 6, 'Ramos Mejía', 'ramos-mejia'),
(657, 6, 'Ramos Otero', 'ramos-otero'),
(658, 6, 'Rancagua', 'rancagua'),
(659, 6, 'Ranchos', 'ranchos'),
(660, 6, 'Ranelagh', 'ranelagh'),
(661, 6, 'Rauch', 'rauch'),
(662, 6, 'Rawson', 'rawson'),
(663, 6, 'Recalde', 'recalde'),
(664, 6, 'Remedios de Escalada', 'remedios-de-escalada'),
(665, 6, 'Remedios de Escalada de San Martín', 'remedios-de-escalada-de-san-martin'),
(666, 6, 'Reta', 'reta'),
(667, 6, 'Ricardo Rojas', 'ricardo-rojas'),
(668, 6, 'Rincón de Milberg', 'rincon-de-milberg'),
(669, 6, 'Ringuelet', 'ringuelet'),
(670, 6, 'Río Tala', 'rio-tala'),
(671, 6, 'Rivera', 'rivera'),
(672, 6, 'Roberto Cano', 'roberto-cano'),
(673, 6, 'Roberto de Vicenzo', 'roberto-de-vicenzo'),
(674, 6, 'Roberts', 'roberts'),
(675, 6, 'Rojas', 'rojas'),
(676, 6, 'Roosevelt', 'roosevelt'),
(677, 6, 'Roque Pérez', 'roque-perez'),
(678, 6, 'Rufino de Elizalde', 'rufino-de-elizalde'),
(679, 6, 'Saavedra', 'saavedra'),
(680, 6, 'Sáenz Peña', 'saenz-pena'),
(681, 6, 'Saforcada', 'saforcada'),
(682, 6, 'Saladillo', 'saladillo'),
(683, 6, 'Salazar', 'salazar'),
(684, 6, 'Saldungaray', 'saldungaray'),
(685, 6, 'Salliqueló', 'salliquelo'),
(686, 6, 'Salto', 'salto'),
(687, 6, 'Salvador María', 'salvador-maria'),
(688, 6, 'San Agustín', 'san-agustin'),
(689, 6, 'San Andrés de Giles', 'san-andres-de-giles'),
(690, 6, 'San Antonio de Areco', 'san-antonio-de-areco'),
(691, 6, 'San Antonio de Padua', 'san-antonio-de-padua'),
(692, 6, 'San Bernardo', 'san-bernardo'),
(693, 6, 'San Carlos de Bolívar', 'san-carlos-de-bolivar'),
(694, 6, 'San Cayetano', 'san-cayetano'),
(695, 6, 'San Clemente del Tuyú', 'san-clemente-del-tuyu'),
(696, 6, 'San Emilio', 'san-emilio'),
(697, 6, 'San Enrique', 'san-enrique'),
(698, 6, 'San Esteban', 'san-esteban'),
(699, 6, 'San Fernando', 'san-fernando'),
(700, 6, 'San Francisco de Bellocq', 'san-francisco-de-bellocq'),
(701, 6, 'San Francisco Solano (Almirante Brown)', 'san-francisco-solano-almirante-brown'),
(702, 6, 'San Francisco Solano (Quilmes)', 'san-francisco-solano-quilmes'),
(703, 6, 'San Germán', 'san-german'),
(704, 6, 'San Isidro', 'san-isidro'),
(705, 6, 'San José', 'san-jose'),
(706, 6, 'San Juan Bautista', 'san-juan-bautista'),
(707, 6, 'San Justo', 'san-justo'),
(708, 6, 'San Manuel', 'san-manuel'),
(709, 6, 'San Mauricio', 'san-mauricio'),
(710, 6, 'San Mayol', 'san-mayol'),
(711, 6, 'San Miguel', 'san-miguel'),
(712, 6, 'San Miguel del Monte', 'san-miguel-del-monte'),
(713, 6, 'San Nicolás de los Arroyos', 'san-nicolas-de-los-arroyos'),
(714, 6, 'San Pedro', 'san-pedro'),
(715, 6, 'San Román', 'san-roman'),
(716, 6, 'San Sebastián', 'san-sebastian'),
(717, 6, 'San Vicente', 'san-vicente'),
(718, 6, 'Sansinena', 'sansinena'),
(719, 6, 'Santa Clara del Mar', 'santa-clara-del-mar'),
(720, 6, 'Santa Coloma', 'santa-coloma'),
(721, 6, 'Santa Elena', 'santa-elena'),
(722, 6, 'Santa Eleodora', 'santa-eleodora'),
(723, 6, 'Santa Lucía', 'santa-lucia'),
(724, 6, 'Santa Luisa', 'santa-luisa'),
(725, 6, 'Santa María', 'santa-maria'),
(726, 6, 'Santa Regina', 'santa-regina'),
(727, 6, 'Santa Rosa', 'santa-rosa'),
(728, 6, 'Santa Teresa', 'santa-teresa'),
(729, 6, 'Santa Teresita', 'santa-teresita'),
(730, 6, 'Santa Teresita - Mar del Tuyú', 'santa-teresita-mar-del-tuyu'),
(731, 6, 'Santo Domingo', 'santo-domingo'),
(732, 6, 'Santo Tomás', 'santo-tomas'),
(733, 6, 'Santos Lugares', 'santos-lugares'),
(734, 6, 'Sarandí', 'sarandi'),
(735, 6, 'Sarasa', 'sarasa'),
(736, 6, 'Sevigne', 'sevigne'),
(737, 6, 'Sierra Chica', 'sierra-chica'),
(738, 6, 'Sierra de la Ventana', 'sierra-de-la-ventana'),
(739, 6, 'Sierra de los Padres', 'sierra-de-los-padres'),
(740, 6, 'Sierras Bayas', 'sierras-bayas'),
(741, 6, 'Smith', 'smith'),
(742, 6, 'Sol de Mayo', 'sol-de-mayo'),
(743, 6, 'Solanet', 'solanet'),
(744, 6, 'Solís', 'solis'),
(745, 6, 'Stroeder', 'stroeder'),
(746, 6, 'Suipacha', 'suipacha'),
(747, 6, 'Sundblad', 'sundblad'),
(748, 6, 'Tamangueyú', 'tamangueyu'),
(749, 6, 'Tandil', 'tandil'),
(750, 6, 'Tapalqué', 'tapalque'),
(751, 6, 'Tapiales', 'tapiales'),
(752, 6, 'Tedín Uriburu', 'tedin-uriburu'),
(753, 6, 'Temperley', 'temperley'),
(754, 6, 'Teniente Origone', 'teniente-origone'),
(755, 6, 'Tigre', 'tigre'),
(756, 6, 'Timote', 'timote'),
(757, 6, 'Tolosa', 'tolosa'),
(758, 6, 'Tornquist', 'tornquist'),
(759, 6, 'Torres', 'torres'),
(760, 6, 'Tortuguitas (José C. Paz)', 'tortuguitas-jose-c-paz'),
(761, 6, 'Tortuguitas (Malvinas Argentinas)', 'tortuguitas-malvinas-argentinas'),
(762, 6, 'Tortuguitas (Pilar)', 'tortuguitas-pilar'),
(763, 6, 'Transradio', 'transradio'),
(764, 6, 'Trenque Lauquen', 'trenque-lauquen'),
(765, 6, 'Tres Algarrobos', 'tres-algarrobos'),
(766, 6, 'Tres Arroyos', 'tres-arroyos'),
(767, 6, 'Tres de Febrero', 'tres-de-febrero'),
(768, 6, 'Tres Lomas', 'tres-lomas'),
(769, 6, 'Tres Picos', 'tres-picos'),
(770, 6, 'Tres Sargentos', 'tres-sargentos'),
(771, 6, 'Tristán Suárez', 'tristan-suarez'),
(772, 6, 'Triunvirato', 'triunvirato'),
(773, 6, 'Troncos del Talar', 'troncos-del-talar'),
(774, 6, 'Trongé', 'tronge'),
(775, 6, 'Trujui', 'trujui'),
(776, 6, 'Turdera', 'turdera'),
(777, 6, 'Udaquiola', 'udaquiola'),
(778, 6, 'Urdampilleta', 'urdampilleta'),
(779, 6, 'Uribelarrea', 'uribelarrea'),
(780, 6, 'Va.María Irene de los Remedios Escalada', 'va-maria-irene-de-los-remedios-escalada'),
(781, 6, 'Va.Marqués Alejandro María de Aguado', 'va-marques-alejandro-maria-de-aguado'),
(782, 6, 'Valdés', 'valdes'),
(783, 6, 'Valentín Alsina', 'valentin-alsina'),
(784, 6, 'Valeria del Mar', 'valeria-del-mar'),
(785, 6, 'Vásquez', 'vasquez'),
(786, 6, 'Vedia', 'vedia'),
(787, 6, 'Velloso', 'velloso'),
(788, 6, 'Verónica', 'veronica'),
(789, 6, 'Vicente López', 'vicente-lopez'),
(790, 6, 'Victoria', 'victoria'),
(791, 6, 'Vieytes', 'vieytes'),
(792, 6, 'Villa Adelina (San Isidro)', 'villa-adelina-san-isidro'),
(793, 6, 'Villa Adelina (Vicente López)', 'villa-adelina-vicente-lopez'),
(794, 6, 'Villa Alfredo Fortabat', 'villa-alfredo-fortabat'),
(795, 6, 'Villa Alsina', 'villa-alsina'),
(796, 6, 'Villa Angélica', 'villa-angelica'),
(797, 6, 'Villa Argüello', 'villa-arguello'),
(798, 6, 'Villa Arrieta', 'villa-arrieta'),
(799, 6, 'Villa Astolfi', 'villa-astolfi'),
(800, 6, 'Villa Ayacucho', 'villa-ayacucho');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(801, 6, 'Villa Ballester', 'villa-ballester'),
(802, 6, 'Villa Bernardo Monteagudo', 'villa-bernardo-monteagudo'),
(803, 6, 'Villa Bordeau', 'villa-bordeau'),
(804, 6, 'Villa Bosch', 'villa-bosch'),
(805, 6, 'Villa Brown', 'villa-brown'),
(806, 6, 'Villa Cacique', 'villa-cacique'),
(807, 6, 'Villa Castelar', 'villa-castelar'),
(808, 6, 'Villa Catela', 'villa-catela'),
(809, 6, 'Villa Centenario', 'villa-centenario'),
(810, 6, 'Villa Chacabuco', 'villa-chacabuco'),
(811, 6, 'Villa Coronel José M. Zapiola', 'villa-coronel-jose-m-zapiola'),
(812, 6, 'Villa de Mayo', 'villa-de-mayo'),
(813, 6, 'Villa del Mar', 'villa-del-mar'),
(814, 6, 'Villa Dolores', 'villa-dolores'),
(815, 6, 'Villa Domínico', 'villa-dominico'),
(816, 6, 'Villa Eduardo Madero', 'villa-eduardo-madero'),
(817, 6, 'Villa Elisa', 'villa-elisa'),
(818, 6, 'Villa Elvira', 'villa-elvira'),
(819, 6, 'Villa España', 'villa-espana'),
(820, 6, 'Villa Esperanza', 'villa-esperanza'),
(821, 6, 'Villa Espil', 'villa-espil'),
(822, 6, 'Villa Espora', 'villa-espora'),
(823, 6, 'Villa Fiorito', 'villa-fiorito'),
(824, 6, 'Villa Flandria Norte', 'villa-flandria-norte'),
(825, 6, 'Villa Flandria Sur', 'villa-flandria-sur'),
(826, 6, 'Villa Francia', 'villa-francia'),
(827, 6, 'Villa Garibaldi', 'villa-garibaldi'),
(828, 6, 'Villa General Antonio J. de Sucre', 'villa-general-antonio-j-de-sucre'),
(829, 6, 'Villa General Eugenio Necochea', 'villa-general-eugenio-necochea'),
(830, 6, 'Villa General José Tomás Guido', 'villa-general-jose-tomas-guido'),
(831, 6, 'Villa General Juan G. Las Heras', 'villa-general-juan-g-las-heras'),
(832, 6, 'Villa General Savio', 'villa-general-savio'),
(833, 6, 'Villa Gesell', 'villa-gesell'),
(834, 6, 'Villa Gobernador Udadondo', 'villa-gobernador-udadondo'),
(835, 6, 'Villa Godoy Cruz', 'villa-godoy-cruz'),
(836, 6, 'Villa Granaderos de San Martín', 'villa-granaderos-de-san-martin'),
(837, 6, 'Villa Gregoria Matorras', 'villa-gregoria-matorras'),
(838, 6, 'Villa Grisolía', 'villa-grisolia'),
(839, 6, 'Villa Independencia', 'villa-independencia'),
(840, 6, 'Villa Iris', 'villa-iris'),
(841, 6, 'Villa José León Suárez', 'villa-jose-leon-suarez'),
(842, 6, 'Villa Juan Martín de Pueyrredón', 'villa-juan-martin-de-pueyrredon'),
(843, 6, 'Villa La Arcadia', 'villa-la-arcadia'),
(844, 6, 'Villa La Florida', 'villa-la-florida'),
(845, 6, 'Villa Laguna La Brava', 'villa-laguna-la-brava'),
(846, 6, 'Villa Lía', 'villa-lia'),
(847, 6, 'Villa Libertad', 'villa-libertad'),
(848, 6, 'Villa Luzuriaga', 'villa-luzuriaga'),
(849, 6, 'Villa Lynch', 'villa-lynch'),
(850, 6, 'Villa Lynch Pueyrredón', 'villa-lynch-pueyrredon'),
(851, 6, 'Villa Maipú', 'villa-maipu'),
(852, 6, 'Villa Manuel Pomar (Colón)', 'villa-manuel-pomar-colon'),
(853, 6, 'Villa Manuel Pomar (Rojas)', 'villa-manuel-pomar-rojas'),
(854, 6, 'Villa María', 'villa-maria'),
(855, 6, 'Villa Martelli', 'villa-martelli'),
(856, 6, 'Villa Moll', 'villa-moll'),
(857, 6, 'Villa Montoro', 'villa-montoro'),
(858, 6, 'Villa Nueva', 'villa-nueva'),
(859, 6, 'Villa Ortiz', 'villa-ortiz'),
(860, 6, 'Villa Parque Cecir', 'villa-parque-cecir'),
(861, 6, 'Villa Parque Girado', 'villa-parque-girado'),
(862, 6, 'Villa Parque Presidente Figueroa Alcorta', 'villa-parque-presidente-figueroa-alcorta'),
(863, 6, 'Villa Parque San Lorenzo', 'villa-parque-san-lorenzo'),
(864, 6, 'Villa Parque Sicardi', 'villa-parque-sicardi'),
(865, 6, 'Villa Porteña', 'villa-portena'),
(866, 6, 'Villa Progreso', 'villa-progreso'),
(867, 6, 'Villa Raffo', 'villa-raffo'),
(868, 6, 'Villa Rodríguez', 'villa-rodriguez'),
(869, 6, 'Villa Rosa', 'villa-rosa'),
(870, 6, 'Villa Roth', 'villa-roth'),
(871, 6, 'Villa Ruiz', 'villa-ruiz'),
(872, 6, 'Villa Saboya', 'villa-saboya'),
(873, 6, 'Villa San Andrés', 'villa-san-andres'),
(874, 6, 'Villa San Carlos', 'villa-san-carlos'),
(875, 6, 'Villa San José', 'villa-san-jose'),
(876, 6, 'Villa San Luis', 'villa-san-luis'),
(877, 6, 'Villa Santa Rosa', 'villa-santa-rosa'),
(878, 6, 'Villa Santos Tesei', 'villa-santos-tesei'),
(879, 6, 'Villa Sarmiento', 'villa-sarmiento'),
(880, 6, 'Villa Sauze', 'villa-sauze'),
(881, 6, 'Villa Vatteone', 'villa-vatteone'),
(882, 6, 'Villa Ventana', 'villa-ventana'),
(883, 6, 'Villa Yapeyú', 'villa-yapeyu'),
(884, 6, 'Villa Zula', 'villa-zula'),
(885, 6, 'Villalonga', 'villalonga'),
(886, 6, 'Villanueva', 'villanueva'),
(887, 6, 'Villars', 'villars'),
(888, 6, 'Viña', 'vina'),
(889, 6, 'Virrey del Pino', 'virrey-del-pino'),
(890, 6, 'Virreyes', 'virreyes'),
(891, 6, 'Vivoratá', 'vivorata'),
(892, 6, 'Warnes', 'warnes'),
(893, 6, 'Wilde', 'wilde'),
(894, 6, 'William C. Morris', 'william-c-morris'),
(895, 6, 'Yutuyaco', 'yutuyaco'),
(896, 6, 'Zárate', 'zarate'),
(897, 6, 'Zavalía', 'zavalia'),
(898, 6, 'Zelaya', 'zelaya'),
(899, 6, 'Zenón Videla Dorna', 'zenon-videla-dorna'),
(900, 10, 'Aconquija', 'aconquija'),
(901, 10, 'Alijilán', 'alijilan'),
(902, 10, 'Alto de las Juntas', 'alto-de-las-juntas'),
(903, 10, 'Amadores', 'amadores'),
(904, 10, 'Amanao', 'amanao'),
(905, 10, 'Ancasti', 'ancasti'),
(906, 10, 'Andalgalá', 'andalgala'),
(907, 10, 'Andalhualá', 'andalhuala'),
(908, 10, 'Anillaco', 'anillaco'),
(909, 10, 'Anquincila', 'anquincila'),
(910, 10, 'Antofagasta de la Sierra', 'antofagasta-de-la-sierra'),
(911, 10, 'Bañado de Ovanta', 'banado-de-ovanta'),
(912, 10, 'Barranca Larga', 'barranca-larga'),
(913, 10, 'Belén', 'belen'),
(914, 10, 'Buena Vista', 'buena-vista'),
(915, 10, 'Capayán', 'capayan'),
(916, 10, 'Casa de Piedra (La Paz)', 'casa-de-piedra-la-paz'),
(917, 10, 'Casa de Piedra (Santa María)', 'casa-de-piedra-santa-maria'),
(918, 10, 'Chañar Punco', 'chanar-punco'),
(919, 10, 'Chaquiago', 'chaquiago'),
(920, 10, 'Choya', 'choya'),
(921, 10, 'Chuchucaruana', 'chuchucaruana'),
(922, 10, 'Chumbicha', 'chumbicha'),
(923, 10, 'Colana', 'colana'),
(924, 10, 'Colonia del Valle', 'colonia-del-valle'),
(925, 10, 'Colonia Nueva Coneta', 'colonia-nueva-coneta'),
(926, 10, 'Colpes (Ambato)', 'colpes-ambato'),
(927, 10, 'Colpes (Pomán)', 'colpes-poman'),
(928, 10, 'Concepción', 'concepcion'),
(929, 10, 'Cóndor Huasi', 'condor-huasi'),
(930, 10, 'Coneta', 'coneta'),
(931, 10, 'Copacabana', 'copacabana'),
(932, 10, 'Corral Quemado', 'corral-quemado'),
(933, 10, 'El Alamito', 'el-alamito'),
(934, 10, 'El Alto', 'el-alto'),
(935, 10, 'El Aybal', 'el-aybal'),
(936, 10, 'El Bañado (Capayán)', 'el-banado-capayan'),
(937, 10, 'El Bañado (La Paz)', 'el-banado-la-paz'),
(938, 10, 'El Bañado (Valle Viejo)', 'el-banado-valle-viejo'),
(939, 10, 'El Bolsón', 'el-bolson'),
(940, 10, 'El Cajón', 'el-cajon'),
(941, 10, 'El Cerrito', 'el-cerrito'),
(942, 10, 'El Desmonte', 'el-desmonte'),
(943, 10, 'El Divisadero', 'el-divisadero'),
(944, 10, 'El Durazno', 'el-durazno'),
(945, 10, 'El Hueco', 'el-hueco'),
(946, 10, 'El Lindero', 'el-lindero'),
(947, 10, 'El Pajonal', 'el-pajonal'),
(948, 10, 'El Peñón', 'el-penon'),
(949, 10, 'El Portezuelo', 'el-portezuelo'),
(950, 10, 'El Potrero', 'el-potrero'),
(951, 10, 'El Puesto (Santa María)', 'el-puesto-santa-maria'),
(952, 10, 'El Puesto (Tinogasta)', 'el-puesto-tinogasta'),
(953, 10, 'El Quimilo', 'el-quimilo'),
(954, 10, 'El Rodeo', 'el-rodeo'),
(955, 10, 'El Salado', 'el-salado'),
(956, 10, 'Esquiú', 'esquiu'),
(957, 10, 'Famatanca', 'famatanca'),
(958, 10, 'Farallón Negro', 'farallon-negro'),
(959, 10, 'Fiambalá', 'fiambala'),
(960, 10, 'Fuerte Quemado', 'fuerte-quemado'),
(961, 10, 'Guayamba', 'guayamba'),
(962, 10, 'Hualfín', 'hualfin'),
(963, 10, 'Huaycama (Ambato)', 'huaycama-ambato'),
(964, 10, 'Huaycama (Valle Viejo)', 'huaycama-valle-viejo'),
(965, 10, 'Huillapima', 'huillapima'),
(966, 10, 'Icaño', 'icano'),
(967, 10, 'Infanzón', 'infanzon'),
(968, 10, 'Jacipunco', 'jacipunco'),
(969, 10, 'La Candelaria', 'la-candelaria'),
(970, 10, 'La Carrera', 'la-carrera'),
(971, 10, 'La Dorada', 'la-dorada'),
(972, 10, 'La Falda de San Antonio', 'la-falda-de-san-antonio'),
(973, 10, 'La Guardia', 'la-guardia'),
(974, 10, 'La Higuera', 'la-higuera'),
(975, 10, 'La Hoyada', 'la-hoyada'),
(976, 10, 'La Majada', 'la-majada'),
(977, 10, 'La Merced', 'la-merced'),
(978, 10, 'La Mesada', 'la-mesada'),
(979, 10, 'La Puerta', 'la-puerta'),
(980, 10, 'La Puntilla (Belén)', 'la-puntilla-belen'),
(981, 10, 'La Puntilla (Santa María)', 'la-puntilla-santa-maria'),
(982, 10, 'La Puntilla (Tinogasta)', 'la-puntilla-tinogasta'),
(983, 10, 'La Ramadita', 'la-ramadita'),
(984, 10, 'La Tercena', 'la-tercena'),
(985, 10, 'La Viña', 'la-vina'),
(986, 10, 'Lampacito', 'lampacito'),
(987, 10, 'Las Cañas', 'las-canas'),
(988, 10, 'Las Chacritas', 'las-chacritas'),
(989, 10, 'Las Esquinas', 'las-esquinas'),
(990, 10, 'Las Juntas (Ambato)', 'las-juntas-ambato'),
(991, 10, 'Las Juntas (Belén)', 'las-juntas-belen'),
(992, 10, 'Las Lajas', 'las-lajas'),
(993, 10, 'Las Mojarras', 'las-mojarras'),
(994, 10, 'Las Palmitas', 'las-palmitas'),
(995, 10, 'Las Tejas', 'las-tejas'),
(996, 10, 'Lavalle', 'lavalle'),
(997, 10, 'Londres', 'londres'),
(998, 10, 'Los Altos', 'los-altos'),
(999, 10, 'Los Angeles', 'los-angeles'),
(1000, 10, 'Los Ángeles Norte', 'los-angeles-norte');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1001, 10, 'Los Ángeles Sur', 'los-angeles-sur'),
(1002, 10, 'Los Castillos', 'los-castillos'),
(1003, 10, 'Los Corrales', 'los-corrales'),
(1004, 10, 'Los Nacimientos (Antofagasta de la Sierra)', 'los-nacimientos-antofagasta-de-la-sierra'),
(1005, 10, 'Los Nacimientos (Belén)', 'los-nacimientos-belen'),
(1006, 10, 'Los Talas', 'los-talas'),
(1007, 10, 'Los Varela', 'los-varela'),
(1008, 10, 'Manantiales', 'manantiales'),
(1009, 10, 'Medanitos (Santa María)', 'medanitos-santa-maria'),
(1010, 10, 'Medanitos (Tinogasta)', 'medanitos-tinogasta'),
(1011, 10, 'Miraflores', 'miraflores'),
(1012, 10, 'Monte Potrero', 'monte-potrero'),
(1013, 10, 'Mutquin', 'mutquin'),
(1014, 10, 'Palo Blanco', 'palo-blanco'),
(1015, 10, 'Palo Labrado', 'palo-labrado'),
(1016, 10, 'Palo Seco', 'palo-seco'),
(1017, 10, 'Pampa Blanca', 'pampa-blanca'),
(1018, 10, 'Polcos', 'polcos'),
(1019, 10, 'Pomán', 'poman'),
(1020, 10, 'Pomancillo Este', 'pomancillo-este'),
(1021, 10, 'Pomancillo Oeste', 'pomancillo-oeste'),
(1022, 10, 'Pozo del Mistol', 'pozo-del-mistol'),
(1023, 10, 'Puerta de Corral Quemado', 'puerta-de-corral-quemado'),
(1024, 10, 'Puerta de San José', 'puerta-de-san-jose'),
(1025, 10, 'Punta de Balasto', 'punta-de-balasto'),
(1026, 10, 'Quirós', 'quiros'),
(1027, 10, 'Ramblones', 'ramblones'),
(1028, 10, 'Recreo', 'recreo'),
(1029, 10, 'Rincón', 'rincon'),
(1030, 10, 'San Antonio (Fray Mamerto Esquiú)', 'san-antonio-fray-mamerto-esquiu'),
(1031, 10, 'San Antonio (La Paz)', 'san-antonio-la-paz'),
(1032, 10, 'San Antonio (Paclín)', 'san-antonio-paclin'),
(1033, 10, 'San Fernando del Valle de Catamarca', 'san-fernando-del-valle-de-catamarca'),
(1034, 10, 'San Isidro', 'san-isidro'),
(1035, 10, 'San José (Fray Mamerto Esquiú)', 'san-jose-fray-mamerto-esquiu'),
(1036, 10, 'San José (Santa María)', 'san-jose-santa-maria'),
(1037, 10, 'San José Banda', 'san-jose-banda'),
(1038, 10, 'San José Norte', 'san-jose-norte'),
(1039, 10, 'San José Villa', 'san-jose-villa'),
(1040, 10, 'San Martín', 'san-martin'),
(1041, 10, 'San Miguel', 'san-miguel'),
(1042, 10, 'San Pablo', 'san-pablo'),
(1043, 10, 'San Pedro (Capayán)', 'san-pedro-capayan'),
(1044, 10, 'San Pedro (Santa Rosa)', 'san-pedro-santa-rosa'),
(1045, 10, 'Santa Cruz', 'santa-cruz'),
(1046, 10, 'Santa María', 'santa-maria'),
(1047, 10, 'Santa Rosa', 'santa-rosa'),
(1048, 10, 'Saujil (Pomán)', 'saujil-poman'),
(1049, 10, 'Saujil (Tinogasta)', 'saujil-tinogasta'),
(1050, 10, 'Siján', 'sijan'),
(1051, 10, 'Singuil', 'singuil'),
(1052, 10, 'Sumalao', 'sumalao'),
(1053, 10, 'Tapso', 'tapso'),
(1054, 10, 'Tinogasta', 'tinogasta'),
(1055, 10, 'Vilismán', 'vilisman'),
(1056, 10, 'Villa de Balcozna', 'villa-de-balcozna'),
(1057, 10, 'Villa Dolores', 'villa-dolores'),
(1058, 10, 'Villa Las Pirquitas', 'villa-las-pirquitas'),
(1059, 10, 'Villa Vil', 'villa-vil'),
(1060, 10, 'Yapes', 'yapes'),
(1061, 14, '1° de Agosto', '1-de-agosto'),
(1062, 14, 'Achiras', 'achiras'),
(1063, 14, 'Adelia María', 'adelia-maria'),
(1064, 14, 'Agua de Oro', 'agua-de-oro'),
(1065, 14, 'Alcira Gigena', 'alcira-gigena'),
(1066, 14, 'Aldea Santa María', 'aldea-santa-maria'),
(1067, 14, 'Alejandro Roca', 'alejandro-roca'),
(1068, 14, 'Alejo Ledesma', 'alejo-ledesma'),
(1069, 14, 'Alicia', 'alicia'),
(1070, 14, 'Allmirante Brown', 'allmirante-brown'),
(1071, 14, 'Almafuerte', 'almafuerte'),
(1072, 14, 'Alpa Corral', 'alpa-corral'),
(1073, 14, 'Alta Gracia', 'alta-gracia'),
(1074, 14, 'Alto Alegre', 'alto-alegre'),
(1075, 14, 'Alto de los Quebrachos', 'alto-de-los-quebrachos'),
(1076, 14, 'Alto Resbaloso - El Barrial', 'alto-resbaloso-el-barrial'),
(1077, 14, 'Altos de Chipión', 'altos-de-chipion'),
(1078, 14, 'Amboy', 'amboy'),
(1079, 14, 'Ambul', 'ambul'),
(1080, 14, 'Ana Zumarán', 'ana-zumaran'),
(1081, 14, 'Anisacate', 'anisacate'),
(1082, 14, 'Arias', 'arias'),
(1083, 14, 'Arroyito', 'arroyito'),
(1084, 14, 'Arroyo Algodón', 'arroyo-algodon'),
(1085, 14, 'Arroyo Cabral', 'arroyo-cabral'),
(1086, 14, 'Arroyo Los Patos', 'arroyo-los-patos'),
(1087, 14, 'Arroyo San Antonio', 'arroyo-san-antonio'),
(1088, 14, 'Ascochinga', 'ascochinga'),
(1089, 14, 'Assunta', 'assunta'),
(1090, 14, 'Atahona', 'atahona'),
(1091, 14, 'Ausonia', 'ausonia'),
(1092, 14, 'Avellaneda', 'avellaneda'),
(1093, 14, 'Ballesteros', 'ballesteros'),
(1094, 14, 'Ballesteros Sud', 'ballesteros-sud'),
(1095, 14, 'Balnearia', 'balnearia'),
(1096, 14, 'Bañado de Soto', 'banado-de-soto'),
(1097, 14, 'Barrio Gilbert', 'barrio-gilbert'),
(1098, 14, 'Barrio Gilbert (1º de Mayo) - Tejas Tres', 'barrio-gilbert-1o-de-mayo-tejas-tres'),
(1099, 14, 'Barrio Villa del Parque', 'barrio-villa-del-parque'),
(1100, 14, 'Bell Ville', 'bell-ville'),
(1101, 14, 'Bengolea', 'bengolea'),
(1102, 14, 'Benjamín Gould', 'benjamin-gould'),
(1103, 14, 'Berrotarán', 'berrotaran'),
(1104, 14, 'Bialet Massé', 'bialet-masse'),
(1105, 14, 'Bouwer', 'bouwer'),
(1106, 14, 'Brinkmann', 'brinkmann'),
(1107, 14, 'Bulnes', 'bulnes'),
(1108, 14, 'Cabalango', 'cabalango'),
(1109, 14, 'Calchín', 'calchin'),
(1110, 14, 'Calchín Oeste', 'calchin-oeste'),
(1111, 14, 'Camilo Aldao', 'camilo-aldao'),
(1112, 14, 'Caminiaga', 'caminiaga'),
(1113, 14, 'Cañada de Luque', 'canada-de-luque'),
(1114, 14, 'Cañada de Machado', 'canada-de-machado'),
(1115, 14, 'Cañada de Río Pinto', 'canada-de-rio-pinto'),
(1116, 14, 'Cañada del Sauce', 'canada-del-sauce'),
(1117, 14, 'Canals', 'canals'),
(1118, 14, 'Candelaria Sur', 'candelaria-sur'),
(1119, 14, 'Capilla de los Remedios', 'capilla-de-los-remedios'),
(1120, 14, 'Capilla de Sitón', 'capilla-de-siton'),
(1121, 14, 'Capilla del Carmen', 'capilla-del-carmen'),
(1122, 14, 'Capilla del Monte', 'capilla-del-monte'),
(1123, 14, 'Capitán General Bernardo O''Higgins', 'capitan-general-bernardo-o-higgins'),
(1124, 14, 'Carnerillo', 'carnerillo'),
(1125, 14, 'Carrilobo', 'carrilobo'),
(1126, 14, 'Casa Grande', 'casa-grande'),
(1127, 14, 'Caseros Centro', 'caseros-centro'),
(1128, 14, 'Cavanagh', 'cavanagh'),
(1129, 14, 'Cerro Colorado', 'cerro-colorado'),
(1130, 14, 'Chaján', 'chajan'),
(1131, 14, 'Chalacea', 'chalacea'),
(1132, 14, 'Chañar Viejo', 'chanar-viejo'),
(1133, 14, 'Chancani', 'chancani'),
(1134, 14, 'Charbonier', 'charbonier'),
(1135, 14, 'Charras', 'charras'),
(1136, 14, 'Chazón', 'chazon'),
(1137, 14, 'Chilibroste', 'chilibroste'),
(1138, 14, 'Chucul', 'chucul'),
(1139, 14, 'Chuña', 'chuna'),
(1140, 14, 'Chuña Huasi', 'chuna-huasi'),
(1141, 14, 'Churqui Cañada', 'churqui-canada'),
(1142, 14, 'Ciénaga del Coro', 'cienaga-del-coro'),
(1143, 14, 'Cintra', 'cintra'),
(1144, 14, 'Ciudad de los Niños', 'ciudad-de-los-ninos'),
(1145, 14, 'Colazo', 'colazo'),
(1146, 14, 'Colonia 10 de Julio', 'colonia-10-de-julio'),
(1147, 14, 'Colonia Almada', 'colonia-almada'),
(1148, 14, 'Colonia Anita', 'colonia-anita'),
(1149, 14, 'Colonia Barge', 'colonia-barge'),
(1150, 14, 'Colonia Bismarck', 'colonia-bismarck'),
(1151, 14, 'Colonia Bremen', 'colonia-bremen'),
(1152, 14, 'Colonia Caroya', 'colonia-caroya'),
(1153, 14, 'Colonia Italiana', 'colonia-italiana'),
(1154, 14, 'Colonia Iturraspe', 'colonia-iturraspe'),
(1155, 14, 'Colonia Las Cuatro Esquinas', 'colonia-las-cuatro-esquinas'),
(1156, 14, 'Colonia Las Pichanas', 'colonia-las-pichanas'),
(1157, 14, 'Colonia Marina', 'colonia-marina'),
(1158, 14, 'Colonia Prosperidad', 'colonia-prosperidad'),
(1159, 14, 'Colonia San Bartolomé', 'colonia-san-bartolome'),
(1160, 14, 'Colonia San Pedro', 'colonia-san-pedro'),
(1161, 14, 'Colonia Santa María', 'colonia-santa-maria'),
(1162, 14, 'Colonia Tirolesa', 'colonia-tirolesa'),
(1163, 14, 'Colonia Valtelina', 'colonia-valtelina'),
(1164, 14, 'Colonia Veinticinco', 'colonia-veinticinco'),
(1165, 14, 'Colonia Vicente Agüero', 'colonia-vicente-aguero'),
(1166, 14, 'Colonia Videla', 'colonia-videla'),
(1167, 14, 'Colonia Vignaud', 'colonia-vignaud'),
(1168, 14, 'Conlara', 'conlara'),
(1169, 14, 'Copacabana', 'copacabana'),
(1170, 14, 'Córdoba', 'cordoba'),
(1171, 14, 'Coronel Baigorria', 'coronel-baigorria'),
(1172, 14, 'Coronel Moldes', 'coronel-moldes'),
(1173, 14, 'Corral de Bustos', 'corral-de-bustos'),
(1174, 14, 'Corralito', 'corralito'),
(1175, 14, 'Cosquín', 'cosquin'),
(1176, 14, 'Costasacate', 'costasacate'),
(1177, 14, 'Cruz Alta', 'cruz-alta'),
(1178, 14, 'Cruz de Caña', 'cruz-de-cana'),
(1179, 14, 'Cruz del Eje', 'cruz-del-eje'),
(1180, 14, 'Cuesta Blanca', 'cuesta-blanca'),
(1181, 14, 'Dalmacio Vélez', 'dalmacio-velez'),
(1182, 14, 'Deán Funes', 'dean-funes'),
(1183, 14, 'Del Campillo', 'del-campillo'),
(1184, 14, 'Despeñaderos', 'despenaderos'),
(1185, 14, 'Devoto', 'devoto'),
(1186, 14, 'Diego de Rojas', 'diego-de-rojas'),
(1187, 14, 'Dique Chico', 'dique-chico'),
(1188, 14, 'Dumesnil', 'dumesnil'),
(1189, 14, 'El Alcalde', 'el-alcalde'),
(1190, 14, 'El Arañado', 'el-aranado'),
(1191, 14, 'El Brete', 'el-brete'),
(1192, 14, 'El Chacho', 'el-chacho'),
(1193, 14, 'El Corcovado - El Torreón', 'el-corcovado-el-torreon'),
(1194, 14, 'El Crispín', 'el-crispin'),
(1195, 14, 'El Diquecito', 'el-diquecito'),
(1196, 14, 'El Durazno', 'el-durazno'),
(1197, 14, 'El Fortín', 'el-fortin'),
(1198, 14, 'El Fuertecito', 'el-fuertecito'),
(1199, 14, 'El Manzano', 'el-manzano'),
(1200, 14, 'El Pueblito (Colón)', 'el-pueblito-colon');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1201, 14, 'El Pueblito (San Javier)', 'el-pueblito-san-javier'),
(1202, 14, 'El Rastreador', 'el-rastreador'),
(1203, 14, 'El Rodeo', 'el-rodeo'),
(1204, 14, 'El Tío', 'el-tio'),
(1205, 14, 'El Tuscal', 'el-tuscal'),
(1206, 14, 'El Valle', 'el-valle'),
(1207, 14, 'Elena', 'elena'),
(1208, 14, 'Embalse', 'embalse'),
(1209, 14, 'Esquina', 'esquina'),
(1210, 14, 'Estación Lecueder', 'estacion-lecueder'),
(1211, 14, 'Estación Luxardo', 'estacion-luxardo'),
(1212, 14, 'Estancia de Guadalupe', 'estancia-de-guadalupe'),
(1213, 14, 'Estancia Vieja', 'estancia-vieja'),
(1214, 14, 'Etruria', 'etruria'),
(1215, 14, 'Eufrasio Loza', 'eufrasio-loza'),
(1216, 14, 'Falda del Cañete', 'falda-del-canete'),
(1217, 14, 'Falda del Carmen', 'falda-del-carmen'),
(1218, 14, 'Freyre', 'freyre'),
(1219, 14, 'General Baldissera', 'general-baldissera'),
(1220, 14, 'General Cabrera', 'general-cabrera'),
(1221, 14, 'General Deheza', 'general-deheza'),
(1222, 14, 'General Fotheringham', 'general-fotheringham'),
(1223, 14, 'General Levalle', 'general-levalle'),
(1224, 14, 'General Paz', 'general-paz'),
(1225, 14, 'General Roca', 'general-roca'),
(1226, 14, 'Guanaco Muerto', 'guanaco-muerto'),
(1227, 14, 'Guasapampa', 'guasapampa'),
(1228, 14, 'Guatimozín', 'guatimozin'),
(1229, 14, 'Guiñazú Norte', 'guinazu-norte'),
(1230, 14, 'Gutemberg', 'gutemberg'),
(1231, 14, 'Hernando', 'hernando'),
(1232, 14, 'Hipólito Bouchard', 'hipolito-bouchard'),
(1233, 14, 'Huanchillas', 'huanchillas'),
(1234, 14, 'Huerta Grande', 'huerta-grande'),
(1235, 14, 'Huinca Renancó', 'huinca-renanco'),
(1236, 14, 'Idiazabal', 'idiazabal'),
(1237, 14, 'Impira', 'impira'),
(1238, 14, 'Inriville', 'inriville'),
(1239, 14, 'Isla Verde', 'isla-verde'),
(1240, 14, 'Italó', 'italo'),
(1241, 14, 'James Craik', 'james-craik'),
(1242, 14, 'Jardín Arenales', 'jardin-arenales'),
(1243, 14, 'Jesús María', 'jesus-maria'),
(1244, 14, 'José de la Quintana', 'jose-de-la-quintana'),
(1245, 14, 'Juárez Celman', 'juarez-celman'),
(1246, 14, 'Justiniano Posse', 'justiniano-posse'),
(1247, 14, 'Kilómetro 658', 'kilometro-658'),
(1248, 14, 'La Batea', 'la-batea'),
(1249, 14, 'La Boca del Río', 'la-boca-del-rio'),
(1250, 14, 'La Calera', 'la-calera'),
(1251, 14, 'La Carlota', 'la-carlota'),
(1252, 14, 'La Carolina', 'la-carolina'),
(1253, 14, 'La Cautiva', 'la-cautiva'),
(1254, 14, 'La Cesira', 'la-cesira'),
(1255, 14, 'La Cruz', 'la-cruz'),
(1256, 14, 'La Cumbre', 'la-cumbre'),
(1257, 14, 'La Cumbrecita', 'la-cumbrecita'),
(1258, 14, 'La Donosa', 'la-donosa'),
(1259, 14, 'La Falda', 'la-falda'),
(1260, 14, 'La Floresta', 'la-floresta'),
(1261, 14, 'La Francia', 'la-francia'),
(1262, 14, 'La Gilda', 'la-gilda'),
(1263, 14, 'La Granja', 'la-granja'),
(1264, 14, 'La Higuera', 'la-higuera'),
(1265, 14, 'La Laguna', 'la-laguna'),
(1266, 14, 'La Paisanita', 'la-paisanita'),
(1267, 14, 'La Palestina', 'la-palestina'),
(1268, 14, 'La Pampa', 'la-pampa'),
(1269, 14, 'La Paquita', 'la-paquita'),
(1270, 14, 'La Para', 'la-para'),
(1271, 14, 'La Paz', 'la-paz'),
(1272, 14, 'La Playa', 'la-playa'),
(1273, 14, 'La Playosa', 'la-playosa'),
(1274, 14, 'La Población', 'la-poblacion'),
(1275, 14, 'La Posta', 'la-posta'),
(1276, 14, 'La Puerta (Colón)', 'la-puerta-colon'),
(1277, 14, 'La Puerta (Río Primero)', 'la-puerta-rio-primero'),
(1278, 14, 'La Quinta', 'la-quinta'),
(1279, 14, 'La Rancherita y Las Cascadas', 'la-rancherita-y-las-cascadas'),
(1280, 14, 'La Rinconada', 'la-rinconada'),
(1281, 14, 'La Serranita', 'la-serranita'),
(1282, 14, 'La Tordilla', 'la-tordilla'),
(1283, 14, 'La Travesía', 'la-travesia'),
(1284, 14, 'Laborde', 'laborde'),
(1285, 14, 'Laboulaye', 'laboulaye'),
(1286, 14, 'Laguna Larga', 'laguna-larga'),
(1287, 14, 'Las Acequias', 'las-acequias'),
(1288, 14, 'Las Albahacas', 'las-albahacas'),
(1289, 14, 'Las Arrias', 'las-arrias'),
(1290, 14, 'Las Bajadas', 'las-bajadas'),
(1291, 14, 'Las Caleras', 'las-caleras'),
(1292, 14, 'Las Calles', 'las-calles'),
(1293, 14, 'Las Cañadas', 'las-canadas'),
(1294, 14, 'Las Chacras', 'las-chacras'),
(1295, 14, 'Las Gramillas', 'las-gramillas'),
(1296, 14, 'Las Higueras', 'las-higueras'),
(1297, 14, 'Las Isletillas', 'las-isletillas'),
(1298, 14, 'Las Jarillas', 'las-jarillas'),
(1299, 14, 'Las Junturas', 'las-junturas'),
(1300, 14, 'Las Mojarras', 'las-mojarras'),
(1301, 14, 'Las Palmas', 'las-palmas'),
(1302, 14, 'Las Peñas (Río Cuarto)', 'las-penas-rio-cuarto'),
(1303, 14, 'Las Peñas (Totoral)', 'las-penas-totoral'),
(1304, 14, 'Las Perdices', 'las-perdices'),
(1305, 14, 'Las Playas', 'las-playas'),
(1306, 14, 'Las Quintas', 'las-quintas'),
(1307, 14, 'Las Rabonas', 'las-rabonas'),
(1308, 14, 'Las Saladas', 'las-saladas'),
(1309, 14, 'Las Tapias', 'las-tapias'),
(1310, 14, 'Las Varas', 'las-varas'),
(1311, 14, 'Las Varillas', 'las-varillas'),
(1312, 14, 'Las Vertientes', 'las-vertientes'),
(1313, 14, 'Leguizamón', 'leguizamon'),
(1314, 14, 'Leones', 'leones'),
(1315, 14, 'Los Cedros', 'los-cedros'),
(1316, 14, 'Los Cerrillos', 'los-cerrillos'),
(1317, 14, 'Los Chañaritos (Cruz del Eje)', 'los-chanaritos-cruz-del-eje'),
(1318, 14, 'Los Chañaritos (Río Segundo)', 'los-chanaritos-rio-segundo'),
(1319, 14, 'Los Cisnes', 'los-cisnes'),
(1320, 14, 'Los Cocos', 'los-cocos'),
(1321, 14, 'Los Cóndores', 'los-condores'),
(1322, 14, 'Los Hornillos', 'los-hornillos'),
(1323, 14, 'Los Hoyos', 'los-hoyos'),
(1324, 14, 'Los Mistoles', 'los-mistoles'),
(1325, 14, 'Los Molinos', 'los-molinos'),
(1326, 14, 'Los Pozos', 'los-pozos'),
(1327, 14, 'Los Reartes', 'los-reartes'),
(1328, 14, 'Los Surgentes', 'los-surgentes'),
(1329, 14, 'Los Talares', 'los-talares'),
(1330, 14, 'Los Zorros', 'los-zorros'),
(1331, 14, 'Lozada', 'lozada'),
(1332, 14, 'Luca', 'luca'),
(1333, 14, 'Lucio V. Mansilla', 'lucio-v-mansilla'),
(1334, 14, 'Luque', 'luque'),
(1335, 14, 'Lutti', 'lutti'),
(1336, 14, 'Luyaba', 'luyaba'),
(1337, 14, 'Malagueño', 'malagueno'),
(1338, 14, 'Malena', 'malena'),
(1339, 14, 'Mallín', 'mallin'),
(1340, 14, 'Malvinas Argentinas', 'malvinas-argentinas'),
(1341, 14, 'Manfredi', 'manfredi'),
(1342, 14, 'Maquinista Gallini', 'maquinista-gallini'),
(1343, 14, 'Marcos Juárez', 'marcos-juarez'),
(1344, 14, 'Marull', 'marull'),
(1345, 14, 'Matorrales', 'matorrales'),
(1346, 14, 'Mattaldi', 'mattaldi'),
(1347, 14, 'Mayu Sumaj', 'mayu-sumaj'),
(1348, 14, 'Media Naranja', 'media-naranja'),
(1349, 14, 'Melo', 'melo'),
(1350, 14, 'Mendiolaza', 'mendiolaza'),
(1351, 14, 'Mi Granja', 'mi-granja'),
(1352, 14, 'Mi Valle', 'mi-valle'),
(1353, 14, 'Mina Clavero', 'mina-clavero'),
(1354, 14, 'Miramar', 'miramar'),
(1355, 14, 'Monte Buey', 'monte-buey'),
(1356, 14, 'Monte de los Gauchos', 'monte-de-los-gauchos'),
(1357, 14, 'Monte del Rosario', 'monte-del-rosario'),
(1358, 14, 'Monte Leña', 'monte-lena'),
(1359, 14, 'Monte Maíz', 'monte-maiz'),
(1360, 14, 'Monte Ralo', 'monte-ralo'),
(1361, 14, 'Montecristo', 'montecristo'),
(1362, 14, 'Morrison', 'morrison'),
(1363, 14, 'Morteros', 'morteros'),
(1364, 14, 'Mussi', 'mussi'),
(1365, 14, 'Nicolás Bruzzone', 'nicolas-bruzzone'),
(1366, 14, 'Noetinger', 'noetinger'),
(1367, 14, 'Nono', 'nono'),
(1368, 14, 'Obispo Trejo', 'obispo-trejo'),
(1369, 14, 'Olaeta', 'olaeta'),
(1370, 14, 'Oliva', 'oliva'),
(1371, 14, 'Olivares de San Nicolás', 'olivares-de-san-nicolas'),
(1372, 14, 'Onagoity', 'onagoity'),
(1373, 14, 'Oncativo', 'oncativo'),
(1374, 14, 'Ordoñez', 'ordonez'),
(1375, 14, 'Pacheco de Melo', 'pacheco-de-melo'),
(1376, 14, 'Pampayasta Norte', 'pampayasta-norte'),
(1377, 14, 'Pampayasta Sud', 'pampayasta-sud'),
(1378, 14, 'Panaholma', 'panaholma'),
(1379, 14, 'Parque Calmayo', 'parque-calmayo'),
(1380, 14, 'Parque Norte', 'parque-norte'),
(1381, 14, 'Parque Norte - Ciudad de los Niños - Guiñazú Norte', 'parque-norte-ciudad-de-los-ninos-gui-14021310'),
(1382, 14, 'Pascanas', 'pascanas'),
(1383, 14, 'Pasco', 'pasco'),
(1384, 14, 'Paso del Durazno (Juárez Celman)', 'paso-del-durazno-juarez-celman'),
(1385, 14, 'Paso del Durazno (Río Cuarto)', 'paso-del-durazno-rio-cuarto'),
(1386, 14, 'Paso Viejo', 'paso-viejo'),
(1387, 14, 'Pilar', 'pilar'),
(1388, 14, 'Pincén', 'pincen'),
(1389, 14, 'Piquillín', 'piquillin'),
(1390, 14, 'Plaza de Mercedes', 'plaza-de-mercedes'),
(1391, 14, 'Plaza Luxardo', 'plaza-luxardo'),
(1392, 14, 'Plaza San Francisco', 'plaza-san-francisco'),
(1393, 14, 'Porteña', 'portena'),
(1394, 14, 'Potrero de Garay', 'potrero-de-garay'),
(1395, 14, 'Pozo del Molle', 'pozo-del-molle'),
(1396, 14, 'Pozo Nuevo', 'pozo-nuevo'),
(1397, 14, 'Pueblo Comechingones', 'pueblo-comechingones'),
(1398, 14, 'Pueblo Italiano', 'pueblo-italiano'),
(1399, 14, 'Puesto de Castro', 'puesto-de-castro'),
(1400, 14, 'Punta del Agua', 'punta-del-agua');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1401, 14, 'Quebracho Herrado', 'quebracho-herrado'),
(1402, 14, 'Quilino', 'quilino'),
(1403, 14, 'Rafael García', 'rafael-garcia'),
(1404, 14, 'Ramón J. Cárcano', 'ramon-j-carcano'),
(1405, 14, 'Ranqueles', 'ranqueles'),
(1406, 14, 'Rayo Cortado', 'rayo-cortado'),
(1407, 14, 'Rincón', 'rincon'),
(1408, 14, 'Río Bamba', 'rio-bamba'),
(1409, 14, 'Río Ceballos', 'rio-ceballos'),
(1410, 14, 'Río Cuarto', 'rio-cuarto'),
(1411, 14, 'Río de los Sauces', 'rio-de-los-sauces'),
(1412, 14, 'Río Primero', 'rio-primero'),
(1413, 14, 'Río Segundo', 'rio-segundo'),
(1414, 14, 'Río Tercero', 'rio-tercero'),
(1415, 14, 'Rosales', 'rosales'),
(1416, 14, 'Rosario del Saladillo', 'rosario-del-saladillo'),
(1417, 14, 'Sacanta', 'sacanta'),
(1418, 14, 'Sagrada Familia', 'sagrada-familia'),
(1419, 14, 'Saira', 'saira'),
(1420, 14, 'Saladillo', 'saladillo'),
(1421, 14, 'Saldán', 'saldan'),
(1422, 14, 'Salsacate', 'salsacate'),
(1423, 14, 'Salsipuedes', 'salsipuedes'),
(1424, 14, 'Sampacho', 'sampacho'),
(1425, 14, 'San Agustín', 'san-agustin'),
(1426, 14, 'San Antonio de Arredondo', 'san-antonio-de-arredondo'),
(1427, 14, 'San Antonio de Litín', 'san-antonio-de-litin'),
(1428, 14, 'San Basilio', 'san-basilio'),
(1429, 14, 'San Carlos Minas', 'san-carlos-minas'),
(1430, 14, 'San Clemente', 'san-clemente'),
(1431, 14, 'San Esteban', 'san-esteban'),
(1432, 14, 'San Francisco', 'san-francisco'),
(1433, 14, 'San Francisco del Chañar', 'san-francisco-del-chanar'),
(1434, 14, 'San Gerónimo', 'san-geronimo'),
(1435, 14, 'San Ignacio (Loteo San Javier)', 'san-ignacio-loteo-san-javier'),
(1436, 14, 'San Ignacio (Loteo Vélez Crespo)', 'san-ignacio-loteo-velez-crespo'),
(1437, 14, 'San Javier y Yacanto', 'san-javier-y-yacanto'),
(1438, 14, 'San Joaquín', 'san-joaquin'),
(1439, 14, 'San José', 'san-jose'),
(1440, 14, 'San José de la Dormida', 'san-jose-de-la-dormida'),
(1441, 14, 'San José de las Salinas', 'san-jose-de-las-salinas'),
(1442, 14, 'San Lorenzo', 'san-lorenzo'),
(1443, 14, 'San Marcos', 'san-marcos'),
(1444, 14, 'San Marcos Sierra', 'san-marcos-sierra'),
(1445, 14, 'San Martín', 'san-martin'),
(1446, 14, 'San Pedro', 'san-pedro'),
(1447, 14, 'San Pedro de Gütemberg', 'san-pedro-de-gutemberg'),
(1448, 14, 'San Pedro de Toyos', 'san-pedro-de-toyos'),
(1449, 14, 'San Pedro Norte', 'san-pedro-norte'),
(1450, 14, 'San Roque', 'san-roque'),
(1451, 14, 'San Roque del Lago', 'san-roque-del-lago'),
(1452, 14, 'San Severo', 'san-severo'),
(1453, 14, 'San Vicente', 'san-vicente'),
(1454, 14, 'Sanabria', 'sanabria'),
(1455, 14, 'Santa Catalina', 'santa-catalina'),
(1456, 14, 'Santa Catalina Holmberg', 'santa-catalina-holmberg'),
(1457, 14, 'Santa Elena', 'santa-elena'),
(1458, 14, 'Santa Eufemia', 'santa-eufemia'),
(1459, 14, 'Santa Magdalena', 'santa-magdalena'),
(1460, 14, 'Santa María de Punilla', 'santa-maria-de-punilla'),
(1461, 14, 'Santa Mónica', 'santa-monica'),
(1462, 14, 'Santa Rosa de Calamuchita', 'santa-rosa-de-calamuchita'),
(1463, 14, 'Santa Rosa de Río Primero', 'santa-rosa-de-rio-primero'),
(1464, 14, 'Santiago Temple', 'santiago-temple'),
(1465, 14, 'Sarmiento', 'sarmiento'),
(1466, 14, 'Saturnino María Laspiur', 'saturnino-maria-laspiur'),
(1467, 14, 'Sauce Arriba', 'sauce-arriba'),
(1468, 14, 'Sebastián Elcano', 'sebastian-elcano'),
(1469, 14, 'Seeber', 'seeber'),
(1470, 14, 'Segunda Usina', 'segunda-usina'),
(1471, 14, 'Serrano', 'serrano'),
(1472, 14, 'Serrezuela', 'serrezuela'),
(1473, 14, 'Silvio Pellico', 'silvio-pellico'),
(1474, 14, 'Simbolar', 'simbolar'),
(1475, 14, 'Sinsacate', 'sinsacate'),
(1476, 14, 'Socavones', 'socavones'),
(1477, 14, 'Solar de los Molinos', 'solar-de-los-molinos'),
(1478, 14, 'Suco', 'suco'),
(1479, 14, 'Tala Cañada', 'tala-canada'),
(1480, 14, 'Tala Huasi', 'tala-huasi'),
(1481, 14, 'Talaini', 'talaini'),
(1482, 14, 'Tancacha', 'tancacha'),
(1483, 14, 'Tanti', 'tanti'),
(1484, 14, 'Tejas Tres', 'tejas-tres'),
(1485, 14, 'Ticino', 'ticino'),
(1486, 14, 'Tinoco', 'tinoco'),
(1487, 14, 'Tío Pujio', 'tio-pujio'),
(1488, 14, 'Toledo', 'toledo'),
(1489, 14, 'Toro Pujio', 'toro-pujio'),
(1490, 14, 'Tosno', 'tosno'),
(1491, 14, 'Tosquitas', 'tosquitas'),
(1492, 14, 'Tránsito', 'transito'),
(1493, 14, 'Tuclame', 'tuclame'),
(1494, 14, 'Ucacha', 'ucacha'),
(1495, 14, 'Unquillo', 'unquillo'),
(1496, 14, 'Va.Ciudad Pque.Los Reartes (1° Sección)', 'va-ciudad-pque-los-reartes-1-seccion'),
(1497, 14, 'Va.Ciudad Pque.Los Reartes (3° Sección)', 'va-ciudad-pque-los-reartes-3-seccion'),
(1498, 14, 'Valle de Anisacate', 'valle-de-anisacate'),
(1499, 14, 'Valle Hermoso', 'valle-hermoso'),
(1500, 14, 'Viamonte', 'viamonte'),
(1501, 14, 'Vicuña Mackenna', 'vicuna-mackenna'),
(1502, 14, 'Villa Allende', 'villa-allende'),
(1503, 14, 'Villa Alpina', 'villa-alpina'),
(1504, 14, 'Villa Amancay', 'villa-amancay'),
(1505, 14, 'Villa Ascasubi', 'villa-ascasubi'),
(1506, 14, 'Villa Berna', 'villa-berna'),
(1507, 14, 'Villa Candelaria', 'villa-candelaria'),
(1508, 14, 'Villa Carlos Paz', 'villa-carlos-paz'),
(1509, 14, 'Villa Cerro Azul', 'villa-cerro-azul'),
(1510, 14, 'Villa Ciudad de América', 'villa-ciudad-de-america'),
(1511, 14, 'Villa Ciudad Parque Los Reartes', 'villa-ciudad-parque-los-reartes'),
(1512, 14, 'Villa Concepción del Tío', 'villa-concepcion-del-tio'),
(1513, 14, 'Villa Corazón de María', 'villa-corazon-de-maria'),
(1514, 14, 'Villa Cura Brochero', 'villa-cura-brochero'),
(1515, 14, 'Villa de las Rosas', 'villa-de-las-rosas'),
(1516, 14, 'Villa de María', 'villa-de-maria'),
(1517, 14, 'Villa de Pocho', 'villa-de-pocho'),
(1518, 14, 'Villa de Soto', 'villa-de-soto'),
(1519, 14, 'Villa del Dique', 'villa-del-dique'),
(1520, 14, 'Villa del Prado', 'villa-del-prado'),
(1521, 14, 'Villa del Rosario', 'villa-del-rosario'),
(1522, 14, 'Villa del Totoral', 'villa-del-totoral'),
(1523, 14, 'Villa del Tránsito', 'villa-del-transito'),
(1524, 14, 'Villa Dolores', 'villa-dolores'),
(1525, 14, 'Villa El Chacay', 'villa-el-chacay'),
(1526, 14, 'Villa El Tala', 'villa-el-tala'),
(1527, 14, 'Villa Elisa', 'villa-elisa'),
(1528, 14, 'Villa Flor Serrana', 'villa-flor-serrana'),
(1529, 14, 'Villa Fontana', 'villa-fontana'),
(1530, 14, 'Villa General Belgrano', 'villa-general-belgrano'),
(1531, 14, 'Villa Giardino', 'villa-giardino'),
(1532, 14, 'Villa Gutiérrez', 'villa-gutierrez'),
(1533, 14, 'Villa Huidobro', 'villa-huidobro'),
(1534, 14, 'Villa La Bolsa', 'villa-la-bolsa'),
(1535, 14, 'Villa La Rivera', 'villa-la-rivera'),
(1536, 14, 'Villa La Viña', 'villa-la-vina'),
(1537, 14, 'Villa Lago Azul', 'villa-lago-azul'),
(1538, 14, 'Villa Los Aromos', 'villa-los-aromos'),
(1539, 14, 'Villa Los Llanos', 'villa-los-llanos'),
(1540, 14, 'Villa Los Llanos - Juárez Celman', 'villa-los-llanos-juarez-celman'),
(1541, 14, 'Villa Los Patos', 'villa-los-patos'),
(1542, 14, 'Villa María', 'villa-maria'),
(1543, 14, 'Villa Nueva', 'villa-nueva'),
(1544, 14, 'Villa Oeste', 'villa-oeste'),
(1545, 14, 'Villa Parque Santa Ana', 'villa-parque-santa-ana'),
(1546, 14, 'Villa Parque Siquimán', 'villa-parque-siquiman'),
(1547, 14, 'Villa Pastora', 'villa-pastora'),
(1548, 14, 'Villa Quillinzo', 'villa-quillinzo'),
(1549, 14, 'Villa Reducción', 'villa-reduccion'),
(1550, 14, 'Villa Río Icho Cruz', 'villa-rio-icho-cruz'),
(1551, 14, 'Villa Rossi', 'villa-rossi'),
(1552, 14, 'Villa Rumipal', 'villa-rumipal'),
(1553, 14, 'Villa San Esteban', 'villa-san-esteban'),
(1554, 14, 'Villa San Isidro', 'villa-san-isidro'),
(1555, 14, 'Villa San Miguel', 'villa-san-miguel'),
(1556, 14, 'Villa Santa Cruz del Lago', 'villa-santa-cruz-del-lago'),
(1557, 14, 'Villa Sarmiento (General Roca)', 'villa-sarmiento-general-roca'),
(1558, 14, 'Villa Sarmiento (San Alberto)', 'villa-sarmiento-san-alberto'),
(1559, 14, 'Villa Tulumba', 'villa-tulumba'),
(1560, 14, 'Villa Valeria', 'villa-valeria'),
(1561, 14, 'Villa Yacanto', 'villa-yacanto'),
(1562, 14, 'Washington', 'washington'),
(1563, 14, 'Wenceslao Escalante', 'wenceslao-escalante'),
(1564, 18, '9 de Julio', '9-de-julio'),
(1565, 18, 'Alvear', 'alvear'),
(1566, 18, 'Bella Vista', 'bella-vista'),
(1567, 18, 'Berón de Astrada', 'beron-de-astrada'),
(1568, 18, 'Bonpland', 'bonpland'),
(1569, 18, 'Carolina', 'carolina'),
(1570, 18, 'Cazadores Correntinos', 'cazadores-correntinos'),
(1571, 18, 'Chavarría', 'chavarria'),
(1572, 18, 'Colonia Carlos Pellegrini', 'colonia-carlos-pellegrini'),
(1573, 18, 'Colonia Libertad', 'colonia-libertad'),
(1574, 18, 'Colonia Liebig''s', 'colonia-liebig-s'),
(1575, 18, 'Colonia Pando', 'colonia-pando'),
(1576, 18, 'Concepción', 'concepcion'),
(1577, 18, 'Corrientes', 'corrientes'),
(1578, 18, 'Cruz de los Milagros', 'cruz-de-los-milagros'),
(1579, 18, 'Curuzú Cuatiá', 'curuzu-cuatia'),
(1580, 18, 'El Sombrero', 'el-sombrero'),
(1581, 18, 'Empedrado', 'empedrado'),
(1582, 18, 'Esquina', 'esquina'),
(1583, 18, 'Estación Libertad', 'estacion-libertad'),
(1584, 18, 'Estación Torrent', 'estacion-torrent'),
(1585, 18, 'Felipe Yofré', 'felipe-yofre'),
(1586, 18, 'Garruchos', 'garruchos'),
(1587, 18, 'Gobernador Igr. Valentín Virasoro', 'gobernador-igr-valentin-virasoro'),
(1588, 18, 'Gobernador Juan E. Martínez', 'gobernador-juan-e-martinez'),
(1589, 18, 'Goya', 'goya'),
(1590, 18, 'Guaviraví', 'guaviravi'),
(1591, 18, 'Ingenio Primer Correntino', 'ingenio-primer-correntino'),
(1592, 18, 'Itá Ibaté', 'ita-ibate'),
(1593, 18, 'Itatí', 'itati'),
(1594, 18, 'Ituzaingó', 'ituzaingo'),
(1595, 18, 'José Rafael Gómez', 'jose-rafael-gomez'),
(1596, 18, 'Juan Pujol', 'juan-pujol'),
(1597, 18, 'La Cruz', 'la-cruz'),
(1598, 18, 'Lavalle', 'lavalle'),
(1599, 18, 'Lomas de Vallejos', 'lomas-de-vallejos'),
(1600, 18, 'Loreto', 'loreto');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1601, 18, 'Mariano I. Loza', 'mariano-i-loza'),
(1602, 18, 'Mburucuyá', 'mburucuya'),
(1603, 18, 'Mercedes', 'mercedes'),
(1604, 18, 'Mocoretá', 'mocoreta'),
(1605, 18, 'Monte Caseros', 'monte-caseros'),
(1606, 18, 'Nuestra Señora del Rosario de Caá Catí', 'nuestra-senora-del-rosario-de-caa-cati'),
(1607, 18, 'Palmar Grande', 'palmar-grande'),
(1608, 18, 'Parada Acuña', 'parada-acuna'),
(1609, 18, 'Parada Labougle', 'parada-labougle'),
(1610, 18, 'Parada Pucheta', 'parada-pucheta'),
(1611, 18, 'Paso de la Patria', 'paso-de-la-patria'),
(1612, 18, 'Paso de los Libres', 'paso-de-los-libres'),
(1613, 18, 'Pedro R. Fernández', 'pedro-r-fernandez'),
(1614, 18, 'Perugorría', 'perugorria'),
(1615, 18, 'Pueblo Libertador', 'pueblo-libertador'),
(1616, 18, 'Ramada Paso', 'ramada-paso'),
(1617, 18, 'Riachuelo', 'riachuelo'),
(1618, 18, 'Saladas', 'saladas'),
(1619, 18, 'San Antonio', 'san-antonio'),
(1620, 18, 'San Carlos', 'san-carlos'),
(1621, 18, 'San Cayetano', 'san-cayetano'),
(1622, 18, 'San Cosme', 'san-cosme'),
(1623, 18, 'San Lorenzo', 'san-lorenzo'),
(1624, 18, 'San Luis del Palmar', 'san-luis-del-palmar'),
(1625, 18, 'San Miguel', 'san-miguel'),
(1626, 18, 'San Roque', 'san-roque'),
(1627, 18, 'Santa Ana', 'santa-ana'),
(1628, 18, 'Santa Lucía', 'santa-lucia'),
(1629, 18, 'Santa Rosa', 'santa-rosa'),
(1630, 18, 'Santo Tomé', 'santo-tome'),
(1631, 18, 'Sauce', 'sauce'),
(1632, 18, 'Tabay', 'tabay'),
(1633, 18, 'Tapebicuá', 'tapebicua'),
(1634, 18, 'Tatacua', 'tatacua'),
(1635, 18, 'Villa Córdoba', 'villa-cordoba'),
(1636, 18, 'Villa Olivari', 'villa-olivari'),
(1637, 18, 'Yahapé', 'yahape'),
(1638, 18, 'Yapeyú', 'yapeyu'),
(1639, 18, 'Yatayti Calle', 'yatayti-calle'),
(1640, 22, 'Avia Terai', 'avia-terai'),
(1641, 22, 'Barranqueras', 'barranqueras'),
(1642, 22, 'Barrio de los Pescadores', 'barrio-de-los-pescadores'),
(1643, 22, 'Basail', 'basail'),
(1644, 22, 'Campo Largo', 'campo-largo'),
(1645, 22, 'Capitán Solari', 'capitan-solari'),
(1646, 22, 'Charadai', 'charadai'),
(1647, 22, 'Charata', 'charata'),
(1648, 22, 'Chorotis', 'chorotis'),
(1649, 22, 'Ciervo Petiso', 'ciervo-petiso'),
(1650, 22, 'Colonia Aborigen', 'colonia-aborigen'),
(1651, 22, 'Colonia Baranda', 'colonia-baranda'),
(1652, 22, 'Colonia Benítez', 'colonia-benitez'),
(1653, 22, 'Colonia Elisa', 'colonia-elisa'),
(1654, 22, 'Colonia Popular', 'colonia-popular'),
(1655, 22, 'Colonias Unidas', 'colonias-unidas'),
(1656, 22, 'Concepción del Bermejo', 'concepcion-del-bermejo'),
(1657, 22, 'Coronel Du Graty', 'coronel-du-graty'),
(1658, 22, 'Corzuela', 'corzuela'),
(1659, 22, 'Cote Lai', 'cote-lai'),
(1660, 22, 'El Espinillo', 'el-espinillo'),
(1661, 22, 'El Sauzal', 'el-sauzal'),
(1662, 22, 'El Sauzalito', 'el-sauzalito'),
(1663, 22, 'Enrique Urien', 'enrique-urien'),
(1664, 22, 'Estación General Obligado', 'estacion-general-obligado'),
(1665, 22, 'Fontana', 'fontana'),
(1666, 22, 'Fortín Las Chuñas', 'fortin-las-chunas'),
(1667, 22, 'Fortín Lavalle', 'fortin-lavalle'),
(1668, 22, 'Fuerte Esperanza', 'fuerte-esperanza'),
(1669, 22, 'Gancedo', 'gancedo'),
(1670, 22, 'General Capdevila', 'general-capdevila'),
(1671, 22, 'General José de San Martín', 'general-jose-de-san-martin'),
(1672, 22, 'General Pinedo', 'general-pinedo'),
(1673, 22, 'General Vedia', 'general-vedia'),
(1674, 22, 'Haumonia', 'haumonia'),
(1675, 22, 'Hermoso Campo', 'hermoso-campo'),
(1676, 22, 'Horquilla', 'horquilla'),
(1677, 22, 'Ingeniero Barbet', 'ingeniero-barbet'),
(1678, 22, 'Isla del Cerrito', 'isla-del-cerrito'),
(1679, 22, 'Itín', 'itin'),
(1680, 22, 'Juan José Castelli', 'juan-jose-castelli'),
(1681, 22, 'La Clotilde', 'la-clotilde'),
(1682, 22, 'La Eduvigis', 'la-eduvigis'),
(1683, 22, 'La Escondida', 'la-escondida'),
(1684, 22, 'La Leonesa', 'la-leonesa'),
(1685, 22, 'La Sabana', 'la-sabana'),
(1686, 22, 'La Tigra', 'la-tigra'),
(1687, 22, 'La Verde', 'la-verde'),
(1688, 22, 'Laguna Blanca', 'laguna-blanca'),
(1689, 22, 'Laguna Limpia', 'laguna-limpia'),
(1690, 22, 'Lapachito', 'lapachito'),
(1691, 22, 'Las Breñas', 'las-brenas'),
(1692, 22, 'Las Garcitas', 'las-garcitas'),
(1693, 22, 'Las Palmas', 'las-palmas'),
(1694, 22, 'Los Frentones', 'los-frentones'),
(1695, 22, 'Machagai', 'machagai'),
(1696, 22, 'Makallé', 'makalle'),
(1697, 22, 'Margarita Belén', 'margarita-belen'),
(1698, 22, 'Mesón de Fierro', 'meson-de-fierro'),
(1699, 22, 'Miraflores', 'miraflores'),
(1700, 22, 'Napalpí', 'napalpi'),
(1701, 22, 'Napenay', 'napenay'),
(1702, 22, 'Nueva Pompeya', 'nueva-pompeya'),
(1703, 22, 'Pampa Almirón', 'pampa-almiron'),
(1704, 22, 'Pampa del Indio', 'pampa-del-indio'),
(1705, 22, 'Pampa del Infierno', 'pampa-del-infierno'),
(1706, 22, 'Pampa Landriel', 'pampa-landriel'),
(1707, 22, 'Presidencia de la Plaza', 'presidencia-de-la-plaza'),
(1708, 22, 'Presidencia Roca', 'presidencia-roca'),
(1709, 22, 'Presidencia Roque Sáenz Peña', 'presidencia-roque-saenz-pena'),
(1710, 22, 'Puerto Bermejo Nuevo', 'puerto-bermejo-nuevo'),
(1711, 22, 'Puerto Bermejo Viejo', 'puerto-bermejo-viejo'),
(1712, 22, 'Puerto Eva Perón', 'puerto-eva-peron'),
(1713, 22, 'Puerto Tirol', 'puerto-tirol'),
(1714, 22, 'Puerto Vilelas', 'puerto-vilelas'),
(1715, 22, 'Quitilipi', 'quitilipi'),
(1716, 22, 'Resistencia', 'resistencia'),
(1717, 22, 'Río Muerto', 'rio-muerto'),
(1718, 22, 'Samuhú', 'samuhu'),
(1719, 22, 'San Bernardo', 'san-bernardo'),
(1720, 22, 'Santa Sylvina', 'santa-sylvina'),
(1721, 22, 'Selvas del Río de Oro', 'selvas-del-rio-de-oro'),
(1722, 22, 'Taco Pozo', 'taco-pozo'),
(1723, 22, 'Tres Isletas', 'tres-isletas'),
(1724, 22, 'Venados Grandes', 'venados-grandes'),
(1725, 22, 'Villa Angela', 'villa-angela'),
(1726, 22, 'Villa Berthet', 'villa-berthet'),
(1727, 22, 'Villa El Palmar', 'villa-el-palmar'),
(1728, 22, 'Villa Río Bermejito', 'villa-rio-bermejito'),
(1729, 22, 'Wichi', 'wichi'),
(1730, 22, 'Zaparinqui', 'zaparinqui'),
(1731, 26, '28 de Julio', '28-de-julio'),
(1732, 26, 'Acceso Norte', 'acceso-norte'),
(1733, 26, 'Aldea Apeleg', 'aldea-apeleg'),
(1734, 26, 'Aldea Beleiro', 'aldea-beleiro'),
(1735, 26, 'Aldea Epulef', 'aldea-epulef'),
(1736, 26, 'Aldea Escolar (Los Rápidos)', 'aldea-escolar-los-rapidos'),
(1737, 26, 'Alto Río Senguer', 'alto-rio-senguer'),
(1738, 26, 'Arroyo Verde', 'arroyo-verde'),
(1739, 26, 'Astra', 'astra'),
(1740, 26, 'Bahía Bustamante', 'bahia-bustamante'),
(1741, 26, 'Barrio 25 de Mayo', 'barrio-25-de-mayo'),
(1742, 26, 'Barrio Caleta Córdova', 'barrio-caleta-cordova'),
(1743, 26, 'Barrio Castelli', 'barrio-castelli'),
(1744, 26, 'Barrio Ciudadela', 'barrio-ciudadela'),
(1745, 26, 'Barrio Gasoducto', 'barrio-gasoducto'),
(1746, 26, 'Barrio Güemes', 'barrio-guemes'),
(1747, 26, 'Barrio Laprida', 'barrio-laprida'),
(1748, 26, 'Barrio Manantial Rosales', 'barrio-manantial-rosales'),
(1749, 26, 'Barrio Militar y Aeropuerto', 'barrio-militar-y-aeropuerto'),
(1750, 26, 'Barrio Próspero Palazzo', 'barrio-prospero-palazzo'),
(1751, 26, 'Barrio Restinga Alí', 'barrio-restinga-ali'),
(1752, 26, 'Barrio Rodríguez Peña', 'barrio-rodriguez-pena'),
(1753, 26, 'Barrio Saavedra', 'barrio-saavedra'),
(1754, 26, 'Barrio Sarmiento', 'barrio-sarmiento'),
(1755, 26, 'Barrio Villa S.U.P.E.', 'barrio-villa-s-u-p-e'),
(1756, 26, 'Blancuntre', 'blancuntre'),
(1757, 26, 'Buen Pasto', 'buen-pasto'),
(1758, 26, 'Buenos Aires Chico', 'buenos-aires-chico'),
(1759, 26, 'Caleta Olivares', 'caleta-olivares'),
(1760, 26, 'Camarones', 'camarones'),
(1761, 26, 'Carrenleufú', 'carrenleufu'),
(1762, 26, 'Cerro Cóndor', 'cerro-condor'),
(1763, 26, 'Cholila', 'cholila'),
(1764, 26, 'Colan Conhué', 'colan-conhue'),
(1765, 26, 'Comodoro Rivadavia', 'comodoro-rivadavia'),
(1766, 26, 'Corcovado', 'corcovado'),
(1767, 26, 'Costa del Chubut', 'costa-del-chubut'),
(1768, 26, 'Cushamen Centro', 'cushamen-centro'),
(1769, 26, 'Diadema Argentina', 'diadema-argentina'),
(1770, 26, 'Dique Florentino Ameghino', 'dique-florentino-ameghino'),
(1771, 26, 'Doctor Oscar Atilio Viglione (Frontera de Río Pico)', 'doctor-oscar-atilio-viglione-fronter-26098010'),
(1772, 26, 'Doctor Ricardo Rojas', 'doctor-ricardo-rojas'),
(1773, 26, 'Dolavon', 'dolavon'),
(1774, 26, 'El Escorial', 'el-escorial'),
(1775, 26, 'El Hoyo', 'el-hoyo'),
(1776, 26, 'El Maitén', 'el-maiten'),
(1777, 26, 'El Mirasol', 'el-mirasol'),
(1778, 26, 'Epuyén', 'epuyen'),
(1779, 26, 'Esquel', 'esquel'),
(1780, 26, 'Facundo', 'facundo'),
(1781, 26, 'Fofo Cahuel', 'fofo-cahuel'),
(1782, 26, 'Gaiman', 'gaiman'),
(1783, 26, 'Gan Gan', 'gan-gan'),
(1784, 26, 'Garayalde', 'garayalde'),
(1785, 26, 'Gastre', 'gastre'),
(1786, 26, 'Gobernador Costa', 'gobernador-costa'),
(1787, 26, 'Gualjaina', 'gualjaina'),
(1788, 26, 'José de San Martín', 'jose-de-san-martin'),
(1789, 26, 'Kilómetro 11 - Cuarteles', 'kilometro-11-cuarteles'),
(1790, 26, 'Kilómetro 3 - General Mosconi', 'kilometro-3-general-mosconi'),
(1791, 26, 'Kilómetro 5 - Presidente Ortíz', 'kilometro-5-presidente-ortiz'),
(1792, 26, 'Kilómetro 8 - Don Bosco', 'kilometro-8-don-bosco'),
(1793, 26, 'Lago Blanco', 'lago-blanco'),
(1794, 26, 'Lago Epuyén', 'lago-epuyen'),
(1795, 26, 'Lago Puelo', 'lago-puelo'),
(1796, 26, 'Lago Rosario', 'lago-rosario'),
(1797, 26, 'Lagunita Salada', 'lagunita-salada'),
(1798, 26, 'Las Plumas', 'las-plumas'),
(1799, 26, 'Leleque', 'leleque'),
(1800, 26, 'Los Altares', 'los-altares');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(1801, 26, 'Los Cipreses', 'los-cipreses'),
(1802, 26, 'Paso de Indios', 'paso-de-indios'),
(1803, 26, 'Paso del Sapo', 'paso-del-sapo'),
(1804, 26, 'Playa Magagna', 'playa-magagna'),
(1805, 26, 'Playa Unión', 'playa-union'),
(1806, 26, 'Puerto Madryn', 'puerto-madryn'),
(1807, 26, 'Puerto Pirámides', 'puerto-piramides'),
(1808, 26, 'Quintas El Mirador', 'quintas-el-mirador'),
(1809, 26, 'Rada Tilly', 'rada-tilly'),
(1810, 26, 'Rawson', 'rawson'),
(1811, 26, 'Reserva Area Protegida El Doradillo', 'reserva-area-protegida-el-doradillo'),
(1812, 26, 'Río Mayo', 'rio-mayo'),
(1813, 26, 'Río Pico', 'rio-pico'),
(1814, 26, 'Sarmiento', 'sarmiento'),
(1815, 26, 'Tecka', 'tecka'),
(1816, 26, 'Telsen', 'telsen'),
(1817, 26, 'Trelew', 'trelew'),
(1818, 26, 'Trevelín', 'trevelin'),
(1819, 26, 'Villa Futalaufquen', 'villa-futalaufquen'),
(1820, 26, 'Yala Laubat', 'yala-laubat'),
(1821, 30, '1º de Mayo', '1o-de-mayo'),
(1822, 30, 'Alcaraz', 'alcaraz'),
(1823, 30, 'Aldea Asunción', 'aldea-asuncion'),
(1824, 30, 'Aldea Brasilera', 'aldea-brasilera'),
(1825, 30, 'Aldea Grapschental', 'aldea-grapschental'),
(1826, 30, 'Aldea María Luisa', 'aldea-maria-luisa'),
(1827, 30, 'Aldea Protestante', 'aldea-protestante'),
(1828, 30, 'Aldea Salto', 'aldea-salto'),
(1829, 30, 'Aldea San Antonio', 'aldea-san-antonio'),
(1830, 30, 'Aldea San Francisco', 'aldea-san-francisco'),
(1831, 30, 'Aldea San Isidro (El Cimarrón)', 'aldea-san-isidro-el-cimarron'),
(1832, 30, 'Aldea San Juan (Gualeguaychú)', 'aldea-san-juan-gualeguaychu'),
(1833, 30, 'Aldea San Juan (Paraná)', 'aldea-san-juan-parana'),
(1834, 30, 'Aldea San Rafael', 'aldea-san-rafael'),
(1835, 30, 'Aldea Santa María', 'aldea-santa-maria'),
(1836, 30, 'Aldea Santa Rosa', 'aldea-santa-rosa'),
(1837, 30, 'Aldea Spatzenkutter', 'aldea-spatzenkutter'),
(1838, 30, 'Aldea Valle María', 'aldea-valle-maria'),
(1839, 30, 'Altamirano Sur', 'altamirano-sur'),
(1840, 30, 'Antelo', 'antelo'),
(1841, 30, 'Aranguren', 'aranguren'),
(1842, 30, 'Arroyo Barú', 'arroyo-baru'),
(1843, 30, 'Basavilbaso', 'basavilbaso'),
(1844, 30, 'Benito Legerén', 'benito-legeren'),
(1845, 30, 'Betbeder', 'betbeder'),
(1846, 30, 'Bovril', 'bovril'),
(1847, 30, 'Calabacilla', 'calabacilla'),
(1848, 30, 'Caseros', 'caseros'),
(1849, 30, 'Ceibas', 'ceibas'),
(1850, 30, 'Cerrito', 'cerrito'),
(1851, 30, 'Chajarí', 'chajari'),
(1852, 30, 'Clodomiro Ledesma', 'clodomiro-ledesma'),
(1853, 30, 'Colón', 'colon'),
(1854, 30, 'Colonia Alemana', 'colonia-alemana'),
(1855, 30, 'Colonia Avellaneda', 'colonia-avellaneda'),
(1856, 30, 'Colonia Avigdor', 'colonia-avigdor'),
(1857, 30, 'Colonia Ayuí', 'colonia-ayui'),
(1858, 30, 'Colonia Crespo', 'colonia-crespo'),
(1859, 30, 'Colonia Elía', 'colonia-elia'),
(1860, 30, 'Colonia Ensayo', 'colonia-ensayo'),
(1861, 30, 'Colonia General Roca', 'colonia-general-roca'),
(1862, 30, 'Colonia Hugues', 'colonia-hugues'),
(1863, 30, 'Colonia La Argentina', 'colonia-la-argentina'),
(1864, 30, 'Concepción del Uruguay', 'concepcion-del-uruguay'),
(1865, 30, 'Concordia', 'concordia'),
(1866, 30, 'Conscripto Bernardi', 'conscripto-bernardi'),
(1867, 30, 'Crespo', 'crespo'),
(1868, 30, 'Diamante', 'diamante'),
(1869, 30, 'Don Cristóbal', 'don-cristobal'),
(1870, 30, 'Durazno', 'durazno'),
(1871, 30, 'El Brillante', 'el-brillante'),
(1872, 30, 'El Colorado', 'el-colorado'),
(1873, 30, 'El Palenque', 'el-palenque'),
(1874, 30, 'El Pingo', 'el-pingo'),
(1875, 30, 'El Ramblón', 'el-ramblon'),
(1876, 30, 'El Solar', 'el-solar'),
(1877, 30, 'Enrique Carbó', 'enrique-carbo'),
(1878, 30, 'Estación Arroyo Clé', 'estacion-arroyo-cle'),
(1879, 30, 'Estación Camps', 'estacion-camps'),
(1880, 30, 'Estación Escriña', 'estacion-escrina'),
(1881, 30, 'Estación Lazo', 'estacion-lazo'),
(1882, 30, 'Estación Puiggari', 'estacion-puiggari'),
(1883, 30, 'Estación Raíces', 'estacion-raices'),
(1884, 30, 'Estación Yeruá', 'estacion-yerua'),
(1885, 30, 'Estación Yuquerí', 'estacion-yuqueri'),
(1886, 30, 'Estancia Grande', 'estancia-grande'),
(1887, 30, 'Faustino M. Parera', 'faustino-m-parera'),
(1888, 30, 'Febré', 'febre'),
(1889, 30, 'Federación', 'federacion'),
(1890, 30, 'Federal', 'federal'),
(1891, 30, 'General Almada', 'general-almada'),
(1892, 30, 'General Alvear', 'general-alvear'),
(1893, 30, 'General Campos', 'general-campos'),
(1894, 30, 'General Galarza', 'general-galarza'),
(1895, 30, 'General Racedo (El Carmen)', 'general-racedo-el-carmen'),
(1896, 30, 'General Ramírez', 'general-ramirez'),
(1897, 30, 'Gilbert', 'gilbert'),
(1898, 30, 'Gobernador Echagüe', 'gobernador-echague'),
(1899, 30, 'Gobernador Mansilla', 'gobernador-mansilla'),
(1900, 30, 'Gobernador Solá', 'gobernador-sola'),
(1901, 30, 'Gualeguay', 'gualeguay'),
(1902, 30, 'Gualeguaychú', 'gualeguaychu'),
(1903, 30, 'Guardamonte', 'guardamonte'),
(1904, 30, 'Hambis', 'hambis'),
(1905, 30, 'Hasenkamp', 'hasenkamp'),
(1906, 30, 'Hernandarias', 'hernandarias'),
(1907, 30, 'Hernández', 'hernandez'),
(1908, 30, 'Herrera', 'herrera'),
(1909, 30, 'Hocker', 'hocker'),
(1910, 30, 'Ibicuy', 'ibicuy'),
(1911, 30, 'Ingeniero Miguel Sajaroff', 'ingeniero-miguel-sajaroff'),
(1912, 30, 'Irazusta', 'irazusta'),
(1913, 30, 'Jubileo', 'jubileo'),
(1914, 30, 'La Clarita', 'la-clarita'),
(1915, 30, 'La Criolla', 'la-criolla'),
(1916, 30, 'La Juanita', 'la-juanita'),
(1917, 30, 'La Paz', 'la-paz'),
(1918, 30, 'La Picada', 'la-picada'),
(1919, 30, 'Larroque', 'larroque'),
(1920, 30, 'Las Guachas', 'las-guachas'),
(1921, 30, 'Las Jaulas', 'las-jaulas'),
(1922, 30, 'Las Moscas', 'las-moscas'),
(1923, 30, 'Las Tejas', 'las-tejas'),
(1924, 30, 'Las Tunas', 'las-tunas'),
(1925, 30, 'Líbaros', 'libaros'),
(1926, 30, 'Los Charrúas', 'los-charruas'),
(1927, 30, 'Los Conquistadores', 'los-conquistadores'),
(1928, 30, 'Lucas González', 'lucas-gonzalez'),
(1929, 30, 'Maciá', 'macia'),
(1930, 30, 'María Grande', 'maria-grande'),
(1931, 30, 'Médanos', 'medanos'),
(1932, 30, 'Molino Doll', 'molino-doll'),
(1933, 30, 'Nogoyá', 'nogoya'),
(1934, 30, 'Nueva Escocia', 'nueva-escocia'),
(1935, 30, 'Nueva Vizcaya', 'nueva-vizcaya'),
(1936, 30, 'Oro Verde', 'oro-verde'),
(1937, 30, 'Osvaldo Magnasco', 'osvaldo-magnasco'),
(1938, 30, 'Paraje La Virgen', 'paraje-la-virgen'),
(1939, 30, 'Paraná', 'parana'),
(1940, 30, 'Paso de la Laguna', 'paso-de-la-laguna'),
(1941, 30, 'Pastor Britos', 'pastor-britos'),
(1942, 30, 'Pedernal', 'pedernal'),
(1943, 30, 'Piedras Blancas', 'piedras-blancas'),
(1944, 30, 'Pronunciamiento', 'pronunciamiento'),
(1945, 30, 'Pueblo Bellocq (Las Garzas)', 'pueblo-bellocq-las-garzas'),
(1946, 30, 'Pueblo Brugo', 'pueblo-brugo'),
(1947, 30, 'Pueblo Cazes', 'pueblo-cazes'),
(1948, 30, 'Pueblo General Belgrano', 'pueblo-general-belgrano'),
(1949, 30, 'Pueblo General San Martín', 'pueblo-general-san-martin'),
(1950, 30, 'Pueblo Liebig''s', 'pueblo-liebig-s'),
(1951, 30, 'Pueblo Moreno', 'pueblo-moreno'),
(1952, 30, 'Puerto Las Cuevas', 'puerto-las-cuevas'),
(1953, 30, 'Puerto Ruiz', 'puerto-ruiz'),
(1954, 30, 'Puerto Yeruá', 'puerto-yerua'),
(1955, 30, 'Rocamora', 'rocamora'),
(1956, 30, 'Rosario del Tala', 'rosario-del-tala'),
(1957, 30, 'San Benito', 'san-benito'),
(1958, 30, 'San Gustavo', 'san-gustavo'),
(1959, 30, 'San Jaime de la Frontera', 'san-jaime-de-la-frontera'),
(1960, 30, 'San José', 'san-jose'),
(1961, 30, 'San José de Feliciano', 'san-jose-de-feliciano'),
(1962, 30, 'San Pedro', 'san-pedro'),
(1963, 30, 'San Ramón', 'san-ramon'),
(1964, 30, 'San Salvador', 'san-salvador'),
(1965, 30, 'San Víctor', 'san-victor'),
(1966, 30, 'Santa Ana', 'santa-ana'),
(1967, 30, 'Santa Anita', 'santa-anita'),
(1968, 30, 'Santa Elena', 'santa-elena'),
(1969, 30, 'Sauce de Luna', 'sauce-de-luna'),
(1970, 30, 'Sauce Montrull', 'sauce-montrull'),
(1971, 30, 'Sauce Pinto', 'sauce-pinto'),
(1972, 30, 'Seguí', 'segui'),
(1973, 30, 'Sir Leonard', 'sir-leonard'),
(1974, 30, 'Sosa', 'sosa'),
(1975, 30, 'Strobel', 'strobel'),
(1976, 30, 'Tabossi', 'tabossi'),
(1977, 30, 'Tezanos Pinto', 'tezanos-pinto'),
(1978, 30, 'Ubajay', 'ubajay'),
(1979, 30, 'Urdinarrain', 'urdinarrain'),
(1980, 30, 'Viale', 'viale'),
(1981, 30, 'Victoria', 'victoria'),
(1982, 30, 'Villa Adela', 'villa-adela'),
(1983, 30, 'Villa Clara', 'villa-clara'),
(1984, 30, 'Villa del Rosario', 'villa-del-rosario'),
(1985, 30, 'Villa Domínguez', 'villa-dominguez'),
(1986, 30, 'Villa Elisa', 'villa-elisa'),
(1987, 30, 'Villa Fontana', 'villa-fontana'),
(1988, 30, 'Villa Gdor. Luis F. Etchevehere', 'villa-gdor-luis-f-etchevehere'),
(1989, 30, 'Villa Libertador San Martín', 'villa-libertador-san-martin'),
(1990, 30, 'Villa Mantero', 'villa-mantero'),
(1991, 30, 'Villa Paranacito', 'villa-paranacito'),
(1992, 30, 'Villa San Justo', 'villa-san-justo'),
(1993, 30, 'Villa San Marcial (Est. Gobernador Urquiza)', 'villa-san-marcial-est-gobernador-urquiza'),
(1994, 30, 'Villa Urquiza', 'villa-urquiza'),
(1995, 30, 'Villa Zorraquín', 'villa-zorraquin'),
(1996, 30, 'Villaguay', 'villaguay'),
(1997, 30, 'XX de Setiembre', 'xx-de-setiembre'),
(1998, 34, 'Banco Payaguá', 'banco-payagua'),
(1999, 34, 'Bartolomé de las Casas', 'bartolome-de-las-casas'),
(2000, 34, 'Buena Vista', 'buena-vista');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(2001, 34, 'Clorinda', 'clorinda'),
(2002, 34, 'Colonia Campo Villafañe', 'colonia-campo-villafane'),
(2003, 34, 'Colonia Pastoril', 'colonia-pastoril'),
(2004, 34, 'Colonia Sarmiento', 'colonia-sarmiento'),
(2005, 34, 'Comandante Fontana', 'comandante-fontana'),
(2006, 34, 'Comunidad Aborigen Bartolomé de las Casas', 'comunidad-aborigen-bartolome-de-las-casas'),
(2007, 34, 'El Colorado', 'el-colorado'),
(2008, 34, 'El Espinillo', 'el-espinillo'),
(2009, 34, 'El Potrillo', 'el-potrillo'),
(2010, 34, 'El Quebracho', 'el-quebracho'),
(2011, 34, 'El Recreo', 'el-recreo'),
(2012, 34, 'Estanislao del Campo', 'estanislao-del-campo'),
(2013, 34, 'Formosa', 'formosa'),
(2014, 34, 'Fortín Cabo 1º Lugones', 'fortin-cabo-1o-lugones'),
(2015, 34, 'Fortín Sargento 1º Leyes', 'fortin-sargento-1o-leyes'),
(2016, 34, 'Fortín Soledad', 'fortin-soledad'),
(2017, 34, 'General Lucio V. Mansilla', 'general-lucio-v-mansilla'),
(2018, 34, 'General Mosconi', 'general-mosconi'),
(2019, 34, 'Gran Guardia', 'gran-guardia'),
(2020, 34, 'Guadalcazar', 'guadalcazar'),
(2021, 34, 'Herradura', 'herradura'),
(2022, 34, 'Ibarreta', 'ibarreta'),
(2023, 34, 'Ingeniero Guillermo N. Juárez', 'ingeniero-guillermo-n-juarez'),
(2024, 34, 'Juan G. Bazán', 'juan-g-bazan'),
(2025, 34, 'La Rinconada', 'la-rinconada'),
(2026, 34, 'Laguna Blanca', 'laguna-blanca'),
(2027, 34, 'Laguna Gallo', 'laguna-gallo'),
(2028, 34, 'Laguna Naick-Neck', 'laguna-naick-neck'),
(2029, 34, 'Laguna Yema', 'laguna-yema'),
(2030, 34, 'Lamadrid', 'lamadrid'),
(2031, 34, 'Las Lomitas', 'las-lomitas'),
(2032, 34, 'Los Chiriguanos', 'los-chiriguanos'),
(2033, 34, 'Mariano Boedo', 'mariano-boedo'),
(2034, 34, 'Misión Tacaaglé', 'mision-tacaagle'),
(2035, 34, 'Mojón de Fierro', 'mojon-de-fierro'),
(2036, 34, 'Palma Sola', 'palma-sola'),
(2037, 34, 'Palo Santo', 'palo-santo'),
(2038, 34, 'Pirané', 'pirane'),
(2039, 34, 'Portón Negro', 'porton-negro'),
(2040, 34, 'Posta Cambio Zalazar', 'posta-cambio-zalazar'),
(2041, 34, 'Pozo de Maza', 'pozo-de-maza'),
(2042, 34, 'Pozo del Mortero', 'pozo-del-mortero'),
(2043, 34, 'Pozo del Tigre', 'pozo-del-tigre'),
(2044, 34, 'Puerto Pilcomayo', 'puerto-pilcomayo'),
(2045, 34, 'Riacho He-He', 'riacho-he-he'),
(2046, 34, 'Riacho Negro', 'riacho-negro'),
(2047, 34, 'San Francisco de Laishi', 'san-francisco-de-laishi'),
(2048, 34, 'San Hilario', 'san-hilario'),
(2049, 34, 'San Martín I', 'san-martin-i'),
(2050, 34, 'San Martín II', 'san-martin-ii'),
(2051, 34, 'Siete Palmas', 'siete-palmas'),
(2052, 34, 'Subteniente Perín', 'subteniente-perin'),
(2053, 34, 'Tatané', 'tatane'),
(2054, 34, 'Tres Lagunas', 'tres-lagunas'),
(2055, 34, 'Vaca Perdida', 'vaca-perdida'),
(2056, 34, 'Villa Escolar', 'villa-escolar'),
(2057, 34, 'Villa General Güemes', 'villa-general-guemes'),
(2058, 34, 'Villa General Manuel Belgrano', 'villa-general-manuel-belgrano'),
(2059, 34, 'Villa Kilómetro 213', 'villa-kilometro-213'),
(2060, 38, 'Abdón Castro Tolay', 'abdon-castro-tolay'),
(2061, 38, 'Abra Pampa', 'abra-pampa'),
(2062, 38, 'Abralaite', 'abralaite'),
(2063, 38, 'Agua de Castilla', 'agua-de-castilla'),
(2064, 38, 'Aguas Calientes', 'aguas-calientes'),
(2065, 38, 'Aparzo', 'aparzo'),
(2066, 38, 'Arrayanal', 'arrayanal'),
(2067, 38, 'Arroyo Colorado', 'arroyo-colorado'),
(2068, 38, 'Bananal', 'bananal'),
(2069, 38, 'Bárcena', 'barcena'),
(2070, 38, 'Barrio El Milagro', 'barrio-el-milagro'),
(2071, 38, 'Barrio La Unión', 'barrio-la-union'),
(2072, 38, 'Barrios', 'barrios'),
(2073, 38, 'Bermejito', 'bermejito'),
(2074, 38, 'Caimancito', 'caimancito'),
(2075, 38, 'Calilegua', 'calilegua'),
(2076, 38, 'Cangrejillos', 'cangrejillos'),
(2077, 38, 'Carahunco', 'carahunco'),
(2078, 38, 'Casa Colorada', 'casa-colorada'),
(2079, 38, 'Casabindo', 'casabindo'),
(2080, 38, 'Casira', 'casira'),
(2081, 38, 'Caspalá', 'caspala'),
(2082, 38, 'Catua', 'catua'),
(2083, 38, 'Centro Forestal', 'centro-forestal'),
(2084, 38, 'Chalicán', 'chalican'),
(2085, 38, 'Cianzo', 'cianzo'),
(2086, 38, 'Ciénega de Paicone', 'cienega-de-paicone'),
(2087, 38, 'Cieneguillas', 'cieneguillas'),
(2088, 38, 'Cochinoca', 'cochinoca'),
(2089, 38, 'Coctaca', 'coctaca'),
(2090, 38, 'Colonia San José', 'colonia-san-jose'),
(2091, 38, 'Coranzuli', 'coranzuli'),
(2092, 38, 'Coyaguaima', 'coyaguaima'),
(2093, 38, 'Cusi Cusi', 'cusi-cusi'),
(2094, 38, 'Don Emilio', 'don-emilio'),
(2095, 38, 'El Acheral', 'el-acheral'),
(2096, 38, 'El Aguilar', 'el-aguilar'),
(2097, 38, 'El Angosto', 'el-angosto'),
(2098, 38, 'El Carmen', 'el-carmen'),
(2099, 38, 'El Ceibal', 'el-ceibal'),
(2100, 38, 'El Cóndor', 'el-condor'),
(2101, 38, 'El Fuerte', 'el-fuerte'),
(2102, 38, 'El Moreno', 'el-moreno'),
(2103, 38, 'El Piquete', 'el-piquete'),
(2104, 38, 'El Puesto', 'el-puesto'),
(2105, 38, 'El Quemado', 'el-quemado'),
(2106, 38, 'El Talar', 'el-talar'),
(2107, 38, 'El Toro', 'el-toro'),
(2108, 38, 'Fleming', 'fleming'),
(2109, 38, 'Fraile Pintado', 'fraile-pintado'),
(2110, 38, 'Guerrero', 'guerrero'),
(2111, 38, 'Hipólito Yrigoyen', 'hipolito-yrigoyen'),
(2112, 38, 'Huacalera', 'huacalera'),
(2113, 38, 'Huáncar', 'huancar'),
(2114, 38, 'Humahuaca', 'humahuaca'),
(2115, 38, 'Jama', 'jama'),
(2116, 38, 'Juella', 'juella'),
(2117, 38, 'La Almona', 'la-almona'),
(2118, 38, 'La Ciénega', 'la-cienega'),
(2119, 38, 'La Esperanza', 'la-esperanza'),
(2120, 38, 'La Intermedia', 'la-intermedia'),
(2121, 38, 'La Manga', 'la-manga'),
(2122, 38, 'La Mendieta', 'la-mendieta'),
(2123, 38, 'La Quiaca', 'la-quiaca'),
(2124, 38, 'La Redonda', 'la-redonda'),
(2125, 38, 'Lagunillas de Farallón', 'lagunillas-de-farallon'),
(2126, 38, 'León', 'leon'),
(2127, 38, 'Libertad', 'libertad'),
(2128, 38, 'Libertador General San Martín', 'libertador-general-san-martin'),
(2129, 38, 'Liviara', 'liviara'),
(2130, 38, 'Llulluchayoc', 'llulluchayoc'),
(2131, 38, 'Loma Blanca', 'loma-blanca'),
(2132, 38, 'Los Alisos', 'los-alisos'),
(2133, 38, 'Los Lapachos', 'los-lapachos'),
(2134, 38, 'Los Nogales', 'los-nogales'),
(2135, 38, 'Loteo Navea', 'loteo-navea'),
(2136, 38, 'Lozano', 'lozano'),
(2137, 38, 'Maimará', 'maimara'),
(2138, 38, 'Maíz Negro', 'maiz-negro'),
(2139, 38, 'Manantiales', 'manantiales'),
(2140, 38, 'Mina Providencia', 'mina-providencia'),
(2141, 38, 'Misarrumi', 'misarrumi'),
(2142, 38, 'Monterrico', 'monterrico'),
(2143, 38, 'Nuestra Señora del Rosario', 'nuestra-senora-del-rosario'),
(2144, 38, 'Nuevo Pirquitas', 'nuevo-pirquitas'),
(2145, 38, 'Ocloyas', 'ocloyas'),
(2146, 38, 'Olacapato', 'olacapato'),
(2147, 38, 'Olaroz Chico', 'olaroz-chico'),
(2148, 38, 'Oratorio', 'oratorio'),
(2149, 38, 'Orosmayo', 'orosmayo'),
(2150, 38, 'Paicone', 'paicone'),
(2151, 38, 'Palca de Aparzo', 'palca-de-aparzo'),
(2152, 38, 'Palca de Varas', 'palca-de-varas'),
(2153, 38, 'Palma Sola', 'palma-sola'),
(2154, 38, 'Palos Blancos', 'palos-blancos'),
(2155, 38, 'Palpalá', 'palpala'),
(2156, 38, 'Pampa Blanca', 'pampa-blanca'),
(2157, 38, 'Pampichuela', 'pampichuela'),
(2158, 38, 'Pastos Chicos', 'pastos-chicos'),
(2159, 38, 'Paulina', 'paulina'),
(2160, 38, 'Perico', 'perico'),
(2161, 38, 'Piedritas', 'piedritas'),
(2162, 38, 'Pila Pardo', 'pila-pardo'),
(2163, 38, 'Pueblo Ledesma', 'pueblo-ledesma'),
(2164, 38, 'Puente Lavayén', 'puente-lavayen'),
(2165, 38, 'Puerta de Colorados', 'puerta-de-colorados'),
(2166, 38, 'Puesto del Marquéz', 'puesto-del-marquez'),
(2167, 38, 'Puesto Sey', 'puesto-sey'),
(2168, 38, 'Puesto Viejo', 'puesto-viejo'),
(2169, 38, 'Pumahuasi', 'pumahuasi'),
(2170, 38, 'Purmamarca', 'purmamarca'),
(2171, 38, 'Quebraleña', 'quebralena'),
(2172, 38, 'Quera', 'quera'),
(2173, 38, 'Rinconada', 'rinconada'),
(2174, 38, 'Rinconadillas', 'rinconadillas'),
(2175, 38, 'Río Blanco', 'rio-blanco'),
(2176, 38, 'Rodeito', 'rodeito'),
(2177, 38, 'Rodero', 'rodero'),
(2178, 38, 'Rosario de Río Grande (ex Barro Negro)', 'rosario-de-rio-grande-ex-barro-negro'),
(2179, 38, 'San Antonio (San Antonio)', 'san-antonio-san-antonio'),
(2180, 38, 'San Antonio (San Pedro)', 'san-antonio-san-pedro'),
(2181, 38, 'San Francisco', 'san-francisco'),
(2182, 38, 'San Francisco de Alfarcito', 'san-francisco-de-alfarcito'),
(2183, 38, 'San Isidro', 'san-isidro'),
(2184, 38, 'San José', 'san-jose'),
(2185, 38, 'San Juan de Oros', 'san-juan-de-oros'),
(2186, 38, 'San Juan de Quillaqués', 'san-juan-de-quillaques'),
(2187, 38, 'San Juancito', 'san-juancito'),
(2188, 38, 'San Lucas', 'san-lucas'),
(2189, 38, 'San Pablo de Reyes', 'san-pablo-de-reyes'),
(2190, 38, 'San Pedro', 'san-pedro'),
(2191, 38, 'San Salvador de Jujuy', 'san-salvador-de-jujuy'),
(2192, 38, 'Santa Ana', 'santa-ana'),
(2193, 38, 'Santa Ana de la Puna', 'santa-ana-de-la-puna'),
(2194, 38, 'Santa Catalina', 'santa-catalina'),
(2195, 38, 'Santa Clara', 'santa-clara'),
(2196, 38, 'Santuario de Tres Pozos', 'santuario-de-tres-pozos'),
(2197, 38, 'Susques', 'susques'),
(2198, 38, 'Tambillos', 'tambillos'),
(2199, 38, 'Tesorero', 'tesorero'),
(2200, 38, 'Tilcara', 'tilcara');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(2201, 38, 'Tres Cruces', 'tres-cruces'),
(2202, 38, 'Tumbaya', 'tumbaya'),
(2203, 38, 'Tusaquillas', 'tusaquillas'),
(2204, 38, 'Uquía', 'uquia'),
(2205, 38, 'Valle Colorado', 'valle-colorado'),
(2206, 38, 'Valle Grande', 'valle-grande'),
(2207, 38, 'Vinalito', 'vinalito'),
(2208, 38, 'Volcán', 'volcan'),
(2209, 38, 'Yacoraite', 'yacoraite'),
(2210, 38, 'Yala', 'yala'),
(2211, 38, 'Yavi', 'yavi'),
(2212, 38, 'Yavi Chico', 'yavi-chico'),
(2213, 38, 'Yoscaba', 'yoscaba'),
(2214, 38, 'Yuto', 'yuto'),
(2215, 42, '25 de Mayo', '25-de-mayo'),
(2216, 42, 'Abramo', 'abramo'),
(2217, 42, 'Adolfo Van Praet', 'adolfo-van-praet'),
(2218, 42, 'Agustoni', 'agustoni'),
(2219, 42, 'Algarrobo del Águila', 'algarrobo-del-aguila'),
(2220, 42, 'Alpachiri', 'alpachiri'),
(2221, 42, 'Alta Italia', 'alta-italia'),
(2222, 42, 'Anguil', 'anguil'),
(2223, 42, 'Anzoátegui', 'anzoategui'),
(2224, 42, 'Arata', 'arata'),
(2225, 42, 'Ataliva Roca', 'ataliva-roca'),
(2226, 42, 'Bernardo Larroude', 'bernardo-larroude'),
(2227, 42, 'Bernasconi', 'bernasconi'),
(2228, 42, 'Cachirulo', 'cachirulo'),
(2229, 42, 'Caleufú', 'caleufu'),
(2230, 42, 'Carro Quemado', 'carro-quemado'),
(2231, 42, 'Casa de Piedra', 'casa-de-piedra'),
(2232, 42, 'Catriló', 'catrilo'),
(2233, 42, 'Ceballos', 'ceballos'),
(2234, 42, 'Chacharramendi', 'chacharramendi'),
(2235, 42, 'Colonia Barón', 'colonia-baron'),
(2236, 42, 'Colonia San José', 'colonia-san-jose'),
(2237, 42, 'Colonia Santa María', 'colonia-santa-maria'),
(2238, 42, 'Conhelo', 'conhelo'),
(2239, 42, 'Coronel Hilario Lagos', 'coronel-hilario-lagos'),
(2240, 42, 'Cuchillo Co', 'cuchillo-co'),
(2241, 42, 'Damián Maisonave', 'damian-maisonave'),
(2242, 42, 'Doblas', 'doblas'),
(2243, 42, 'Dorila', 'dorila'),
(2244, 42, 'Eduardo Castex', 'eduardo-castex'),
(2245, 42, 'Embajador Martini', 'embajador-martini'),
(2246, 42, 'Falucho', 'falucho'),
(2247, 42, 'General Acha', 'general-acha'),
(2248, 42, 'General Manuel J. Campos', 'general-manuel-j-campos'),
(2249, 42, 'General Pico', 'general-pico'),
(2250, 42, 'General San Martín', 'general-san-martin'),
(2251, 42, 'Gobernador Duval', 'gobernador-duval'),
(2252, 42, 'Guatraché', 'guatrache'),
(2253, 42, 'Hucal', 'hucal'),
(2254, 42, 'Ingeniero Foster', 'ingeniero-foster'),
(2255, 42, 'Ingeniero Luiggi', 'ingeniero-luiggi'),
(2256, 42, 'Intendente Alvear', 'intendente-alvear'),
(2257, 42, 'Jacinto Aráuz', 'jacinto-arauz'),
(2258, 42, 'La Adela', 'la-adela'),
(2259, 42, 'La Gloria', 'la-gloria'),
(2260, 42, 'La Humada', 'la-humada'),
(2261, 42, 'La Maruja', 'la-maruja'),
(2262, 42, 'La Reforma', 'la-reforma'),
(2263, 42, 'Limay Mahuida', 'limay-mahuida'),
(2264, 42, 'Lonquimay', 'lonquimay'),
(2265, 42, 'Loventué', 'loventue'),
(2266, 42, 'Luan Toro', 'luan-toro'),
(2267, 42, 'Macachín', 'macachin'),
(2268, 42, 'Mauricio Mayer', 'mauricio-mayer'),
(2269, 42, 'Metileo', 'metileo'),
(2270, 42, 'Miguel Cané', 'miguel-cane'),
(2271, 42, 'Miguel Riglos', 'miguel-riglos'),
(2272, 42, 'Monte Nievas', 'monte-nievas'),
(2273, 42, 'Naicó', 'naico'),
(2274, 42, 'Ojeda', 'ojeda'),
(2275, 42, 'Parera', 'parera'),
(2276, 42, 'Perú', 'peru'),
(2277, 42, 'Pichi Huinca', 'pichi-huinca'),
(2278, 42, 'Puelches', 'puelches'),
(2279, 42, 'Puelén', 'puelen'),
(2280, 42, 'Quehué', 'quehue'),
(2281, 42, 'Quemú Quemú', 'quemu-quemu'),
(2282, 42, 'Quetrequén', 'quetrequen'),
(2283, 42, 'Rancul', 'rancul'),
(2284, 42, 'Realicó', 'realico'),
(2285, 42, 'Relmo', 'relmo'),
(2286, 42, 'Rolón', 'rolon'),
(2287, 42, 'Rucanelo', 'rucanelo'),
(2288, 42, 'Santa Isabel', 'santa-isabel'),
(2289, 42, 'Santa Rosa', 'santa-rosa'),
(2290, 42, 'Santa Teresa', 'santa-teresa'),
(2291, 42, 'Sarah', 'sarah'),
(2292, 42, 'Speluzzi', 'speluzzi'),
(2293, 42, 'Telén', 'telen'),
(2294, 42, 'Toay', 'toay'),
(2295, 42, 'Tomás M. Anchorena', 'tomas-m-anchorena'),
(2296, 42, 'Trebolares', 'trebolares'),
(2297, 42, 'Trenel', 'trenel'),
(2298, 42, 'Unanué', 'unanue'),
(2299, 42, 'Uriburu', 'uriburu'),
(2300, 42, 'Vértiz', 'vertiz'),
(2301, 42, 'Victorica', 'victorica'),
(2302, 42, 'Villa Mirasol', 'villa-mirasol'),
(2303, 42, 'Winifreda', 'winifreda'),
(2304, 46, 'Aicuñá', 'aicuna'),
(2305, 46, 'Aimogasta', 'aimogasta'),
(2306, 46, 'Alpasinche', 'alpasinche'),
(2307, 46, 'Alto Carrizal', 'alto-carrizal'),
(2308, 46, 'Amaná', 'amana'),
(2309, 46, 'Ambil', 'ambil'),
(2310, 46, 'Aminga', 'aminga'),
(2311, 46, 'Amuschina', 'amuschina'),
(2312, 46, 'Andolucas', 'andolucas'),
(2313, 46, 'Anguinán', 'anguinan'),
(2314, 46, 'Angulos', 'angulos'),
(2315, 46, 'Anillaco', 'anillaco'),
(2316, 46, 'Anjullón', 'anjullon'),
(2317, 46, 'Antinaco', 'antinaco'),
(2318, 46, 'Bajo Carrizal', 'bajo-carrizal'),
(2319, 46, 'Bañado de los Pantanos', 'banado-de-los-pantanos'),
(2320, 46, 'Banda Florida', 'banda-florida'),
(2321, 46, 'Campanas', 'campanas'),
(2322, 46, 'Castro Barros', 'castro-barros'),
(2323, 46, 'Chamical', 'chamical'),
(2324, 46, 'Chañar', 'chanar'),
(2325, 46, 'Chañarmuyo', 'chanarmuyo'),
(2326, 46, 'Chaupihuasi', 'chaupihuasi'),
(2327, 46, 'Chepes', 'chepes'),
(2328, 46, 'Chilecito', 'chilecito'),
(2329, 46, 'Chuquis', 'chuquis'),
(2330, 46, 'Colonia Anguinán', 'colonia-anguinan'),
(2331, 46, 'Colonia Malligasta', 'colonia-malligasta'),
(2332, 46, 'Colonia Ortiz de Ocampo', 'colonia-ortiz-de-ocampo'),
(2333, 46, 'Colonia Vichigasta', 'colonia-vichigasta'),
(2334, 46, 'Cuipán', 'cuipan'),
(2335, 46, 'Desiderio Tello', 'desiderio-tello'),
(2336, 46, 'Estación Mazán', 'estacion-mazan'),
(2337, 46, 'Famatina', 'famatina'),
(2338, 46, 'Guanchín', 'guanchin'),
(2339, 46, 'Guandacol', 'guandacol'),
(2340, 46, 'Jagüé', 'jague'),
(2341, 46, 'La Cuadra', 'la-cuadra'),
(2342, 46, 'La Puntilla', 'la-puntilla'),
(2343, 46, 'La Rioja', 'la-rioja'),
(2344, 46, 'Las Talas', 'las-talas'),
(2345, 46, 'Loma Blanca', 'loma-blanca'),
(2346, 46, 'Los Molinos', 'los-molinos'),
(2347, 46, 'Los Palacios', 'los-palacios'),
(2348, 46, 'Los Robles', 'los-robles'),
(2349, 46, 'Los Sarmientos', 'los-sarmientos'),
(2350, 46, 'Machigasta', 'machigasta'),
(2351, 46, 'Malanzán', 'malanzan'),
(2352, 46, 'Malligasta', 'malligasta'),
(2353, 46, 'Milagro', 'milagro'),
(2354, 46, 'Miranda', 'miranda'),
(2355, 46, 'Nácate', 'nacate'),
(2356, 46, 'Nonogasta', 'nonogasta'),
(2357, 46, 'Olpas', 'olpas'),
(2358, 46, 'Olta', 'olta'),
(2359, 46, 'Pagancillo', 'pagancillo'),
(2360, 46, 'Patquía', 'patquia'),
(2361, 46, 'Pinchas', 'pinchas'),
(2362, 46, 'Pituil', 'pituil'),
(2363, 46, 'Plaza Vieja', 'plaza-vieja'),
(2364, 46, 'Polco', 'polco'),
(2365, 46, 'Portezuelo', 'portezuelo'),
(2366, 46, 'Punta de los Llanos', 'punta-de-los-llanos'),
(2367, 46, 'Salicas', 'salicas'),
(2368, 46, 'Salicas - San Blas', 'salicas-san-blas'),
(2369, 46, 'San Antonio (Arauco)', 'san-antonio-arauco'),
(2370, 46, 'San Antonio (General Juan Facundo Quiroga)', 'san-antonio-general-juan-facundo-quiroga'),
(2371, 46, 'San Blas', 'san-blas'),
(2372, 46, 'San Miguel', 'san-miguel'),
(2373, 46, 'San Nicolás', 'san-nicolas'),
(2374, 46, 'San Pedro', 'san-pedro'),
(2375, 46, 'Sañogasta', 'sanogasta'),
(2376, 46, 'Santa Clara', 'santa-clara'),
(2377, 46, 'Santa Cruz', 'santa-cruz'),
(2378, 46, 'Santa Florentina', 'santa-florentina'),
(2379, 46, 'Santa Rita de Catuna', 'santa-rita-de-catuna'),
(2380, 46, 'Santa Vera Cruz', 'santa-vera-cruz'),
(2381, 46, 'Santo Domingo', 'santo-domingo'),
(2382, 46, 'Shaqui', 'shaqui'),
(2383, 46, 'Suriyaco', 'suriyaco'),
(2384, 46, 'Tama', 'tama'),
(2385, 46, 'Termas de Santa Teresita', 'termas-de-santa-teresita'),
(2386, 46, 'Tilimuqui', 'tilimuqui'),
(2387, 46, 'Tuyubil', 'tuyubil'),
(2388, 46, 'Ulapes', 'ulapes'),
(2389, 46, 'Vichigasta', 'vichigasta'),
(2390, 46, 'Villa Castelli', 'villa-castelli'),
(2391, 46, 'Villa Mazán', 'villa-mazan'),
(2392, 46, 'Villa San José de Vinchina', 'villa-san-jose-de-vinchina'),
(2393, 46, 'Villa Sanagasta', 'villa-sanagasta'),
(2394, 46, 'Villa Unión', 'villa-union'),
(2395, 50, '10ma Sección', '10ma-seccion'),
(2396, 50, '11va Sección', '11va-seccion'),
(2397, 50, '1ra Sección', '1ra-seccion'),
(2398, 50, '25 de Mayo', '25-de-mayo'),
(2399, 50, '2da Sección', '2da-seccion'),
(2400, 50, '3 de Mayo', '3-de-mayo');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(2401, 50, '3ra Sección', '3ra-seccion'),
(2402, 50, '4ta Sección', '4ta-seccion'),
(2403, 50, '5ta Sección', '5ta-seccion'),
(2404, 50, '6ta Sección', '6ta-seccion'),
(2405, 50, '7ma Sección', '7ma-seccion'),
(2406, 50, '8va Sección', '8va-seccion'),
(2407, 50, '9na Sección', '9na-seccion'),
(2408, 50, 'Agrelo', 'agrelo'),
(2409, 50, 'Agua Escondida', 'agua-escondida'),
(2410, 50, 'Alto Verde', 'alto-verde'),
(2411, 50, 'Andrade', 'andrade'),
(2412, 50, 'Barrancas', 'barrancas'),
(2413, 50, 'Barrio 12 de Octubre', 'barrio-12-de-octubre'),
(2414, 50, 'Barrio Alto del Olvido', 'barrio-alto-del-olvido'),
(2415, 50, 'Barrio Belgrano Norte', 'barrio-belgrano-norte'),
(2416, 50, 'Barrio Carrasco', 'barrio-carrasco'),
(2417, 50, 'Barrio Chivilcoy', 'barrio-chivilcoy'),
(2418, 50, 'Barrio Cooperativa Los Campamentos', 'barrio-cooperativa-los-campamentos'),
(2419, 50, 'Barrio El Cepillo', 'barrio-el-cepillo'),
(2420, 50, 'Barrio El Nevado', 'barrio-el-nevado'),
(2421, 50, 'Barrio Emanuel', 'barrio-emanuel'),
(2422, 50, 'Barrio Empleados de Comercio', 'barrio-empleados-de-comercio'),
(2423, 50, 'Barrio Intendencia', 'barrio-intendencia'),
(2424, 50, 'Barrio Jesús de Nazaret', 'barrio-jesus-de-nazaret'),
(2425, 50, 'Barrio Jocolí II', 'barrio-jocoli-ii'),
(2426, 50, 'Barrio La Estación', 'barrio-la-estacion'),
(2427, 50, 'Barrio La Palmera', 'barrio-la-palmera'),
(2428, 50, 'Barrio La Pega', 'barrio-la-pega'),
(2429, 50, 'Barrio Lagunas de Bartoluzzi', 'barrio-lagunas-de-bartoluzzi'),
(2430, 50, 'Barrio Los Charabones', 'barrio-los-charabones'),
(2431, 50, 'Barrio Los Jarilleros', 'barrio-los-jarilleros'),
(2432, 50, 'Barrio Los Olivos', 'barrio-los-olivos'),
(2433, 50, 'Barrio María Auxiliadora', 'barrio-maria-auxiliadora'),
(2434, 50, 'Barrio Molina Cabrera', 'barrio-molina-cabrera'),
(2435, 50, 'Barrio Ntra. Sra. De Fátima', 'barrio-ntra-sra-de-fatima'),
(2436, 50, 'Barrio Perdriel IV', 'barrio-perdriel-iv'),
(2437, 50, 'Barrio Rivadavia', 'barrio-rivadavia'),
(2438, 50, 'Barrio San Cayetano', 'barrio-san-cayetano'),
(2439, 50, 'Barrio Virgen del Rosario', 'barrio-virgen-del-rosario'),
(2440, 50, 'Bermejo', 'bermejo'),
(2441, 50, 'Bowen', 'bowen'),
(2442, 50, 'Buena Nueva', 'buena-nueva'),
(2443, 50, 'Cacheuta', 'cacheuta'),
(2444, 50, 'Campo Los Andes', 'campo-los-andes'),
(2445, 50, 'Capdevila', 'capdevila'),
(2446, 50, 'Capilla del Rosario', 'capilla-del-rosario'),
(2447, 50, 'Capitán Montoya', 'capitan-montoya'),
(2448, 50, 'Carmensa', 'carmensa'),
(2449, 50, 'Carrodilla', 'carrodilla'),
(2450, 50, 'Chacras de Coria', 'chacras-de-coria'),
(2451, 50, 'Chapanay', 'chapanay'),
(2452, 50, 'Chilecito', 'chilecito'),
(2453, 50, 'Chivilcoy', 'chivilcoy'),
(2454, 50, 'Ciudad de Godoy Cruz', 'ciudad-de-godoy-cruz'),
(2455, 50, 'Ciudad de Las Heras', 'ciudad-de-las-heras'),
(2456, 50, 'Ciudad de Maipú', 'ciudad-de-maipu'),
(2457, 50, 'Ciudad Luján de Cuyo', 'ciudad-lujan-de-cuyo'),
(2458, 50, 'Colonia Las Rosas', 'colonia-las-rosas'),
(2459, 50, 'Colonia Segovia', 'colonia-segovia'),
(2460, 50, 'Coquimbito', 'coquimbito'),
(2461, 50, 'Cordón del Plata', 'cordon-del-plata'),
(2462, 50, 'Costa de Araujo', 'costa-de-araujo'),
(2463, 50, 'Costa Flores', 'costa-flores'),
(2464, 50, 'Cruz de Piedra', 'cruz-de-piedra'),
(2465, 50, 'Cuadro Benegas', 'cuadro-benegas'),
(2466, 50, 'Cuadro Nacional', 'cuadro-nacional'),
(2467, 50, 'Cuadro Ortega', 'cuadro-ortega'),
(2468, 50, 'Desaguadero', 'desaguadero'),
(2469, 50, 'Dorrego', 'dorrego'),
(2470, 50, 'El Algarrobal', 'el-algarrobal'),
(2471, 50, 'El Borbollón', 'el-borbollon'),
(2472, 50, 'El Carrizal', 'el-carrizal'),
(2473, 50, 'El Challao', 'el-challao'),
(2474, 50, 'El Espino', 'el-espino'),
(2475, 50, 'El Manzano', 'el-manzano'),
(2476, 50, 'El Mirador', 'el-mirador'),
(2477, 50, 'El Nihuil', 'el-nihuil'),
(2478, 50, 'El Paramillo', 'el-paramillo'),
(2479, 50, 'El Pedregal', 'el-pedregal'),
(2480, 50, 'El Peral', 'el-peral'),
(2481, 50, 'El Plumerillo', 'el-plumerillo'),
(2482, 50, 'El Ramblón', 'el-ramblon'),
(2483, 50, 'El Resguardo', 'el-resguardo'),
(2484, 50, 'El Salto', 'el-salto'),
(2485, 50, 'El Sauce', 'el-sauce'),
(2486, 50, 'El Sosneado', 'el-sosneado'),
(2487, 50, 'El Tropezón', 'el-tropezon'),
(2488, 50, 'El Vergel', 'el-vergel'),
(2489, 50, 'El Zampal', 'el-zampal'),
(2490, 50, 'El Zapallar', 'el-zapallar'),
(2491, 50, 'Fray Luis Beltrán', 'fray-luis-beltran'),
(2492, 50, 'General Alvear', 'general-alvear'),
(2493, 50, 'General Belgrano', 'general-belgrano'),
(2494, 50, 'General Gutiérrez', 'general-gutierrez'),
(2495, 50, 'Gobernador Benegas', 'gobernador-benegas'),
(2496, 50, 'Godoy Cruz', 'godoy-cruz'),
(2497, 50, 'Goudge', 'goudge'),
(2498, 50, 'Guaymallén', 'guaymallen'),
(2499, 50, 'Ingeniero Giagnoni', 'ingeniero-giagnoni'),
(2500, 50, 'Ingeniero Gustavo André', 'ingeniero-gustavo-andre'),
(2501, 50, 'Jaime Prats', 'jaime-prats'),
(2502, 50, 'Jesús Nazareno', 'jesus-nazareno'),
(2503, 50, 'Jocolí (Las Heras)', 'jocoli-las-heras'),
(2504, 50, 'Jocolí (Lavalle)', 'jocoli-lavalle'),
(2505, 50, 'Jocolí Viejo', 'jocoli-viejo'),
(2506, 50, 'Junín', 'junin'),
(2507, 50, 'La Arboleda', 'la-arboleda'),
(2508, 50, 'La Central', 'la-central'),
(2509, 50, 'La Cieneguita', 'la-cieneguita'),
(2510, 50, 'La Colonia', 'la-colonia'),
(2511, 50, 'La Consulta', 'la-consulta'),
(2512, 50, 'La Dormida', 'la-dormida'),
(2513, 50, 'La Esperanza', 'la-esperanza'),
(2514, 50, 'La Florida', 'la-florida'),
(2515, 50, 'La Libertad', 'la-libertad'),
(2516, 50, 'La Llave Nueva', 'la-llave-nueva'),
(2517, 50, 'La Paz', 'la-paz'),
(2518, 50, 'La Primavera', 'la-primavera'),
(2519, 50, 'La Puntilla', 'la-puntilla'),
(2520, 50, 'Las Cañas', 'las-canas'),
(2521, 50, 'Las Catitas', 'las-catitas'),
(2522, 50, 'Las Compuertas', 'las-compuertas'),
(2523, 50, 'Las Cuevas', 'las-cuevas'),
(2524, 50, 'Las Heras', 'las-heras'),
(2525, 50, 'Las Leñas', 'las-lenas'),
(2526, 50, 'Las Malvinas', 'las-malvinas'),
(2527, 50, 'Las Tortugas', 'las-tortugas'),
(2528, 50, 'Las Vegas', 'las-vegas'),
(2529, 50, 'Las Violetas', 'las-violetas'),
(2530, 50, 'Los Árboles', 'los-arboles'),
(2531, 50, 'Los Barriales', 'los-barriales'),
(2532, 50, 'Los Campamentos', 'los-campamentos'),
(2533, 50, 'Los Compartos', 'los-compartos'),
(2534, 50, 'Los Corralitos', 'los-corralitos'),
(2535, 50, 'Los Penitentes', 'los-penitentes'),
(2536, 50, 'Los Sauces', 'los-sauces'),
(2537, 50, 'Luján de Cuyo', 'lujan-de-cuyo'),
(2538, 50, 'Luzuriaga', 'luzuriaga'),
(2539, 50, 'Maipú', 'maipu'),
(2540, 50, 'Malargüe', 'malargue'),
(2541, 50, 'Mayor Drummond', 'mayor-drummond'),
(2542, 50, 'Medrano (Junín)', 'medrano-junin'),
(2543, 50, 'Medrano (Rivadavia)', 'medrano-rivadavia'),
(2544, 50, 'Mendoza', 'mendoza'),
(2545, 50, 'Monte Comán', 'monte-coman'),
(2546, 50, 'Montecaseros', 'montecaseros'),
(2547, 50, 'Mundo Nuevo', 'mundo-nuevo'),
(2548, 50, 'Nueva California', 'nueva-california'),
(2549, 50, 'Nueva Ciudad', 'nueva-ciudad'),
(2550, 50, 'Palmira', 'palmira'),
(2551, 50, 'Panquehuá', 'panquehua'),
(2552, 50, 'Pareditas', 'pareditas'),
(2553, 50, 'Pedro Molina', 'pedro-molina'),
(2554, 50, 'Perdriel', 'perdriel'),
(2555, 50, 'Phillips', 'phillips'),
(2556, 50, 'Pobre Diablo', 'pobre-diablo'),
(2557, 50, 'Polvaredas', 'polvaredas'),
(2558, 50, 'Potrerillos', 'potrerillos'),
(2559, 50, 'Presidente Sarmiento', 'presidente-sarmiento'),
(2560, 50, 'Puente de Hierro', 'puente-de-hierro'),
(2561, 50, 'Puente del Inca', 'puente-del-inca'),
(2562, 50, 'Punta de Vacas', 'punta-de-vacas'),
(2563, 50, 'Punta del Agua', 'punta-del-agua'),
(2564, 50, 'Rama Caída', 'rama-caida'),
(2565, 50, 'Real del Padre', 'real-del-padre'),
(2566, 50, 'Reducción de Abajo', 'reduccion-de-abajo'),
(2567, 50, 'Rivadavia', 'rivadavia'),
(2568, 50, 'Rodeo de la Cruz', 'rodeo-de-la-cruz'),
(2569, 50, 'Rodeo del Medio', 'rodeo-del-medio'),
(2570, 50, 'Rodríguez Peña', 'rodriguez-pena'),
(2571, 50, 'Russell', 'russell'),
(2572, 50, 'Salto de las Rosas', 'salto-de-las-rosas'),
(2573, 50, 'San Carlos', 'san-carlos'),
(2574, 50, 'San Francisco del Monte (Godoy Cruz)', 'san-francisco-del-monte-godoy-cruz'),
(2575, 50, 'San Francisco del Monte (Guaymallén)', 'san-francisco-del-monte-guaymallen'),
(2576, 50, 'San José (Guaymallén)', 'san-jose-guaymallen'),
(2577, 50, 'San José (Tupungato)', 'san-jose-tupungato'),
(2578, 50, 'San Martín', 'san-martin'),
(2579, 50, 'San Rafael', 'san-rafael'),
(2580, 50, 'San Roque', 'san-roque'),
(2581, 50, 'Santa María de Oro', 'santa-maria-de-oro'),
(2582, 50, 'Santa Rosa', 'santa-rosa'),
(2583, 50, 'Sierras de Encalada', 'sierras-de-encalada'),
(2584, 50, 'Tres Porteñas', 'tres-portenas'),
(2585, 50, 'Tunuyán', 'tunuyan'),
(2586, 50, 'Tupungato', 'tupungato'),
(2587, 50, 'Ugarteche', 'ugarteche'),
(2588, 50, 'Uspallata', 'uspallata'),
(2589, 50, 'Villa Antigua', 'villa-antigua'),
(2590, 50, 'Villa Atuel', 'villa-atuel'),
(2591, 50, 'Villa Atuel Norte', 'villa-atuel-norte'),
(2592, 50, 'Villa Bastias', 'villa-bastias'),
(2593, 50, 'Villa Nueva', 'villa-nueva'),
(2594, 50, 'Villa Tulumaya', 'villa-tulumaya'),
(2595, 50, 'Vista Flores', 'vista-flores'),
(2596, 50, 'Vistalba', 'vistalba'),
(2597, 54, '1º de Mayo', '1o-de-mayo'),
(2598, 54, '25 de Mayo', '25-de-mayo'),
(2599, 54, '9 de Julio Kilómetro 20', '9-de-julio-kilometro-20'),
(2600, 54, '9 de Julio Kilómetro 28', '9-de-julio-kilometro-28');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(2601, 54, 'Alba Posse', 'alba-posse'),
(2602, 54, 'Alicia Alta', 'alicia-alta'),
(2603, 54, 'Alicia Baja', 'alicia-baja'),
(2604, 54, 'Almafuerte', 'almafuerte'),
(2605, 54, 'Apóstoles', 'apostoles'),
(2606, 54, 'Aristóbulo del Valle', 'aristobulo-del-valle'),
(2607, 54, 'Arroyo del Medio', 'arroyo-del-medio'),
(2608, 54, 'Azara', 'azara'),
(2609, 54, 'Bario Ita', 'bario-ita'),
(2610, 54, 'Barra Concepción', 'barra-concepcion'),
(2611, 54, 'Barrio Cuatro Bocas', 'barrio-cuatro-bocas'),
(2612, 54, 'Barrio del Lago', 'barrio-del-lago'),
(2613, 54, 'Barrio Escuela 461', 'barrio-escuela-461'),
(2614, 54, 'Barrio Escuela 633', 'barrio-escuela-633'),
(2615, 54, 'Barrio Guatambu', 'barrio-guatambu'),
(2616, 54, 'Barrio Rural', 'barrio-rural'),
(2617, 54, 'Barrio Tungoil', 'barrio-tungoil'),
(2618, 54, 'Bernardo de Irigoyen', 'bernardo-de-irigoyen'),
(2619, 54, 'Bonpland', 'bonpland'),
(2620, 54, 'Caá - Yarí', 'caa-yari'),
(2621, 54, 'Caburei', 'caburei'),
(2622, 54, 'Campo Grande', 'campo-grande'),
(2623, 54, 'Campo Ramón', 'campo-ramon'),
(2624, 54, 'Campo Viera', 'campo-viera'),
(2625, 54, 'Candelaria', 'candelaria'),
(2626, 54, 'Capioví', 'capiovi'),
(2627, 54, 'Capioviciño', 'capiovicino'),
(2628, 54, 'Caraguatay', 'caraguatay'),
(2629, 54, 'Cerro Azul', 'cerro-azul'),
(2630, 54, 'Cerro Corá', 'cerro-cora'),
(2631, 54, 'Colonia Alberdi', 'colonia-alberdi'),
(2632, 54, 'Colonia Aurora', 'colonia-aurora'),
(2633, 54, 'Colonia Polana', 'colonia-polana'),
(2634, 54, 'Colonia Victoria', 'colonia-victoria'),
(2635, 54, 'Colonia Wanda', 'colonia-wanda'),
(2636, 54, 'Comandante Andresito', 'comandante-andresito'),
(2637, 54, 'Concepción de la Sierra', 'concepcion-de-la-sierra'),
(2638, 54, 'Corpus', 'corpus'),
(2639, 54, 'Cruce Caballero', 'cruce-caballero'),
(2640, 54, 'Domingo Savio', 'domingo-savio'),
(2641, 54, 'Dos Arroyos', 'dos-arroyos'),
(2642, 54, 'Dos de Mayo', 'dos-de-mayo'),
(2643, 54, 'Dos de Mayo Núcleo I', 'dos-de-mayo-nucleo-i'),
(2644, 54, 'Dos de Mayo Núcleo II', 'dos-de-mayo-nucleo-ii'),
(2645, 54, 'Dos de Mayo Nucleo III (Bº Bernardino Rivadavia)', 'dos-de-mayo-nucleo-iii-bo-bernardino-54014050'),
(2646, 54, 'Dos Hermanas', 'dos-hermanas'),
(2647, 54, 'El Alcázar', 'el-alcazar'),
(2648, 54, 'El Salto', 'el-salto'),
(2649, 54, 'El Soberbio', 'el-soberbio'),
(2650, 54, 'Eldorado', 'eldorado'),
(2651, 54, 'Estación Apóstoles', 'estacion-apostoles'),
(2652, 54, 'Florentino Ameghino', 'florentino-ameghino'),
(2653, 54, 'Fracrán', 'fracran'),
(2654, 54, 'Garuhapé', 'garuhape'),
(2655, 54, 'Garupá', 'garupa'),
(2656, 54, 'General Alvear', 'general-alvear'),
(2657, 54, 'General Urquiza', 'general-urquiza'),
(2658, 54, 'Gobernador López', 'gobernador-lopez'),
(2659, 54, 'Gobernador Roca', 'gobernador-roca'),
(2660, 54, 'Guaraní', 'guarani'),
(2661, 54, 'Helvecia', 'helvecia'),
(2662, 54, 'Hipólito Yrigoyen', 'hipolito-yrigoyen'),
(2663, 54, 'Integración', 'integracion'),
(2664, 54, 'Itacaruaré', 'itacaruare'),
(2665, 54, 'Jardín América', 'jardin-america'),
(2666, 54, 'Kilómetro 17', 'kilometro-17'),
(2667, 54, 'La Corita', 'la-corita'),
(2668, 54, 'Laharrague', 'laharrague'),
(2669, 54, 'Leandro N. Alem', 'leandro-n-alem'),
(2670, 54, 'Loreto', 'loreto'),
(2671, 54, 'Los Helechos', 'los-helechos'),
(2672, 54, 'María Magdalena', 'maria-magdalena'),
(2673, 54, 'Mártires', 'martires'),
(2674, 54, 'Mbopicuá', 'mbopicua'),
(2675, 54, 'Mojón Grande', 'mojon-grande'),
(2676, 54, 'Montecarlo', 'montecarlo'),
(2677, 54, 'Nemesio Parma', 'nemesio-parma'),
(2678, 54, 'Nueva Delicia', 'nueva-delicia'),
(2679, 54, 'Oasis', 'oasis'),
(2680, 54, 'Oberá', 'obera'),
(2681, 54, 'Olegario V. Andrade', 'olegario-v-andrade'),
(2682, 54, 'Panambí', 'panambi'),
(2683, 54, 'Panambi Kilómetro 15', 'panambi-kilometro-15'),
(2684, 54, 'Panambí Kilómetro 8', 'panambi-kilometro-8'),
(2685, 54, 'Paraíso', 'paraiso'),
(2686, 54, 'Piñalito Norte', 'pinalito-norte'),
(2687, 54, 'Piñalito Sur', 'pinalito-sur'),
(2688, 54, 'Pindapoy', 'pindapoy'),
(2689, 54, 'Piray Kilómetro 18', 'piray-kilometro-18'),
(2690, 54, 'Posadas', 'posadas'),
(2691, 54, 'Posadas (Extensión)', 'posadas-extension'),
(2692, 54, 'Profundidad', 'profundidad'),
(2693, 54, 'Pueblo Illia', 'pueblo-illia'),
(2694, 54, 'Pueblo Nuevo', 'pueblo-nuevo'),
(2695, 54, 'Puerto Andresito', 'puerto-andresito'),
(2696, 54, 'Puerto Deseado', 'puerto-deseado'),
(2697, 54, 'Puerto Esperanza', 'puerto-esperanza'),
(2698, 54, 'Puerto Iguazú', 'puerto-iguazu'),
(2699, 54, 'Puerto Leoni', 'puerto-leoni'),
(2700, 54, 'Puerto Libertad', 'puerto-libertad'),
(2701, 54, 'Puerto Mado', 'puerto-mado'),
(2702, 54, 'Puerto Pinares', 'puerto-pinares'),
(2703, 54, 'Puerto Piray', 'puerto-piray'),
(2704, 54, 'Puerto Rico', 'puerto-rico'),
(2705, 54, 'Puerto Santa Ana', 'puerto-santa-ana'),
(2706, 54, 'Rincón de Azara', 'rincon-de-azara'),
(2707, 54, 'Roca Chica', 'roca-chica'),
(2708, 54, 'Ruiz de Montoya', 'ruiz-de-montoya'),
(2709, 54, 'Salto Encantado', 'salto-encantado'),
(2710, 54, 'San Alberto', 'san-alberto'),
(2711, 54, 'San Antonio', 'san-antonio'),
(2712, 54, 'San Francisco de Asís', 'san-francisco-de-asis'),
(2713, 54, 'San Gotardo', 'san-gotardo'),
(2714, 54, 'San Ignacio', 'san-ignacio'),
(2715, 54, 'San Javier', 'san-javier'),
(2716, 54, 'San José', 'san-jose'),
(2717, 54, 'San Martín', 'san-martin'),
(2718, 54, 'San Miguel', 'san-miguel'),
(2719, 54, 'San Pedro', 'san-pedro'),
(2720, 54, 'San Vicente', 'san-vicente'),
(2721, 54, 'Santa Ana', 'santa-ana'),
(2722, 54, 'Santa María', 'santa-maria'),
(2723, 54, 'Santa Rita', 'santa-rita'),
(2724, 54, 'Santiago de Liniers', 'santiago-de-liniers'),
(2725, 54, 'Santo Pipó', 'santo-pipo'),
(2726, 54, 'Tarumá', 'taruma'),
(2727, 54, 'Tobuna', 'tobuna'),
(2728, 54, 'Tres Capones', 'tres-capones'),
(2729, 54, 'Valle Hermoso', 'valle-hermoso'),
(2730, 54, 'Villa Akerman', 'villa-akerman'),
(2731, 54, 'Villa Bonita', 'villa-bonita'),
(2732, 54, 'Villa Cooperativa', 'villa-cooperativa'),
(2733, 54, 'Villa Libertad', 'villa-libertad'),
(2734, 54, 'Villa Parodi', 'villa-parodi'),
(2735, 54, 'Villa Roulet', 'villa-roulet'),
(2736, 54, 'Villa Urrutia', 'villa-urrutia'),
(2737, 58, 'Aguada San Roque', 'aguada-san-roque'),
(2738, 58, 'Aluminé', 'alumine'),
(2739, 58, 'Andacollo', 'andacollo'),
(2740, 58, 'Añelo', 'anelo'),
(2741, 58, 'Arroyito', 'arroyito'),
(2742, 58, 'Bajada del Agrio', 'bajada-del-agrio'),
(2743, 58, 'Barrancas', 'barrancas'),
(2744, 58, 'Buta Ranquil', 'buta-ranquil'),
(2745, 58, 'Caviahue', 'caviahue'),
(2746, 58, 'Centenario', 'centenario'),
(2747, 58, 'Chorriaca', 'chorriaca'),
(2748, 58, 'Chos Malal', 'chos-malal'),
(2749, 58, 'Copahue', 'copahue'),
(2750, 58, 'Covunco Centro', 'covunco-centro'),
(2751, 58, 'Cutral Có', 'cutral-co'),
(2752, 58, 'El Cholar', 'el-cholar'),
(2753, 58, 'El Huecú', 'el-huecu'),
(2754, 58, 'El Sauce', 'el-sauce'),
(2755, 58, 'Huinganco', 'huinganco'),
(2756, 58, 'Junín de los Andes', 'junin-de-los-andes'),
(2757, 58, 'La Buitrera', 'la-buitrera'),
(2758, 58, 'Las Coloradas', 'las-coloradas'),
(2759, 58, 'Las Lajas', 'las-lajas'),
(2760, 58, 'Las Ovejas', 'las-ovejas'),
(2761, 58, 'Loncopué', 'loncopue'),
(2762, 58, 'Los Catutos', 'los-catutos'),
(2763, 58, 'Los Miches', 'los-miches'),
(2764, 58, 'Manzano Amargo', 'manzano-amargo'),
(2765, 58, 'Mari Menuco', 'mari-menuco'),
(2766, 58, 'Mariano Moreno', 'mariano-moreno'),
(2767, 58, 'Moquehue', 'moquehue'),
(2768, 58, 'Neuquén', 'neuquen'),
(2769, 58, 'Octavio Pico', 'octavio-pico'),
(2770, 58, 'Paso Aguerre', 'paso-aguerre'),
(2771, 58, 'Picún Leufú', 'picun-leufu'),
(2772, 58, 'Piedra del Águila', 'piedra-del-aguila'),
(2773, 58, 'Plaza Huincul', 'plaza-huincul'),
(2774, 58, 'Plottier', 'plottier'),
(2775, 58, 'Quili Malal', 'quili-malal'),
(2776, 58, 'Ramón M. Castro', 'ramon-m-castro'),
(2777, 58, 'Rincón de los Sauces', 'rincon-de-los-sauces'),
(2778, 58, 'San Martín de los Andes', 'san-martin-de-los-andes'),
(2779, 58, 'San Patricio del Chañar', 'san-patricio-del-chanar'),
(2780, 58, 'Santo Tomás', 'santo-tomas'),
(2781, 58, 'Senillosa', 'senillosa'),
(2782, 58, 'Taquimilán', 'taquimilan'),
(2783, 58, 'Tricao Malal', 'tricao-malal'),
(2784, 58, 'Varvarco', 'varvarco'),
(2785, 58, 'Villa del Curi Leuvú', 'villa-del-curi-leuvu'),
(2786, 58, 'Villa del Nahueve', 'villa-del-nahueve'),
(2787, 58, 'Villa El Chocón', 'villa-el-chocon'),
(2788, 58, 'Villa La Angostura', 'villa-la-angostura'),
(2789, 58, 'Villa Lago Meliquina', 'villa-lago-meliquina'),
(2790, 58, 'Villa Pehuenia', 'villa-pehuenia'),
(2791, 58, 'Villa Traful', 'villa-traful'),
(2792, 58, 'Vista Alegre Norte', 'vista-alegre-norte'),
(2793, 58, 'Vista Alegre Sur', 'vista-alegre-sur'),
(2794, 58, 'Zapala', 'zapala'),
(2795, 62, 'Aguada Cecilio', 'aguada-cecilio'),
(2796, 62, 'Aguada de Guerra', 'aguada-de-guerra'),
(2797, 62, 'Aguada Guzmán', 'aguada-guzman'),
(2798, 62, 'Allen', 'allen'),
(2799, 62, 'Arroyo Los Berros', 'arroyo-los-berros'),
(2800, 62, 'Arroyo Ventana', 'arroyo-ventana');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(2801, 62, 'Bahía Creek', 'bahia-creek'),
(2802, 62, 'Barda del Medio', 'barda-del-medio'),
(2803, 62, 'Barrio Blanco', 'barrio-blanco'),
(2804, 62, 'Barrio Calle Ciega Nº 10', 'barrio-calle-ciega-no-10'),
(2805, 62, 'Barrio Calle Ciega Nº 6', 'barrio-calle-ciega-no-6'),
(2806, 62, 'Barrio Canale', 'barrio-canale'),
(2807, 62, 'Barrio Chacra Monte', 'barrio-chacra-monte'),
(2808, 62, 'Barrio Colonia Conesa', 'barrio-colonia-conesa'),
(2809, 62, 'Barrio Costa Este', 'barrio-costa-este'),
(2810, 62, 'Barrio Costa Oeste', 'barrio-costa-oeste'),
(2811, 62, 'Barrio Destacamento', 'barrio-destacamento'),
(2812, 62, 'Barrio El Labrador', 'barrio-el-labrador'),
(2813, 62, 'Barrio El Maruchito', 'barrio-el-maruchito'),
(2814, 62, 'Barrio El Petróleo', 'barrio-el-petroleo'),
(2815, 62, 'Barrio Emergente', 'barrio-emergente'),
(2816, 62, 'Barrio Esperanza', 'barrio-esperanza'),
(2817, 62, 'Barrio Fátima', 'barrio-fatima'),
(2818, 62, 'Barrio Frontera', 'barrio-frontera'),
(2819, 62, 'Barrio Guerrico', 'barrio-guerrico'),
(2820, 62, 'Barrio Isla 10', 'barrio-isla-10'),
(2821, 62, 'Barrio La Barda', 'barrio-la-barda'),
(2822, 62, 'Barrio La Costa', 'barrio-la-costa'),
(2823, 62, 'Barrio La Defensa', 'barrio-la-defensa'),
(2824, 62, 'Barrio La Herradura', 'barrio-la-herradura'),
(2825, 62, 'Barrio La Lor', 'barrio-la-lor'),
(2826, 62, 'Barrio Luisillo', 'barrio-luisillo'),
(2827, 62, 'Barrio Mar del Plata', 'barrio-mar-del-plata'),
(2828, 62, 'Barrio María Elvira', 'barrio-maria-elvira'),
(2829, 62, 'Barrio Moño Azul', 'barrio-mono-azul'),
(2830, 62, 'Barrio Norte', 'barrio-norte'),
(2831, 62, 'Barrio Pinar', 'barrio-pinar'),
(2832, 62, 'Barrio Pino Azul', 'barrio-pino-azul'),
(2833, 62, 'Barrio Planta Compresora de Gas', 'barrio-planta-compresora-de-gas'),
(2834, 62, 'Barrio Porvenir', 'barrio-porvenir'),
(2835, 62, 'Barrio Presidente Perón', 'barrio-presidente-peron'),
(2836, 62, 'Barrio Santa Lucia', 'barrio-santa-lucia'),
(2837, 62, 'Barrio Santa Rita', 'barrio-santa-rita'),
(2838, 62, 'Barrio Unión (Avellaneda)', 'barrio-union-avellaneda'),
(2839, 62, 'Barrio Unión (General Roca)', 'barrio-union-general-roca'),
(2840, 62, 'Cañadón Chileno', 'canadon-chileno'),
(2841, 62, 'Catriel', 'catriel'),
(2842, 62, 'Cerro Policía', 'cerro-policia'),
(2843, 62, 'Cervantes', 'cervantes'),
(2844, 62, 'Chelforó', 'chelforo'),
(2845, 62, 'Chichinales', 'chichinales'),
(2846, 62, 'Chimpay', 'chimpay'),
(2847, 62, 'Choele Choel', 'choele-choel'),
(2848, 62, 'Cinco Saltos', 'cinco-saltos'),
(2849, 62, 'Cipolletti', 'cipolletti'),
(2850, 62, 'Clemente Onelli', 'clemente-onelli'),
(2851, 62, 'Colan Conhue', 'colan-conhue'),
(2852, 62, 'Colonia Juliá y Echarren', 'colonia-julia-y-echarren'),
(2853, 62, 'Colonia Suiza', 'colonia-suiza'),
(2854, 62, 'Comallo', 'comallo'),
(2855, 62, 'Comicó', 'comico'),
(2856, 62, 'Cona Niyeu', 'cona-niyeu'),
(2857, 62, 'Contralmirante Cordero', 'contralmirante-cordero'),
(2858, 62, 'Coronel Belisle', 'coronel-belisle'),
(2859, 62, 'Darwin', 'darwin'),
(2860, 62, 'Dina Huapi', 'dina-huapi'),
(2861, 62, 'El Bolsón', 'el-bolson'),
(2862, 62, 'El Caín', 'el-cain'),
(2863, 62, 'El Cóndor', 'el-condor'),
(2864, 62, 'El Cuy', 'el-cuy'),
(2865, 62, 'El Empalme', 'el-empalme'),
(2866, 62, 'El Foyel', 'el-foyel'),
(2867, 62, 'El Juncal', 'el-juncal'),
(2868, 62, 'Ferri', 'ferri'),
(2869, 62, 'General Conesa', 'general-conesa'),
(2870, 62, 'General Enrique Godoy', 'general-enrique-godoy'),
(2871, 62, 'General Fernández Oro', 'general-fernandez-oro'),
(2872, 62, 'General Roca', 'general-roca'),
(2873, 62, 'Guardia Mitre', 'guardia-mitre'),
(2874, 62, 'Ingeniero Jacobacci', 'ingeniero-jacobacci'),
(2875, 62, 'Ingeniero Luis A. Huergo', 'ingeniero-luis-a-huergo'),
(2876, 62, 'Ingeniero Otto Krause', 'ingeniero-otto-krause'),
(2877, 62, 'Juventud Unida', 'juventud-unida'),
(2878, 62, 'La Lobería', 'la-loberia'),
(2879, 62, 'Laguna Blanca', 'laguna-blanca'),
(2880, 62, 'Lamarque', 'lamarque'),
(2881, 62, 'Las Bayas', 'las-bayas'),
(2882, 62, 'Las Grutas', 'las-grutas'),
(2883, 62, 'Las Perlas', 'las-perlas'),
(2884, 62, 'Los Menucos', 'los-menucos'),
(2885, 62, 'Loteo Costa de Río', 'loteo-costa-de-rio'),
(2886, 62, 'Luis Beltrán', 'luis-beltran'),
(2887, 62, 'Mainqué', 'mainque'),
(2888, 62, 'Mallín Ahogado', 'mallin-ahogado'),
(2889, 62, 'Mamuel Choique', 'mamuel-choique'),
(2890, 62, 'Maquinchao', 'maquinchao'),
(2891, 62, 'Mencué', 'mencue'),
(2892, 62, 'Ministro Ramos Mexía', 'ministro-ramos-mexia'),
(2893, 62, 'Nahuel Niyeu', 'nahuel-niyeu'),
(2894, 62, 'Naupa Huen', 'naupa-huen'),
(2895, 62, 'Ñirihuau', 'nirihuau'),
(2896, 62, 'Ñorquincó', 'norquinco'),
(2897, 62, 'Ojos de Agua', 'ojos-de-agua'),
(2898, 62, 'Paraje Arroyón (Bajo San Cayetano)', 'paraje-arroyon-bajo-san-cayetano'),
(2899, 62, 'Paso Córdova (El Cuy)', 'paso-cordova-el-cuy'),
(2900, 62, 'Paso Córdova (General Roca)', 'paso-cordova-general-roca'),
(2901, 62, 'Península Ruca Co', 'peninsula-ruca-co'),
(2902, 62, 'Pichi Mahuida', 'pichi-mahuida'),
(2903, 62, 'Pilcaniyeu', 'pilcaniyeu'),
(2904, 62, 'Pilquiniyeu', 'pilquiniyeu'),
(2905, 62, 'Pilquiniyeu del Limay', 'pilquiniyeu-del-limay'),
(2906, 62, 'Playas Doradas', 'playas-doradas'),
(2907, 62, 'Pomona', 'pomona'),
(2908, 62, 'Pozo Salado', 'pozo-salado'),
(2909, 62, 'Prahuaniyeu', 'prahuaniyeu'),
(2910, 62, 'Puente Cero', 'puente-cero'),
(2911, 62, 'Puerto San Antonio Este', 'puerto-san-antonio-este'),
(2912, 62, 'Punta Colorada', 'punta-colorada'),
(2913, 62, 'Río Chico', 'rio-chico'),
(2914, 62, 'Río Colorado', 'rio-colorado'),
(2915, 62, 'Río Villegas', 'rio-villegas'),
(2916, 62, 'Saco Viejo', 'saco-viejo'),
(2917, 62, 'Salto Andersen', 'salto-andersen'),
(2918, 62, 'San Antonio Oeste', 'san-antonio-oeste'),
(2919, 62, 'San Carlos de Bariloche', 'san-carlos-de-bariloche'),
(2920, 62, 'San Javier', 'san-javier'),
(2921, 62, 'Sargento Vidal', 'sargento-vidal'),
(2922, 62, 'Sierra Colorada', 'sierra-colorada'),
(2923, 62, 'Sierra Grande', 'sierra-grande'),
(2924, 62, 'Sierra Pailemán', 'sierra-paileman'),
(2925, 62, 'Treneta', 'treneta'),
(2926, 62, 'Valcheta', 'valcheta'),
(2927, 62, 'Valle Azul', 'valle-azul'),
(2928, 62, 'Viedma', 'viedma'),
(2929, 62, 'Villa Alberdi', 'villa-alberdi'),
(2930, 62, 'Villa Campanario', 'villa-campanario'),
(2931, 62, 'Villa Catedral', 'villa-catedral'),
(2932, 62, 'Villa del Parque', 'villa-del-parque'),
(2933, 62, 'Villa Llanquín', 'villa-llanquin'),
(2934, 62, 'Villa Llao Llao', 'villa-llao-llao'),
(2935, 62, 'Villa Manzano', 'villa-manzano'),
(2936, 62, 'Villa Mascardi', 'villa-mascardi'),
(2937, 62, 'Villa Regina', 'villa-regina'),
(2938, 62, 'Villa San Isidro', 'villa-san-isidro'),
(2939, 62, 'Yaminué', 'yaminue'),
(2940, 66, 'Acoyte', 'acoyte'),
(2941, 66, 'Aguaray', 'aguaray'),
(2942, 66, 'Aguas Blancas', 'aguas-blancas'),
(2943, 66, 'Alto de la Sierra', 'alto-de-la-sierra'),
(2944, 66, 'Ampascachi', 'ampascachi'),
(2945, 66, 'Angastaco', 'angastaco'),
(2946, 66, 'Animaná', 'animana'),
(2947, 66, 'Antillá', 'antilla'),
(2948, 66, 'Apolinario Saravia', 'apolinario-saravia'),
(2949, 66, 'Barrio Finca La Maroma', 'barrio-finca-la-maroma'),
(2950, 66, 'Barrio La Rotonda', 'barrio-la-rotonda'),
(2951, 66, 'Barrio Santa Teresita', 'barrio-santa-teresita'),
(2952, 66, 'Cabra Corral', 'cabra-corral'),
(2953, 66, 'Cachi', 'cachi'),
(2954, 66, 'Cafayate', 'cafayate'),
(2955, 66, 'Campichuelo', 'campichuelo'),
(2956, 66, 'Campo Durán', 'campo-duran'),
(2957, 66, 'Campo La Cruz', 'campo-la-cruz'),
(2958, 66, 'Campo Quijano', 'campo-quijano'),
(2959, 66, 'Campo Santo', 'campo-santo'),
(2960, 66, 'Capiazuti', 'capiazuti'),
(2961, 66, 'Capitán Juan Pagé', 'capitan-juan-page'),
(2962, 66, 'Carboncito', 'carboncito'),
(2963, 66, 'Ceibalito', 'ceibalito'),
(2964, 66, 'Centro 25 de Junio', 'centro-25-de-junio'),
(2965, 66, 'Cerrillos', 'cerrillos'),
(2966, 66, 'Chicoana', 'chicoana'),
(2967, 66, 'Cobos', 'cobos'),
(2968, 66, 'Cobres', 'cobres'),
(2969, 66, 'Colonia Santa Rosa', 'colonia-santa-rosa'),
(2970, 66, 'Copo Quile', 'copo-quile'),
(2971, 66, 'Coronel Cornejo', 'coronel-cornejo'),
(2972, 66, 'Coronel Juan Solá', 'coronel-juan-sola'),
(2973, 66, 'Coronel Moldes', 'coronel-moldes'),
(2974, 66, 'Coronel Mollinedo', 'coronel-mollinedo'),
(2975, 66, 'Coronel Olleros', 'coronel-olleros'),
(2976, 66, 'Country Club El Tipal', 'country-club-el-tipal'),
(2977, 66, 'Country Club La Almudena', 'country-club-la-almudena'),
(2978, 66, 'Dragones', 'dragones'),
(2979, 66, 'El Bordo', 'el-bordo'),
(2980, 66, 'El Carril', 'el-carril'),
(2981, 66, 'El Galpón', 'el-galpon'),
(2982, 66, 'El Jardín', 'el-jardin'),
(2983, 66, 'El Naranjo', 'el-naranjo'),
(2984, 66, 'El Potrero', 'el-potrero'),
(2985, 66, 'El Quebrachal', 'el-quebrachal'),
(2986, 66, 'El Tabacal', 'el-tabacal'),
(2987, 66, 'El Tala', 'el-tala'),
(2988, 66, 'El Tunal', 'el-tunal'),
(2989, 66, 'Embarcación', 'embarcacion'),
(2990, 66, 'Gaona', 'gaona'),
(2991, 66, 'General Ballivián', 'general-ballivian'),
(2992, 66, 'General Güemes', 'general-guemes'),
(2993, 66, 'General Mosconi', 'general-mosconi'),
(2994, 66, 'General Pizarro', 'general-pizarro'),
(2995, 66, 'Guachipas', 'guachipas'),
(2996, 66, 'Hickman', 'hickman'),
(2997, 66, 'Hipólito Yrigoyen', 'hipolito-yrigoyen'),
(2998, 66, 'Hito 1', 'hito-1'),
(2999, 66, 'Iruya', 'iruya'),
(3000, 66, 'Isla de Cañas', 'isla-de-canas');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(3001, 66, 'Joaquín V. González', 'joaquin-v-gonzalez'),
(3002, 66, 'La Caldera', 'la-caldera'),
(3003, 66, 'La Candelaria', 'la-candelaria'),
(3004, 66, 'La Merced', 'la-merced'),
(3005, 66, 'La Merced del Encón', 'la-merced-del-encon'),
(3006, 66, 'La Misión', 'la-mision'),
(3007, 66, 'La Poma', 'la-poma'),
(3008, 66, 'La Puerta', 'la-puerta'),
(3009, 66, 'La Silleta', 'la-silleta'),
(3010, 66, 'La Unión', 'la-union'),
(3011, 66, 'La Viña', 'la-vina'),
(3012, 66, 'Las Lajitas', 'las-lajitas'),
(3013, 66, 'Los Blancos', 'los-blancos'),
(3014, 66, 'Los Toldos', 'los-toldos'),
(3015, 66, 'Luis Burela', 'luis-burela'),
(3016, 66, 'Lumbreras', 'lumbreras'),
(3017, 66, 'Macapillo', 'macapillo'),
(3018, 66, 'Misión Chaqueña', 'mision-chaquena'),
(3019, 66, 'Misión Kilómetro 6', 'mision-kilometro-6'),
(3020, 66, 'Misión Tierras Fiscales', 'mision-tierras-fiscales'),
(3021, 66, 'Molinos', 'molinos'),
(3022, 66, 'Nazareno', 'nazareno'),
(3023, 66, 'Nuestra Señora de Talavera', 'nuestra-senora-de-talavera'),
(3024, 66, 'Olacapato', 'olacapato'),
(3025, 66, 'Pacará', 'pacara'),
(3026, 66, 'Padre Lozano', 'padre-lozano'),
(3027, 66, 'Payogasta', 'payogasta'),
(3028, 66, 'Pichanal', 'pichanal'),
(3029, 66, 'Piquete Cabado', 'piquete-cabado'),
(3030, 66, 'Piquirenda', 'piquirenda'),
(3031, 66, 'Pluma de Pato', 'pluma-de-pato'),
(3032, 66, 'Poscaya', 'poscaya'),
(3033, 66, 'Profesor Salvador Mazza', 'profesor-salvador-mazza'),
(3034, 66, 'Pueblo Viejo', 'pueblo-viejo'),
(3035, 66, 'Recaredo', 'recaredo'),
(3036, 66, 'Río del Valle', 'rio-del-valle'),
(3037, 66, 'Río Piedras', 'rio-piedras'),
(3038, 66, 'Rivadavia', 'rivadavia'),
(3039, 66, 'Rosario de la Frontera', 'rosario-de-la-frontera'),
(3040, 66, 'Rosario de Lerma', 'rosario-de-lerma'),
(3041, 66, 'Salta', 'salta'),
(3042, 66, 'San Agustín', 'san-agustin'),
(3043, 66, 'San Antonio de los Cobres', 'san-antonio-de-los-cobres'),
(3044, 66, 'San Carlos', 'san-carlos'),
(3045, 66, 'San Felipe', 'san-felipe'),
(3046, 66, 'San José de Metán (Est. Metán)', 'san-jose-de-metan-est-metan'),
(3047, 66, 'San José de Orquera', 'san-jose-de-orquera'),
(3048, 66, 'San Marcos', 'san-marcos'),
(3049, 66, 'San Ramón de la Nueva Orán', 'san-ramon-de-la-nueva-oran'),
(3050, 66, 'Santa María', 'santa-maria'),
(3051, 66, 'Santa Rosa', 'santa-rosa'),
(3052, 66, 'Santa Rosa de los Pastos Grandes', 'santa-rosa-de-los-pastos-grandes'),
(3053, 66, 'Santa Victoria', 'santa-victoria'),
(3054, 66, 'Santa Victoria Este', 'santa-victoria-este'),
(3055, 66, 'Seclantás', 'seclantas'),
(3056, 66, 'Talapampa', 'talapampa'),
(3057, 66, 'Tartagal', 'tartagal'),
(3058, 66, 'Tobantirenda', 'tobantirenda'),
(3059, 66, 'Tolar Grande', 'tolar-grande'),
(3060, 66, 'Tolloche', 'tolloche'),
(3061, 66, 'Tolombón', 'tolombon'),
(3062, 66, 'Tranquitas', 'tranquitas'),
(3063, 66, 'Urundel', 'urundel'),
(3064, 66, 'Vaqueros', 'vaqueros'),
(3065, 66, 'Villa San Lorenzo', 'villa-san-lorenzo'),
(3066, 66, 'Yacuy', 'yacuy'),
(3067, 70, '9 de Julio', '9-de-julio'),
(3068, 70, 'Alto de Sierra', 'alto-de-sierra'),
(3069, 70, 'Angualasto', 'angualasto'),
(3070, 70, 'Astica', 'astica'),
(3071, 70, 'Balde del Rosario', 'balde-del-rosario'),
(3072, 70, 'Barreal - Villa Pituil', 'barreal-villa-pituil'),
(3073, 70, 'Barrio Municipal', 'barrio-municipal'),
(3074, 70, 'Barrio Ruta 40', 'barrio-ruta-40'),
(3075, 70, 'Barrio Sadop - Bella Vista', 'barrio-sadop-bella-vista'),
(3076, 70, 'Bermejo', 'bermejo'),
(3077, 70, 'Calingasta', 'calingasta'),
(3078, 70, 'Cañada Honda', 'canada-honda'),
(3079, 70, 'Carpintería', 'carpinteria'),
(3080, 70, 'Caucete', 'caucete'),
(3081, 70, 'Chimbas', 'chimbas'),
(3082, 70, 'Chucuma', 'chucuma'),
(3083, 70, 'Cienaguita', 'cienaguita'),
(3084, 70, 'Colonia Fiscal', 'colonia-fiscal'),
(3085, 70, 'Colonia Gutiérrez', 'colonia-gutierrez'),
(3086, 70, 'Divisadero', 'divisadero'),
(3087, 70, 'Dos Acequias', 'dos-acequias'),
(3088, 70, 'El Encón', 'el-encon'),
(3089, 70, 'El Medanito', 'el-medanito'),
(3090, 70, 'El Médano', 'el-medano'),
(3091, 70, 'El Rincón (Albardón)', 'el-rincon-albardon'),
(3092, 70, 'El Rincón (Caucete)', 'el-rincon-caucete'),
(3093, 70, 'Gran China', 'gran-china'),
(3094, 70, 'Huaco', 'huaco'),
(3095, 70, 'Iglesia', 'iglesia'),
(3096, 70, 'La Chimbera', 'la-chimbera'),
(3097, 70, 'La Falda', 'la-falda'),
(3098, 70, 'La Rinconada', 'la-rinconada'),
(3099, 70, 'Las Chacritas', 'las-chacritas'),
(3100, 70, 'Las Flores', 'las-flores'),
(3101, 70, 'Las Lagunas', 'las-lagunas'),
(3102, 70, 'Las Talas', 'las-talas'),
(3103, 70, 'Las Talas - Los Médanos', 'las-talas-los-medanos'),
(3104, 70, 'Las Tapias', 'las-tapias'),
(3105, 70, 'Los Baldecitos', 'los-baldecitos'),
(3106, 70, 'Los Berros', 'los-berros'),
(3107, 70, 'Los Médanos', 'los-medanos'),
(3108, 70, 'Marayes', 'marayes'),
(3109, 70, 'Mogna', 'mogna'),
(3110, 70, 'Niquivil', 'niquivil'),
(3111, 70, 'Pampa Vieja', 'pampa-vieja'),
(3112, 70, 'Pedernal', 'pedernal'),
(3113, 70, 'Pie de Palo', 'pie-de-palo'),
(3114, 70, 'Pismanta', 'pismanta'),
(3115, 70, 'Punta del Médano', 'punta-del-medano'),
(3116, 70, 'Quinto Cuartel', 'quinto-cuartel'),
(3117, 70, 'Rawson', 'rawson'),
(3118, 70, 'Rivadavia', 'rivadavia'),
(3119, 70, 'Rodeo', 'rodeo'),
(3120, 70, 'San Isidro (Jáchal)', 'san-isidro-jachal'),
(3121, 70, 'San Isidro (San Martín)', 'san-isidro-san-martin'),
(3122, 70, 'San José de Jáchal', 'san-jose-de-jachal'),
(3123, 70, 'San Juan', 'san-juan'),
(3124, 70, 'Santa Lucía', 'santa-lucia'),
(3125, 70, 'Tamberías (Calingasta)', 'tamberias-calingasta'),
(3126, 70, 'Tamberías (Jáchal)', 'tamberias-jachal'),
(3127, 70, 'Tudcum', 'tudcum'),
(3128, 70, 'Tupelí', 'tupeli'),
(3129, 70, 'Usno', 'usno'),
(3130, 70, 'Vallecito', 'vallecito'),
(3131, 70, 'Villa Aberastain', 'villa-aberastain'),
(3132, 70, 'Villa Aberastain - La Rinconada', 'villa-aberastain-la-rinconada'),
(3133, 70, 'Villa Barboza', 'villa-barboza'),
(3134, 70, 'Villa Barboza - Villa Nacusi', 'villa-barboza-villa-nacusi'),
(3135, 70, 'Villa Basilio Nievas', 'villa-basilio-nievas'),
(3136, 70, 'Villa Bolaños (Médano de Oro)', 'villa-bolanos-medano-de-oro'),
(3137, 70, 'Villa Borjas', 'villa-borjas'),
(3138, 70, 'Villa Borjas - La Chimbera', 'villa-borjas-la-chimbera'),
(3139, 70, 'Villa Centenario', 'villa-centenario'),
(3140, 70, 'Villa del Salvador', 'villa-del-salvador'),
(3141, 70, 'Villa Dominguito', 'villa-dominguito'),
(3142, 70, 'Villa Don Bosco', 'villa-don-bosco'),
(3143, 70, 'Villa El Salvador', 'villa-el-salvador'),
(3144, 70, 'Villa El Salvador - Villa Sefair', 'villa-el-salvador-villa-sefair'),
(3145, 70, 'Villa El Tango', 'villa-el-tango'),
(3146, 70, 'Villa General San Martín - Campo Afuera', 'villa-general-san-martin-campo-afuera'),
(3147, 70, 'Villa Ibáñez', 'villa-ibanez'),
(3148, 70, 'Villa Independencia', 'villa-independencia'),
(3149, 70, 'Villa Malvinas Argentinas', 'villa-malvinas-argentinas'),
(3150, 70, 'Villa Media Agua', 'villa-media-agua'),
(3151, 70, 'Villa Mercedes', 'villa-mercedes'),
(3152, 70, 'Villa Nacusi', 'villa-nacusi'),
(3153, 70, 'Villa San Agustín', 'villa-san-agustin'),
(3154, 70, 'Villa San Martín', 'villa-san-martin'),
(3155, 70, 'Villa Santa Rosa', 'villa-santa-rosa'),
(3156, 70, 'Villa Sefair', 'villa-sefair'),
(3157, 70, 'Villa Tacú', 'villa-tacu'),
(3158, 74, '5ta Brigada', '5ta-brigada'),
(3159, 74, 'Alto Pelado', 'alto-pelado'),
(3160, 74, 'Alto Pencoso', 'alto-pencoso'),
(3161, 74, 'Anchorena', 'anchorena'),
(3162, 74, 'Arizona', 'arizona'),
(3163, 74, 'Bagual', 'bagual'),
(3164, 74, 'Balde', 'balde'),
(3165, 74, 'Batavia', 'batavia'),
(3166, 74, 'Beazley', 'beazley'),
(3167, 74, 'Buena Esperanza', 'buena-esperanza'),
(3168, 74, 'Candelaria', 'candelaria'),
(3169, 74, 'Carolina', 'carolina'),
(3170, 74, 'Carpintería', 'carpinteria'),
(3171, 74, 'Cazador', 'cazador'),
(3172, 74, 'Cerro Colorado', 'cerro-colorado'),
(3173, 74, 'Chosmes', 'chosmes'),
(3174, 74, 'Concarán', 'concaran'),
(3175, 74, 'Cortaderas', 'cortaderas'),
(3176, 74, 'Country Club Los Caldenes', 'country-club-los-caldenes'),
(3177, 74, 'Cruz de Piedra', 'cruz-de-piedra'),
(3178, 74, 'Desaguadero', 'desaguadero'),
(3179, 74, 'El Chorrillo', 'el-chorrillo'),
(3180, 74, 'El Trapiche', 'el-trapiche'),
(3181, 74, 'El Volcán', 'el-volcan'),
(3182, 74, 'Estancia Grande', 'estancia-grande'),
(3183, 74, 'Fortín El Patria', 'fortin-el-patria'),
(3184, 74, 'Fortuna', 'fortuna'),
(3185, 74, 'Fraga', 'fraga'),
(3186, 74, 'Jarilla', 'jarilla'),
(3187, 74, 'Juan Jorba', 'juan-jorba'),
(3188, 74, 'Juan Llerena', 'juan-llerena'),
(3189, 74, 'Juana Koslay', 'juana-koslay'),
(3190, 74, 'Justo Daract', 'justo-daract'),
(3191, 74, 'La Calera', 'la-calera'),
(3192, 74, 'La Florida', 'la-florida'),
(3193, 74, 'La Maroma', 'la-maroma'),
(3194, 74, 'La Punilla', 'la-punilla'),
(3195, 74, 'La Punta', 'la-punta'),
(3196, 74, 'La Ribera', 'la-ribera'),
(3197, 74, 'La Toma', 'la-toma'),
(3198, 74, 'La Vertiente', 'la-vertiente'),
(3199, 74, 'Lafinur', 'lafinur'),
(3200, 74, 'Las Aguadas', 'las-aguadas');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(3201, 74, 'Las Chacras (Juan Martín de Pueyrredón)', 'las-chacras-juan-martin-de-pueyrredon'),
(3202, 74, 'Las Chacras (Libertador General San Martín)', 'las-chacras-libertador-general-san-martin'),
(3203, 74, 'Las Lagunas', 'las-lagunas'),
(3204, 74, 'Lavaisse', 'lavaisse'),
(3205, 74, 'Leandro N. Alem', 'leandro-n-alem'),
(3206, 74, 'Los Cajones', 'los-cajones'),
(3207, 74, 'Los Molles', 'los-molles'),
(3208, 74, 'Los Overos', 'los-overos'),
(3209, 74, 'Luján', 'lujan'),
(3210, 74, 'Martín de Loyola', 'martin-de-loyola'),
(3211, 74, 'Merlo', 'merlo'),
(3212, 74, 'Mosmota', 'mosmota'),
(3213, 74, 'Nación Ranquel', 'nacion-ranquel'),
(3214, 74, 'Nahuel Mapá', 'nahuel-mapa'),
(3215, 74, 'Naschel', 'naschel'),
(3216, 74, 'Navia', 'navia'),
(3217, 74, 'Nogolí', 'nogoli'),
(3218, 74, 'Nueva Galia', 'nueva-galia'),
(3219, 74, 'Papagayos', 'papagayos'),
(3220, 74, 'Paso Grande', 'paso-grande'),
(3221, 74, 'Potrerillo', 'potrerillo'),
(3222, 74, 'Potrero de los Funes', 'potrero-de-los-funes'),
(3223, 74, 'Quines', 'quines'),
(3224, 74, 'Renca', 'renca'),
(3225, 74, 'Riocito', 'riocito'),
(3226, 74, 'Saladillo', 'saladillo'),
(3227, 74, 'Salinas del Bebedero', 'salinas-del-bebedero'),
(3228, 74, 'San Francisco del Monte de Oro', 'san-francisco-del-monte-de-oro'),
(3229, 74, 'San Jerónimo', 'san-jeronimo'),
(3230, 74, 'San José del Morro', 'san-jose-del-morro'),
(3231, 74, 'San Luis', 'san-luis'),
(3232, 74, 'San Martín', 'san-martin'),
(3233, 74, 'San Pablo', 'san-pablo'),
(3234, 74, 'San Roque', 'san-roque'),
(3235, 74, 'Santa Rosa del Conlara', 'santa-rosa-del-conlara'),
(3236, 74, 'Talita', 'talita'),
(3237, 74, 'Tilisarao', 'tilisarao'),
(3238, 74, 'Unión', 'union'),
(3239, 74, 'Villa de la Quebrada', 'villa-de-la-quebrada'),
(3240, 74, 'Villa de Praga', 'villa-de-praga'),
(3241, 74, 'Villa del Carmen', 'villa-del-carmen'),
(3242, 74, 'Villa General Roca', 'villa-general-roca'),
(3243, 74, 'Villa Larca', 'villa-larca'),
(3244, 74, 'Villa Mercedes', 'villa-mercedes'),
(3245, 74, 'Villa Reynolds', 'villa-reynolds'),
(3246, 74, 'Villa Salles', 'villa-salles'),
(3247, 74, 'Zanjitas', 'zanjitas'),
(3248, 78, '28 de Noviembre', '28-de-noviembre'),
(3249, 78, 'Bajo Caracoles', 'bajo-caracoles'),
(3250, 78, 'Caleta Olivia', 'caleta-olivia'),
(3251, 78, 'Cañadón Seco', 'canadon-seco'),
(3252, 78, 'Comandante Luis Piedrabuena', 'comandante-luis-piedrabuena'),
(3253, 78, 'El Calafate', 'el-calafate'),
(3254, 78, 'El Chaltén', 'el-chalten'),
(3255, 78, 'El Turbio', 'el-turbio'),
(3256, 78, 'Fitz Roy', 'fitz-roy'),
(3257, 78, 'Gobernador Gregores', 'gobernador-gregores'),
(3258, 78, 'Hipólito Yrigoyen', 'hipolito-yrigoyen'),
(3259, 78, 'Jaramillo', 'jaramillo'),
(3260, 78, 'Julia Dufour', 'julia-dufour'),
(3261, 78, 'Koluel Kaike', 'koluel-kaike'),
(3262, 78, 'Las Heras', 'las-heras'),
(3263, 78, 'Los Antiguos', 'los-antiguos'),
(3264, 78, 'Perito Moreno', 'perito-moreno'),
(3265, 78, 'Pico Truncado', 'pico-truncado'),
(3266, 78, 'Puerto Deseado', 'puerto-deseado'),
(3267, 78, 'Puerto San Julián', 'puerto-san-julian'),
(3268, 78, 'Puerto Santa Cruz', 'puerto-santa-cruz'),
(3269, 78, 'Río Gallegos', 'rio-gallegos'),
(3270, 78, 'Rospentek', 'rospentek'),
(3271, 78, 'Tellier', 'tellier'),
(3272, 78, 'Tres Lagos', 'tres-lagos'),
(3273, 78, 'Yacimientos Río Turbio', 'yacimientos-rio-turbio'),
(3274, 82, 'Aarón Castellanos', 'aaron-castellanos'),
(3275, 82, 'Acébal', 'acebal'),
(3276, 82, 'Aguará Grande', 'aguara-grande'),
(3277, 82, 'Albarellos', 'albarellos'),
(3278, 82, 'Alcorta', 'alcorta'),
(3279, 82, 'Aldao (Castellanos)', 'aldao-castellanos'),
(3280, 82, 'Aldao (San Lorenzo)', 'aldao-san-lorenzo'),
(3281, 82, 'Alejandra', 'alejandra'),
(3282, 82, 'Álvarez', 'alvarez'),
(3283, 82, 'Alvear', 'alvear'),
(3284, 82, 'Ambrosetti', 'ambrosetti'),
(3285, 82, 'Amenábar', 'amenabar'),
(3286, 82, 'Angel Gallardo', 'angel-gallardo'),
(3287, 82, 'Angélica', 'angelica'),
(3288, 82, 'Angeloni', 'angeloni'),
(3289, 82, 'Arbilla', 'arbilla'),
(3290, 82, 'Arequito', 'arequito'),
(3291, 82, 'Arminda', 'arminda'),
(3292, 82, 'Armstrong', 'armstrong'),
(3293, 82, 'Arocena', 'arocena'),
(3294, 82, 'Arroyo Aguiar', 'arroyo-aguiar'),
(3295, 82, 'Arroyo Ceibal', 'arroyo-ceibal'),
(3296, 82, 'Arroyo Leyes', 'arroyo-leyes'),
(3297, 82, 'Arroyo Seco', 'arroyo-seco'),
(3298, 82, 'Arrufo', 'arrufo'),
(3299, 82, 'Arteaga', 'arteaga'),
(3300, 82, 'Ataliva', 'ataliva'),
(3301, 82, 'Aurelia', 'aurelia'),
(3302, 82, 'Avellaneda', 'avellaneda'),
(3303, 82, 'Balneario La Verde', 'balneario-la-verde'),
(3304, 82, 'Balneario Monje', 'balneario-monje'),
(3305, 82, 'Barrancas', 'barrancas'),
(3306, 82, 'Barrio Arroyo del Medio', 'barrio-arroyo-del-medio'),
(3307, 82, 'Barrio Caima', 'barrio-caima'),
(3308, 82, 'Barrio Cicarelli', 'barrio-cicarelli'),
(3309, 82, 'Barrio El Pacaá - Barrio Comipini', 'barrio-el-pacaa-barrio-comipini'),
(3310, 82, 'Barrio Mitre', 'barrio-mitre'),
(3311, 82, 'Barrios Acapulco y Veracruz', 'barrios-acapulco-y-veracruz'),
(3312, 82, 'Bauer y Sigel', 'bauer-y-sigel'),
(3313, 82, 'Bella Italia', 'bella-italia'),
(3314, 82, 'Beravebú', 'beravebu'),
(3315, 82, 'Berna', 'berna'),
(3316, 82, 'Bernardo de Irigoyen', 'bernardo-de-irigoyen'),
(3317, 82, 'Bigand', 'bigand'),
(3318, 82, 'Bombal', 'bombal'),
(3319, 82, 'Bouquet', 'bouquet'),
(3320, 82, 'Bustinza', 'bustinza'),
(3321, 82, 'Cabal', 'cabal'),
(3322, 82, 'Cacique Ariacaiquín', 'cacique-ariacaiquin'),
(3323, 82, 'Cafferata', 'cafferata'),
(3324, 82, 'Calchaquí', 'calchaqui'),
(3325, 82, 'Campo Andino', 'campo-andino'),
(3326, 82, 'Cañada de Gómez', 'canada-de-gomez'),
(3327, 82, 'Cañada del Ucle', 'canada-del-ucle'),
(3328, 82, 'Cañada Ombú', 'canada-ombu'),
(3329, 82, 'Cañada Rica', 'canada-rica'),
(3330, 82, 'Cañada Rosquín', 'canada-rosquin'),
(3331, 82, 'Candioti', 'candioti'),
(3332, 82, 'Capitán Bermúdez', 'capitan-bermudez'),
(3333, 82, 'Capivara', 'capivara'),
(3334, 82, 'Carcarañá', 'carcarana'),
(3335, 82, 'Carlos Pellegrini', 'carlos-pellegrini'),
(3336, 82, 'Carmen', 'carmen'),
(3337, 82, 'Carmen del Sauce', 'carmen-del-sauce'),
(3338, 82, 'Carreras', 'carreras'),
(3339, 82, 'Carrizales', 'carrizales'),
(3340, 82, 'Casalegno', 'casalegno'),
(3341, 82, 'Casas', 'casas'),
(3342, 82, 'Casilda', 'casilda'),
(3343, 82, 'Castelar', 'castelar'),
(3344, 82, 'Castellanos', 'castellanos'),
(3345, 82, 'Cavour', 'cavour'),
(3346, 82, 'Cayastá', 'cayasta'),
(3347, 82, 'Cayastacito', 'cayastacito'),
(3348, 82, 'Centeno', 'centeno'),
(3349, 82, 'Cepeda', 'cepeda'),
(3350, 82, 'Ceres', 'ceres'),
(3351, 82, 'Chabas', 'chabas'),
(3352, 82, 'Chañar Ladeado', 'chanar-ladeado'),
(3353, 82, 'Chapuy', 'chapuy'),
(3354, 82, 'Chovet', 'chovet'),
(3355, 82, 'Christophersen', 'christophersen'),
(3356, 82, 'Classon', 'classon'),
(3357, 82, 'Colmena', 'colmena'),
(3358, 82, 'Colonia Ana', 'colonia-ana'),
(3359, 82, 'Colonia Belgrano', 'colonia-belgrano'),
(3360, 82, 'Colonia Bicha', 'colonia-bicha'),
(3361, 82, 'Colonia Bossi', 'colonia-bossi'),
(3362, 82, 'Colonia Cello', 'colonia-cello'),
(3363, 82, 'Colonia Dolores', 'colonia-dolores'),
(3364, 82, 'Colonia Durán', 'colonia-duran'),
(3365, 82, 'Colonia Margarita', 'colonia-margarita'),
(3366, 82, 'Colonia Médici', 'colonia-medici'),
(3367, 82, 'Colonia Raquel', 'colonia-raquel'),
(3368, 82, 'Colonia Rosa', 'colonia-rosa'),
(3369, 82, 'Constanza', 'constanza'),
(3370, 82, 'Coronda', 'coronda'),
(3371, 82, 'Coronel Arnold', 'coronel-arnold'),
(3372, 82, 'Coronel Bogado', 'coronel-bogado'),
(3373, 82, 'Coronel Fraga', 'coronel-fraga'),
(3374, 82, 'Coronel Rodolfo S. Domínguez', 'coronel-rodolfo-s-dominguez'),
(3375, 82, 'Correa', 'correa'),
(3376, 82, 'Crispi', 'crispi'),
(3377, 82, 'Cuatro Esquinas', 'cuatro-esquinas'),
(3378, 82, 'Cululú', 'cululu'),
(3379, 82, 'Curupaytí', 'curupayti'),
(3380, 82, 'Desvío Arijón', 'desvio-arijon'),
(3381, 82, 'Díaz', 'diaz'),
(3382, 82, 'Diego de Alvear', 'diego-de-alvear'),
(3383, 82, 'Egusquiza', 'egusquiza'),
(3384, 82, 'El Araza', 'el-araza'),
(3385, 82, 'El Caramelo', 'el-caramelo'),
(3386, 82, 'El Rabón', 'el-rabon'),
(3387, 82, 'El Trébol', 'el-trebol'),
(3388, 82, 'Elisa', 'elisa'),
(3389, 82, 'Elortondo', 'elortondo'),
(3390, 82, 'Emilia', 'emilia'),
(3391, 82, 'Empalme San Carlos', 'empalme-san-carlos'),
(3392, 82, 'Empalme Villa Constitución', 'empalme-villa-constitucion'),
(3393, 82, 'Esmeralda', 'esmeralda'),
(3394, 82, 'Esperanza', 'esperanza'),
(3395, 82, 'Estación Clucellas', 'estacion-clucellas'),
(3396, 82, 'Estación Presidente Roca', 'estacion-presidente-roca'),
(3397, 82, 'Estación Saguier', 'estacion-saguier'),
(3398, 82, 'Esteban Rams', 'esteban-rams'),
(3399, 82, 'Esther', 'esther'),
(3400, 82, 'Eusebia y Carolina', 'eusebia-y-carolina');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(3401, 82, 'Eustolia', 'eustolia'),
(3402, 82, 'Felicia', 'felicia'),
(3403, 82, 'Fighiera', 'fighiera'),
(3404, 82, 'Firmat', 'firmat'),
(3405, 82, 'Florencia', 'florencia'),
(3406, 82, 'Fortín Olmos', 'fortin-olmos'),
(3407, 82, 'Franck', 'franck'),
(3408, 82, 'Fray Luis Beltrán', 'fray-luis-beltran'),
(3409, 82, 'Frontera', 'frontera'),
(3410, 82, 'Fuentes', 'fuentes'),
(3411, 82, 'Funes', 'funes'),
(3412, 82, 'Gaboto', 'gaboto'),
(3413, 82, 'Gálvez', 'galvez'),
(3414, 82, 'Garabato', 'garabato'),
(3415, 82, 'Garibaldi', 'garibaldi'),
(3416, 82, 'Gato Colorado', 'gato-colorado'),
(3417, 82, 'General Gelly', 'general-gelly'),
(3418, 82, 'General Lagos', 'general-lagos'),
(3419, 82, 'Gessler', 'gessler'),
(3420, 82, 'Gobernador Crespo', 'gobernador-crespo'),
(3421, 82, 'Gödeken', 'godeken'),
(3422, 82, 'Godoy', 'godoy'),
(3423, 82, 'Golondrina', 'golondrina'),
(3424, 82, 'Granadero Baigorria', 'granadero-baigorria'),
(3425, 82, 'Gregoria Pérez de Denis', 'gregoria-perez-de-denis'),
(3426, 82, 'Grutly', 'grutly'),
(3427, 82, 'Guadalupe Norte', 'guadalupe-norte'),
(3428, 82, 'Helvecia', 'helvecia'),
(3429, 82, 'Hersilia', 'hersilia'),
(3430, 82, 'Hipatía', 'hipatia'),
(3431, 82, 'Huanqueros', 'huanqueros'),
(3432, 82, 'Hughes', 'hughes'),
(3433, 82, 'Humberto Primo', 'humberto-primo'),
(3434, 82, 'Humboldt', 'humboldt'),
(3435, 82, 'Ibarlucea', 'ibarlucea'),
(3436, 82, 'Ingeniero Chanourdie', 'ingeniero-chanourdie'),
(3437, 82, 'Intiyaco', 'intiyaco'),
(3438, 82, 'Irigoyen', 'irigoyen'),
(3439, 82, 'Jacinto L. Aráuz', 'jacinto-l-arauz'),
(3440, 82, 'Josefina', 'josefina'),
(3441, 82, 'Juan B. Molina', 'juan-b-molina'),
(3442, 82, 'Juncal', 'juncal'),
(3443, 82, 'Kilómetro 101', 'kilometro-101'),
(3444, 82, 'Kilómetro 115', 'kilometro-115'),
(3445, 82, 'La Brava', 'la-brava'),
(3446, 82, 'La Cabral', 'la-cabral'),
(3447, 82, 'La Chispa', 'la-chispa'),
(3448, 82, 'La Criolla', 'la-criolla'),
(3449, 82, 'La Gallareta', 'la-gallareta'),
(3450, 82, 'La Isleta', 'la-isleta'),
(3451, 82, 'La Pelada', 'la-pelada'),
(3452, 82, 'La Penca y Caraguatá', 'la-penca-y-caraguata'),
(3453, 82, 'La Rubia', 'la-rubia'),
(3454, 82, 'La Sarita', 'la-sarita'),
(3455, 82, 'La Vanguardia', 'la-vanguardia'),
(3456, 82, 'Labordeboy', 'labordeboy'),
(3457, 82, 'Laguna Paiva', 'laguna-paiva'),
(3458, 82, 'Landeta', 'landeta'),
(3459, 82, 'Lanteri', 'lanteri'),
(3460, 82, 'Larguía', 'larguia'),
(3461, 82, 'Larrechea', 'larrechea'),
(3462, 82, 'Las Avispas', 'las-avispas'),
(3463, 82, 'Las Bandurrias', 'las-bandurrias'),
(3464, 82, 'Las Garzas', 'las-garzas'),
(3465, 82, 'Las Palmeras', 'las-palmeras'),
(3466, 82, 'Las Parejas', 'las-parejas'),
(3467, 82, 'Las Petacas', 'las-petacas'),
(3468, 82, 'Las Rosas', 'las-rosas'),
(3469, 82, 'Las Toscas', 'las-toscas'),
(3470, 82, 'Las Tunas', 'las-tunas'),
(3471, 82, 'Lazzarino', 'lazzarino'),
(3472, 82, 'Lehmann', 'lehmann'),
(3473, 82, 'Llambi Campbell', 'llambi-campbell'),
(3474, 82, 'Logroño', 'logrono'),
(3475, 82, 'Loma Alta', 'loma-alta'),
(3476, 82, 'López', 'lopez'),
(3477, 82, 'Los Amores', 'los-amores'),
(3478, 82, 'Los Cardos', 'los-cardos'),
(3479, 82, 'Los Laureles', 'los-laureles'),
(3480, 82, 'Los Molinos', 'los-molinos'),
(3481, 82, 'Los Muchachos - La Alborada', 'los-muchachos-la-alborada'),
(3482, 82, 'Los Nogales', 'los-nogales'),
(3483, 82, 'Los Quirquinchos', 'los-quirquinchos'),
(3484, 82, 'Los Zapallos', 'los-zapallos'),
(3485, 82, 'Lucio V. López', 'lucio-v-lopez'),
(3486, 82, 'Luis Palacios', 'luis-palacios'),
(3487, 82, 'Maciel', 'maciel'),
(3488, 82, 'Maggiolo', 'maggiolo'),
(3489, 82, 'Malabrigo', 'malabrigo'),
(3490, 82, 'Marcelino Escalada', 'marcelino-escalada'),
(3491, 82, 'Margarita', 'margarita'),
(3492, 82, 'María Juana', 'maria-juana'),
(3493, 82, 'María Luisa', 'maria-luisa'),
(3494, 82, 'María Susana', 'maria-susana'),
(3495, 82, 'María Teresa', 'maria-teresa'),
(3496, 82, 'Matilde', 'matilde'),
(3497, 82, 'Máximo Paz', 'maximo-paz'),
(3498, 82, 'Melincué', 'melincue'),
(3499, 82, 'Miguel Torres', 'miguel-torres'),
(3500, 82, 'Moisés Ville', 'moises-ville'),
(3501, 82, 'Monigotes', 'monigotes'),
(3502, 82, 'Monje', 'monje'),
(3503, 82, 'Monte Flores', 'monte-flores'),
(3504, 82, 'Monte Vera', 'monte-vera'),
(3505, 82, 'Montefiore', 'montefiore'),
(3506, 82, 'Montes de Oca', 'montes-de-oca'),
(3507, 82, 'Murphy', 'murphy'),
(3508, 82, 'Ñanducita', 'nanducita'),
(3509, 82, 'Naré', 'nare'),
(3510, 82, 'Nelson', 'nelson'),
(3511, 82, 'Nueva Lehmann', 'nueva-lehmann'),
(3512, 82, 'Nuevo Torino', 'nuevo-torino'),
(3513, 82, 'Oliveros', 'oliveros'),
(3514, 82, 'Palacios', 'palacios'),
(3515, 82, 'Paraje 29', 'paraje-29'),
(3516, 82, 'Paraje Chaco Chico', 'paraje-chaco-chico'),
(3517, 82, 'Paraje La Costa', 'paraje-la-costa'),
(3518, 82, 'Paraje San Manuel', 'paraje-san-manuel'),
(3519, 82, 'Pavón', 'pavon'),
(3520, 82, 'Pavón Arriba', 'pavon-arriba'),
(3521, 82, 'Pedro Gómez Cello', 'pedro-gomez-cello'),
(3522, 82, 'Pérez', 'perez'),
(3523, 82, 'Peyrano', 'peyrano'),
(3524, 82, 'Piamonte', 'piamonte'),
(3525, 82, 'Pilar', 'pilar'),
(3526, 82, 'Piñero', 'pinero'),
(3527, 82, 'Plaza Clucellas', 'plaza-clucellas'),
(3528, 82, 'Plaza Matilde', 'plaza-matilde'),
(3529, 82, 'Plaza Saguier', 'plaza-saguier'),
(3530, 82, 'Pozo Borrado', 'pozo-borrado'),
(3531, 82, 'Pozo de los Indios', 'pozo-de-los-indios'),
(3532, 82, 'Presidente Roca', 'presidente-roca'),
(3533, 82, 'Progreso', 'progreso'),
(3534, 82, 'Providencia', 'providencia'),
(3535, 82, 'Pueblo Andino', 'pueblo-andino'),
(3536, 82, 'Pueblo Esther', 'pueblo-esther'),
(3537, 82, 'Pueblo Marini', 'pueblo-marini'),
(3538, 82, 'Pueblo Muñóz', 'pueblo-munoz'),
(3539, 82, 'Pueblo Santa Lucía', 'pueblo-santa-lucia'),
(3540, 82, 'Pueblo Uranga', 'pueblo-uranga'),
(3541, 82, 'Puerto Aragón', 'puerto-aragon'),
(3542, 82, 'Puerto Arroyo Seco', 'puerto-arroyo-seco'),
(3543, 82, 'Puerto General San Martín', 'puerto-general-san-martin'),
(3544, 82, 'Puerto Reconquista', 'puerto-reconquista'),
(3545, 82, 'Pujato', 'pujato'),
(3546, 82, 'Rafaela', 'rafaela'),
(3547, 82, 'Ramayón', 'ramayon'),
(3548, 82, 'Ramona', 'ramona'),
(3549, 82, 'Reconquista', 'reconquista'),
(3550, 82, 'Recreo', 'recreo'),
(3551, 82, 'Ricardone', 'ricardone'),
(3552, 82, 'Rincón Norte', 'rincon-norte'),
(3553, 82, 'Rincón Potrero', 'rincon-potrero'),
(3554, 82, 'Roldán', 'roldan'),
(3555, 82, 'Romang', 'romang'),
(3556, 82, 'Rosario', 'rosario'),
(3557, 82, 'Rueda', 'rueda'),
(3558, 82, 'Rufino', 'rufino'),
(3559, 82, 'Sa Pereyra', 'sa-pereyra'),
(3560, 82, 'Saladero Mariano Cabal', 'saladero-mariano-cabal'),
(3561, 82, 'Salto Grande', 'salto-grande'),
(3562, 82, 'San Agustín', 'san-agustin'),
(3563, 82, 'San Antonio', 'san-antonio'),
(3564, 82, 'San Antonio de Obligado', 'san-antonio-de-obligado'),
(3565, 82, 'San Bernardo (9 de Julio)', 'san-bernardo-9-de-julio'),
(3566, 82, 'San Bernardo (San Justo)', 'san-bernardo-san-justo'),
(3567, 82, 'San Carlos Centro', 'san-carlos-centro'),
(3568, 82, 'San Carlos Norte', 'san-carlos-norte'),
(3569, 82, 'San Carlos Sud', 'san-carlos-sud'),
(3570, 82, 'San Cristóbal', 'san-cristobal'),
(3571, 82, 'San Eduardo', 'san-eduardo'),
(3572, 82, 'San Eugenio', 'san-eugenio'),
(3573, 82, 'San Fabián', 'san-fabian'),
(3574, 82, 'San Francisco de Santa Fe', 'san-francisco-de-santa-fe'),
(3575, 82, 'San Genaro', 'san-genaro'),
(3576, 82, 'San Gregorio', 'san-gregorio'),
(3577, 82, 'San Guillermo', 'san-guillermo'),
(3578, 82, 'San Javier', 'san-javier'),
(3579, 82, 'San Jerónimo del Sauce', 'san-jeronimo-del-sauce'),
(3580, 82, 'San Jerónimo Norte', 'san-jeronimo-norte'),
(3581, 82, 'San Jerónimo Sud', 'san-jeronimo-sud'),
(3582, 82, 'San Jorge', 'san-jorge'),
(3583, 82, 'San José de la Esquina', 'san-jose-de-la-esquina'),
(3584, 82, 'San José del Rincón', 'san-jose-del-rincon'),
(3585, 82, 'San Justo', 'san-justo'),
(3586, 82, 'San Lorenzo', 'san-lorenzo'),
(3587, 82, 'San Mariano', 'san-mariano'),
(3588, 82, 'San Martín de las Escobas', 'san-martin-de-las-escobas'),
(3589, 82, 'San Martín Norte', 'san-martin-norte'),
(3590, 82, 'San Vicente', 'san-vicente'),
(3591, 82, 'Sancti Spiritu', 'sancti-spiritu'),
(3592, 82, 'Sanford', 'sanford'),
(3593, 82, 'Santa Clara de Buena Vista', 'santa-clara-de-buena-vista'),
(3594, 82, 'Santa Clara de Saguier', 'santa-clara-de-saguier'),
(3595, 82, 'Santa Fe', 'santa-fe'),
(3596, 82, 'Santa Isabel', 'santa-isabel'),
(3597, 82, 'Santa Margarita', 'santa-margarita'),
(3598, 82, 'Santa Rosa de Calchines', 'santa-rosa-de-calchines'),
(3599, 82, 'Santa Teresa', 'santa-teresa'),
(3600, 82, 'Santo Domingo', 'santo-domingo');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(3601, 82, 'Santo Tomé', 'santo-tome'),
(3602, 82, 'Santurce', 'santurce'),
(3603, 82, 'Sargento Cabral', 'sargento-cabral'),
(3604, 82, 'Sarmiento', 'sarmiento'),
(3605, 82, 'Sastre', 'sastre'),
(3606, 82, 'Sauce Viejo', 'sauce-viejo'),
(3607, 82, 'Serodino', 'serodino'),
(3608, 82, 'Silva', 'silva'),
(3609, 82, 'Soldini', 'soldini'),
(3610, 82, 'Soledad', 'soledad'),
(3611, 82, 'Stephenson', 'stephenson'),
(3612, 82, 'Suardi', 'suardi'),
(3613, 82, 'Sunchales', 'sunchales'),
(3614, 82, 'Susana', 'susana'),
(3615, 82, 'Tacuarendí', 'tacuarendi'),
(3616, 82, 'Tacural', 'tacural'),
(3617, 82, 'Tartagal', 'tartagal'),
(3618, 82, 'Teodelina', 'teodelina'),
(3619, 82, 'Theobald', 'theobald'),
(3620, 82, 'Timbúes', 'timbues'),
(3621, 82, 'Toba', 'toba'),
(3622, 82, 'Tortugas', 'tortugas'),
(3623, 82, 'Tostado', 'tostado'),
(3624, 82, 'Totoras', 'totoras'),
(3625, 82, 'Traill', 'traill'),
(3626, 82, 'Venado Tuerto', 'venado-tuerto'),
(3627, 82, 'Vera', 'vera'),
(3628, 82, 'Vera y Pintado', 'vera-y-pintado'),
(3629, 82, 'Videla', 'videla'),
(3630, 82, 'Vila', 'vila'),
(3631, 82, 'Villa Adelina', 'villa-adelina'),
(3632, 82, 'Villa Amelia', 'villa-amelia'),
(3633, 82, 'Villa Ana', 'villa-ana'),
(3634, 82, 'Villa Cañás', 'villa-canas'),
(3635, 82, 'Villa Constitución', 'villa-constitucion'),
(3636, 82, 'Villa del Plata', 'villa-del-plata'),
(3637, 82, 'Villa Eloísa', 'villa-eloisa'),
(3638, 82, 'Villa Elvira', 'villa-elvira'),
(3639, 82, 'Villa Gobernador Gálvez', 'villa-gobernador-galvez'),
(3640, 82, 'Villa Guillermina', 'villa-guillermina'),
(3641, 82, 'Villa Josefina', 'villa-josefina'),
(3642, 82, 'Villa La Rivera (Oliveros)', 'villa-la-rivera-oliveros'),
(3643, 82, 'Villa La Rivera (Pueblo Andino)', 'villa-la-rivera-pueblo-andino'),
(3644, 82, 'Villa Laura', 'villa-laura'),
(3645, 82, 'Villa Minetti', 'villa-minetti'),
(3646, 82, 'Villa Mugueta', 'villa-mugueta'),
(3647, 82, 'Villa Ocampo', 'villa-ocampo'),
(3648, 82, 'Villa San José', 'villa-san-jose'),
(3649, 82, 'Villa Saralegui', 'villa-saralegui'),
(3650, 82, 'Villa Trinidad', 'villa-trinidad'),
(3651, 82, 'Villada', 'villada'),
(3652, 82, 'Virginia', 'virginia'),
(3653, 82, 'Wheelwright', 'wheelwright'),
(3654, 82, 'Wildermuth', 'wildermuth'),
(3655, 82, 'Zavalla', 'zavalla'),
(3656, 82, 'Zenón Pereyra', 'zenon-pereyra'),
(3657, 86, 'Abra Grande', 'abra-grande'),
(3658, 86, 'Aerolito', 'aerolito'),
(3659, 86, 'Alhuampa', 'alhuampa'),
(3660, 86, 'Añatuya', 'anatuya'),
(3661, 86, 'Ancaján', 'ancajan'),
(3662, 86, 'Antajé', 'antaje'),
(3663, 86, 'Ardiles', 'ardiles'),
(3664, 86, 'Argentina', 'argentina'),
(3665, 86, 'Árraga', 'arraga'),
(3666, 86, 'Averías', 'averias'),
(3667, 86, 'Bandera', 'bandera'),
(3668, 86, 'Bandera Bajada', 'bandera-bajada'),
(3669, 86, 'Beltrán', 'beltran'),
(3670, 86, 'Brea Pozo', 'brea-pozo'),
(3671, 86, 'Campo Gallo', 'campo-gallo'),
(3672, 86, 'Cañada Escobar', 'canada-escobar'),
(3673, 86, 'Casares', 'casares'),
(3674, 86, 'Caspi Corral', 'caspi-corral'),
(3675, 86, 'Chauchillas', 'chauchillas'),
(3676, 86, 'Chaupi Pozo', 'chaupi-pozo'),
(3677, 86, 'Chilca Juliana', 'chilca-juliana'),
(3678, 86, 'Choya', 'choya'),
(3679, 86, 'Clodomira', 'clodomira'),
(3680, 86, 'Colonia Alpina', 'colonia-alpina'),
(3681, 86, 'Colonia Dora', 'colonia-dora'),
(3682, 86, 'Colonia El Simbolar', 'colonia-el-simbolar'),
(3683, 86, 'Colonia San Juan', 'colonia-san-juan'),
(3684, 86, 'Colonia Tinco', 'colonia-tinco'),
(3685, 86, 'Coronel Manuel L. Rico', 'coronel-manuel-l-rico'),
(3686, 86, 'Cuatro Bocas', 'cuatro-bocas'),
(3687, 86, 'Donadeu', 'donadeu'),
(3688, 86, 'El 49', 'el-49'),
(3689, 86, 'El Arenal', 'el-arenal'),
(3690, 86, 'El Bobadal', 'el-bobadal'),
(3691, 86, 'El Caburé', 'el-cabure'),
(3692, 86, 'El Charco (Jiménez)', 'el-charco-jimenez'),
(3693, 86, 'El Charco (Río Hondo)', 'el-charco-rio-hondo'),
(3694, 86, 'El Colorado', 'el-colorado'),
(3695, 86, 'El Crucero', 'el-crucero'),
(3696, 86, 'El Cuadrado', 'el-cuadrado'),
(3697, 86, 'El Deán', 'el-dean'),
(3698, 86, 'El Mojón (Capital)', 'el-mojon-capital'),
(3699, 86, 'El Mojón (Pellegrini)', 'el-mojon-pellegrini'),
(3700, 86, 'El Rincón', 'el-rincon'),
(3701, 86, 'El Sauzal', 'el-sauzal'),
(3702, 86, 'El Zanjón', 'el-zanjon'),
(3703, 86, 'Estación Atamisqui', 'estacion-atamisqui'),
(3704, 86, 'Estación La Punta', 'estacion-la-punta'),
(3705, 86, 'Estación Robles', 'estacion-robles'),
(3706, 86, 'Estación Taboada', 'estacion-taboada'),
(3707, 86, 'Estación Tacañitas', 'estacion-tacanitas'),
(3708, 86, 'Fernández', 'fernandez'),
(3709, 86, 'Fortín Inca', 'fortin-inca'),
(3710, 86, 'Frías', 'frias'),
(3711, 86, 'Garza', 'garza'),
(3712, 86, 'Gramilla (Jiménez)', 'gramilla-jimenez'),
(3713, 86, 'Gramilla (Río Hondo)', 'gramilla-rio-hondo'),
(3714, 86, 'Guardia Escolta', 'guardia-escolta'),
(3715, 86, 'Hasse', 'hasse'),
(3716, 86, 'Hernán Mejía Miraval', 'hernan-mejia-miraval'),
(3717, 86, 'Herrera', 'herrera'),
(3718, 86, 'Huyamampa', 'huyamampa'),
(3719, 86, 'Icaño', 'icano'),
(3720, 86, 'Ingeniero Forres', 'ingeniero-forres'),
(3721, 86, 'Isca Yacu', 'isca-yacu'),
(3722, 86, 'Isca Yacu Semaul', 'isca-yacu-semaul'),
(3723, 86, 'La Aurora', 'la-aurora'),
(3724, 86, 'La Banda', 'la-banda'),
(3725, 86, 'La Cañada', 'la-canada'),
(3726, 86, 'La Dársena', 'la-darsena'),
(3727, 86, 'La Nena', 'la-nena'),
(3728, 86, 'La Nueva Donosa', 'la-nueva-donosa'),
(3729, 86, 'Laprida', 'laprida'),
(3730, 86, 'Las Delicias', 'las-delicias'),
(3731, 86, 'Las Tinajas', 'las-tinajas'),
(3732, 86, 'Lavalle', 'lavalle'),
(3733, 86, 'Libertad', 'libertad'),
(3734, 86, 'Lilo Viejo', 'lilo-viejo'),
(3735, 86, 'Los Cardozos', 'los-cardozos'),
(3736, 86, 'Los Juríes', 'los-juries'),
(3737, 86, 'Los Miranda', 'los-miranda'),
(3738, 86, 'Los Núñez', 'los-nunez'),
(3739, 86, 'Los Pirpintos', 'los-pirpintos'),
(3740, 86, 'Los Quiroga', 'los-quiroga'),
(3741, 86, 'Los Soria', 'los-soria'),
(3742, 86, 'Los Telares', 'los-telares'),
(3743, 86, 'Los Tigres', 'los-tigres'),
(3744, 86, 'Lugones', 'lugones'),
(3745, 86, 'Maco', 'maco'),
(3746, 86, 'Malbrán', 'malbran'),
(3747, 86, 'Mansupa', 'mansupa'),
(3748, 86, 'Maquito', 'maquito'),
(3749, 86, 'Matará', 'matara'),
(3750, 86, 'Medellín', 'medellin'),
(3751, 86, 'Minerva', 'minerva'),
(3752, 86, 'Monte Quemado', 'monte-quemado'),
(3753, 86, 'Morales', 'morales'),
(3754, 86, 'Nueva Esperanza', 'nueva-esperanza'),
(3755, 86, 'Nueva Francia', 'nueva-francia'),
(3756, 86, 'Palo Negro', 'palo-negro'),
(3757, 86, 'Pampa de los Guanacos', 'pampa-de-los-guanacos'),
(3758, 86, 'Patay', 'patay'),
(3759, 86, 'Pozo Betbeder', 'pozo-betbeder'),
(3760, 86, 'Pozo Hondo', 'pozo-hondo'),
(3761, 86, 'Pozuelos', 'pozuelos'),
(3762, 86, 'Pueblo Pablo Torelo', 'pueblo-pablo-torelo'),
(3763, 86, 'Puesto de San Antonio', 'puesto-de-san-antonio'),
(3764, 86, 'Quimili', 'quimili'),
(3765, 86, 'Ramírez de Velazco', 'ramirez-de-velazco'),
(3766, 86, 'Rapelli', 'rapelli'),
(3767, 86, 'Real Sayana', 'real-sayana'),
(3768, 86, 'Rodeo de Valdez', 'rodeo-de-valdez'),
(3769, 86, 'Roversi', 'roversi'),
(3770, 86, 'Sachayoj', 'sachayoj'),
(3771, 86, 'San José del Boquerón', 'san-jose-del-boqueron'),
(3772, 86, 'San Pedro (Capital)', 'san-pedro-capital'),
(3773, 86, 'San Pedro (Choya)', 'san-pedro-choya'),
(3774, 86, 'San Pedro (Guasayán)', 'san-pedro-guasayan'),
(3775, 86, 'San Pedro (Jiménez)', 'san-pedro-jimenez'),
(3776, 86, 'Santa María', 'santa-maria'),
(3777, 86, 'Santiago del Estero', 'santiago-del-estero'),
(3778, 86, 'Santo Domingo', 'santo-domingo'),
(3779, 86, 'Santos Lugares', 'santos-lugares'),
(3780, 86, 'Selva', 'selva'),
(3781, 86, 'Simbol', 'simbol'),
(3782, 86, 'Simbolar', 'simbolar'),
(3783, 86, 'Sol de Julio', 'sol-de-julio'),
(3784, 86, 'Sumamao', 'sumamao'),
(3785, 86, 'Sumampa', 'sumampa'),
(3786, 86, 'Sumampa Viejo', 'sumampa-viejo'),
(3787, 86, 'Suncho Corral', 'suncho-corral'),
(3788, 86, 'Tapso', 'tapso'),
(3789, 86, 'Termas de Río Hondo', 'termas-de-rio-hondo'),
(3790, 86, 'Tintina', 'tintina'),
(3791, 86, 'Tomás Young', 'tomas-young'),
(3792, 86, 'Tramo 16', 'tramo-16'),
(3793, 86, 'Tramo 20', 'tramo-20'),
(3794, 86, 'Urutaú', 'urutau'),
(3795, 86, 'Vaca Huañuna', 'vaca-huanuna'),
(3796, 86, 'Vilelas', 'vilelas'),
(3797, 86, 'Villa Atamisqui', 'villa-atamisqui'),
(3798, 86, 'Villa Figueroa', 'villa-figueroa'),
(3799, 86, 'Villa General Mitre', 'villa-general-mitre'),
(3800, 86, 'Villa Giménez', 'villa-gimenez');

INSERT INTO `localidades` (`id`, `id_provincia`, `descr`, `slug`) VALUES
(3801, 86, 'Villa La Punta', 'villa-la-punta'),
(3802, 86, 'Villa Mailín', 'villa-mailin'),
(3803, 86, 'Villa Nueva', 'villa-nueva'),
(3804, 86, 'Villa Ojo de Agua', 'villa-ojo-de-agua'),
(3805, 86, 'Villa Río Hondo', 'villa-rio-hondo'),
(3806, 86, 'Villa Salavina', 'villa-salavina'),
(3807, 86, 'Villa San Martín (Est. Loreto)', 'villa-san-martin-est-loreto'),
(3808, 86, 'Villa Silípica', 'villa-silipica'),
(3809, 86, 'Villa Turística del Embalse de Rio Hondo', 'villa-turistica-del-embalse-de-rio-hondo'),
(3810, 86, 'Villa Unión', 'villa-union'),
(3811, 86, 'Vilmer', 'vilmer'),
(3812, 86, 'Vinará', 'vinara'),
(3813, 86, 'Vuelta de la Barranca', 'vuelta-de-la-barranca'),
(3814, 86, 'Weisburd', 'weisburd'),
(3815, 86, 'Yanda', 'yanda'),
(3816, 86, 'Yuchán', 'yuchan'),
(3817, 90, '7 de Abril', '7-de-abril'),
(3818, 90, 'Acheral', 'acheral'),
(3819, 90, 'Aguilares', 'aguilares'),
(3820, 90, 'Alderetes', 'alderetes'),
(3821, 90, 'Alpachiri', 'alpachiri'),
(3822, 90, 'Alto Verde', 'alto-verde'),
(3823, 90, 'Amaicha del Valle', 'amaicha-del-valle'),
(3824, 90, 'Arcadia', 'arcadia'),
(3825, 90, 'Atahona', 'atahona'),
(3826, 90, 'Banda del Río Salí', 'banda-del-rio-sali'),
(3827, 90, 'Barrio Aeropuerto', 'barrio-aeropuerto'),
(3828, 90, 'Barrio Araujo', 'barrio-araujo'),
(3829, 90, 'Barrio Mutual San Martín', 'barrio-mutual-san-martin'),
(3830, 90, 'Barrio San Felipe', 'barrio-san-felipe'),
(3831, 90, 'Barrio San Jorge', 'barrio-san-jorge'),
(3832, 90, 'Bella Vista', 'bella-vista'),
(3833, 90, 'Campo de Herrera', 'campo-de-herrera'),
(3834, 90, 'Capitán Cáceres', 'capitan-caceres'),
(3835, 90, 'Choromoro', 'choromoro'),
(3836, 90, 'Colalao del Valle', 'colalao-del-valle'),
(3837, 90, 'Colombres', 'colombres'),
(3838, 90, 'Colonia Mayo - Barrio La Milagrosa', 'colonia-mayo-barrio-la-milagrosa'),
(3839, 90, 'Concepción', 'concepcion'),
(3840, 90, 'Delfín Gallo', 'delfin-gallo'),
(3841, 90, 'El Bracho', 'el-bracho'),
(3842, 90, 'El Cadillal', 'el-cadillal'),
(3843, 90, 'El Chañar', 'el-chanar'),
(3844, 90, 'El Corte', 'el-corte'),
(3845, 90, 'El Manantial', 'el-manantial'),
(3846, 90, 'El Mollar', 'el-mollar'),
(3847, 90, 'El Naranjo', 'el-naranjo'),
(3848, 90, 'El Paraíso', 'el-paraiso'),
(3849, 90, 'Estación Aráoz', 'estacion-araoz'),
(3850, 90, 'Ex Ingenio Esperanza', 'ex-ingenio-esperanza'),
(3851, 90, 'Ex Ingenio Los Ralos', 'ex-ingenio-los-ralos'),
(3852, 90, 'Ex Ingenio Luján', 'ex-ingenio-lujan'),
(3853, 90, 'Ex Ingenio Nueva Baviera', 'ex-ingenio-nueva-baviera'),
(3854, 90, 'Ex Ingenio San José', 'ex-ingenio-san-jose'),
(3855, 90, 'Famaillá', 'famailla'),
(3856, 90, 'Garmendia', 'garmendia'),
(3857, 90, 'Graneros', 'graneros'),
(3858, 90, 'Iltico', 'iltico'),
(3859, 90, 'Ingenio Fronterita', 'ingenio-fronterita'),
(3860, 90, 'Ingenio La Florida', 'ingenio-la-florida'),
(3861, 90, 'Ingenio San Pablo', 'ingenio-san-pablo'),
(3862, 90, 'Ingenio Santa Bárbara', 'ingenio-santa-barbara'),
(3863, 90, 'Juan Bautista Alberdi', 'juan-bautista-alberdi'),
(3864, 90, 'La Cocha', 'la-cocha'),
(3865, 90, 'La Florida', 'la-florida'),
(3866, 90, 'La Ramada', 'la-ramada'),
(3867, 90, 'La Reducción', 'la-reduccion'),
(3868, 90, 'La Trinidad', 'la-trinidad'),
(3869, 90, 'Lamadrid', 'lamadrid'),
(3870, 90, 'Las Cejas', 'las-cejas'),
(3871, 90, 'Lastenia', 'lastenia'),
(3872, 90, 'Los Gutiérrez', 'los-gutierrez'),
(3873, 90, 'Los Puestos', 'los-puestos'),
(3874, 90, 'Los Ralos', 'los-ralos'),
(3875, 90, 'Los Sarmientos', 'los-sarmientos'),
(3876, 90, 'Lules', 'lules'),
(3877, 90, 'Macomitas', 'macomitas'),
(3878, 90, 'Manuel García Fernández', 'manuel-garcia-fernandez'),
(3879, 90, 'Medina', 'medina'),
(3880, 90, 'Monteagudo', 'monteagudo'),
(3881, 90, 'Monteros', 'monteros'),
(3882, 90, 'Nueva Trinidad', 'nueva-trinidad'),
(3883, 90, 'Pacará', 'pacara'),
(3884, 90, 'Piedrabuena', 'piedrabuena'),
(3885, 90, 'Pueblo Independencia', 'pueblo-independencia'),
(3886, 90, 'Ranchillos', 'ranchillos'),
(3887, 90, 'Río Chico', 'rio-chico'),
(3888, 90, 'Río Colorado', 'rio-colorado'),
(3889, 90, 'Río Seco', 'rio-seco'),
(3890, 90, 'San Andrés', 'san-andres'),
(3891, 90, 'San José de La Cocha', 'san-jose-de-la-cocha'),
(3892, 90, 'San Miguel de Tucumán', 'san-miguel-de-tucuman'),
(3893, 90, 'San Pedro de Colalao', 'san-pedro-de-colalao'),
(3894, 90, 'Santa Ana', 'santa-ana'),
(3895, 90, 'Santa Cruz', 'santa-cruz'),
(3896, 90, 'Santa Lucía', 'santa-lucia'),
(3897, 90, 'Santa Rosa de Leales', 'santa-rosa-de-leales'),
(3898, 90, 'Sargento Moya', 'sargento-moya'),
(3899, 90, 'Simoca', 'simoca'),
(3900, 90, 'Soldado Maldonado', 'soldado-maldonado'),
(3901, 90, 'Taco Ralo', 'taco-ralo'),
(3902, 90, 'Tafí del Valle', 'tafi-del-valle'),
(3903, 90, 'Tafí Viejo', 'tafi-viejo'),
(3904, 90, 'Teniente Berdina', 'teniente-berdina'),
(3905, 90, 'Villa Belgrano', 'villa-belgrano'),
(3906, 90, 'Villa Benjamín Aráoz', 'villa-benjamin-araoz'),
(3907, 90, 'Villa Burruyacú', 'villa-burruyacu'),
(3908, 90, 'Villa Carmela', 'villa-carmela'),
(3909, 90, 'Villa Chicligasta', 'villa-chicligasta'),
(3910, 90, 'Villa Clodomiro Hileret', 'villa-clodomiro-hileret'),
(3911, 90, 'Villa de Leales', 'villa-de-leales'),
(3912, 90, 'Villa de Trancas', 'villa-de-trancas'),
(3913, 90, 'Villa Fiad - Ingenio Leales', 'villa-fiad-ingenio-leales'),
(3914, 90, 'Villa Mariano Moreno - El Colmenar', 'villa-mariano-moreno-el-colmenar'),
(3915, 90, 'Villa Padre Monti', 'villa-padre-monti'),
(3916, 90, 'Villa Quinteros', 'villa-quinteros'),
(3917, 90, 'Villa Recaste', 'villa-recaste'),
(3918, 90, 'Villa Tercera', 'villa-tercera'),
(3919, 90, 'Yerba Buena - Marcos Paz', 'yerba-buena-marcos-paz'),
(3920, 94, 'Laguna Escondida', 'laguna-escondida'),
(3921, 94, 'Puerto Argentino', 'puerto-argentino'),
(3922, 94, 'Río Grande', 'rio-grande'),
(3923, 94, 'Ushuaia', 'ushuaia');

ALTER TABLE `request_reservas`
  ADD COLUMN `referencia` VARCHAR(45) NULL DEFAULT NULL;

ALTER TABLE `locadores`
  ADD COLUMN `calificacion` TINYINT NULL DEFAULT 0,
  ADD COLUMN `id_localidad` INT(11) NULL DEFAULT NULL,
  ADD INDEX `idx_locadores_localidad` (`id_localidad`),
  ADD CONSTRAINT `fk_locadores_localidad` FOREIGN KEY (`id_localidad`)
    REFERENCES `localidades` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Trazabilidad de IDs internos a la fuente oficial (sin dependencia de red).
-- Localidad 1: Georef 0210501001; departamento 02105; localidad censal 02105010.
-- Localidad 2: Georef 0203501001; departamento 02035; localidad censal 02035010.
-- Localidad 3: Georef 0202101001; departamento 02021; localidad censal 02021010.
-- Localidad 4: Georef 0202801001; departamento 02028; localidad censal 02028010.
-- Localidad 5: Georef 0209101001; departamento 02091; localidad censal 02091010.
-- Localidad 6: Georef 0202801002; departamento 02028; localidad censal 02028010.
-- Localidad 7: Georef 0203501002; departamento 02035; localidad censal 02035010.
-- Localidad 8: Georef 0204201001; departamento 02042; localidad censal 02042010.
-- Localidad 9: Georef 0210501002; departamento 02105; localidad censal 02105010.
-- Localidad 10: Georef 02014010; departamento 02014; localidad censal 02014010.
-- Localidad 11: Georef 0208401001; departamento 02084; localidad censal 02084010.
-- Localidad 12: Georef 0209101002; departamento 02091; localidad censal 02091010.
-- Localidad 13: Georef 0200701001; departamento 02007; localidad censal 02007010.
-- Localidad 14: Georef 0204901001; departamento 02049; localidad censal 02049010.
-- Localidad 15: Georef 0207001001; departamento 02070; localidad censal 02070010.
-- Localidad 16: Georef 0206301001; departamento 02063; localidad censal 02063010.
-- Localidad 17: Georef 0206301002; departamento 02063; localidad censal 02063010.
-- Localidad 18: Georef 0200701002; departamento 02007; localidad censal 02007010.
-- Localidad 19: Georef 0207001002; departamento 02070; localidad censal 02070010.
-- Localidad 20: Georef 0202801003; departamento 02028; localidad censal 02028010.
-- Localidad 21: Georef 0209101003; departamento 02091; localidad censal 02091010.
-- Localidad 22: Georef 0209801001; departamento 02098; localidad censal 02098010.
-- Localidad 23: Georef 0206301003; departamento 02063; localidad censal 02063010.
-- Localidad 24: Georef 0204901002; departamento 02049; localidad censal 02049010.
-- Localidad 25: Georef 0210501003; departamento 02105; localidad censal 02105010.
-- Localidad 26: Georef 0202801004; departamento 02028; localidad censal 02028010.
-- Localidad 27: Georef 0210501004; departamento 02105; localidad censal 02105010.
-- Localidad 28: Georef 0200701003; departamento 02007; localidad censal 02007010.
-- Localidad 29: Georef 0201401001; departamento 02014; localidad censal 02014010.
-- Localidad 30: Georef 0200701004; departamento 02007; localidad censal 02007010.
-- Localidad 31: Georef 0208401002; departamento 02084; localidad censal 02084010.
-- Localidad 32: Georef 0202101002; departamento 02021; localidad censal 02021010.
-- Localidad 33: Georef 0200701005; departamento 02007; localidad censal 02007010.
-- Localidad 34: Georef 0200701006; departamento 02007; localidad censal 02007010.
-- Localidad 35: Georef 0207001003; departamento 02070; localidad censal 02070010.
-- Localidad 36: Georef 0207001004; departamento 02070; localidad censal 02070010.
-- Localidad 37: Georef 0210501005; departamento 02105; localidad censal 02105010.
-- Localidad 38: Georef 0207701001; departamento 02077; localidad censal 02077010.
-- Localidad 39: Georef 0207701002; departamento 02077; localidad censal 02077010.
-- Localidad 40: Georef 0207701003; departamento 02077; localidad censal 02077010.
-- Localidad 41: Georef 0205601001; departamento 02056; localidad censal 02056010.
-- Localidad 42: Georef 0207001005; departamento 02070; localidad censal 02070010.
-- Localidad 43: Georef 0210501006; departamento 02105; localidad censal 02105010.
-- Localidad 44: Georef 0208401003; departamento 02084; localidad censal 02084010.
-- Localidad 45: Georef 0207001006; departamento 02070; localidad censal 02070010.
-- Localidad 46: Georef 0205601002; departamento 02056; localidad censal 02056010.
-- Localidad 47: Georef 0207701004; departamento 02077; localidad censal 02077010.
-- Localidad 48: Georef 0205601003; departamento 02056; localidad censal 02056010.
-- Localidad 49: Georef 0208401004; departamento 02084; localidad censal 02084010.
-- Localidad 50: Georef 0684001009; departamento 06840; localidad censal 06840010.
-- Localidad 51: Georef 06588030; departamento 06588; localidad censal 06588030.
-- Localidad 52: Georef 06049050; departamento 06049; localidad censal 06049050.
-- Localidad 53: Georef 06651040; departamento 06651; localidad censal 06651040.
-- Localidad 54: Georef 0642701012; departamento 06427; localidad censal 06427010.
-- Localidad 55: Georef 06854100; departamento 06854; localidad censal 06854100.
-- Localidad 56: Georef 06826040; departamento 06826; localidad censal 06826040.
-- Localidad 57: Georef 0626001005; departamento 06260; localidad censal 06260010.
-- Localidad 58: Georef 06588100; departamento 06588; localidad censal 06588100.
-- Localidad 59: Georef 0644103001; departamento 06441; localidad censal 06441030.
-- Localidad 60: Georef 06547010; departamento 06547; localidad censal 06547010.
-- Localidad 61: Georef 0675601001; departamento 06756; localidad censal 06756010.
-- Localidad 62: Georef 06623010; departamento 06623; localidad censal 06623010.
-- Localidad 63: Georef 06014010; departamento 06014; localidad censal 06014010.
-- Localidad 64: Georef 0602801001; departamento 06028; localidad censal 06028010.
-- Localidad 65: Georef 0627001001; departamento 06270; localidad censal 06270010.
-- Localidad 66: Georef 06854010; departamento 06854; localidad censal 06854010.
-- Localidad 67: Georef 06413010; departamento 06413; localidad censal 06413010.
-- Localidad 68: Georef 06413020; departamento 06413; localidad censal 06413020.
-- Localidad 69: Georef 06462010; departamento 06462; localidad censal 06462010.
-- Localidad 70: Georef 06021010; departamento 06021; localidad censal 06021010.
-- Localidad 71: Georef 0642701001; departamento 06427; localidad censal 06427010.
-- Localidad 72: Georef 0677802001; departamento 06778; localidad censal 06778020.
-- Localidad 73: Georef 06588010; departamento 06588; localidad censal 06588010.
-- Localidad 74: Georef 06028010; departamento 06028; localidad censal 06028010.
-- Localidad 75: Georef 06119010; departamento 06119; localidad censal 06119010.
-- Localidad 76: Georef 06707010; departamento 06707; localidad censal 06707010.
-- Localidad 77: Georef 06655010; departamento 06655; localidad censal 06655010.
-- Localidad 78: Georef 06679010; departamento 06679; localidad censal 06679010.
-- Localidad 79: Georef 06231010; departamento 06231; localidad censal 06231010.
-- Localidad 80: Georef 0644103002; departamento 06441; localidad censal 06441030.
-- Localidad 81: Georef 06483010; departamento 06483; localidad censal 06483010.
-- Localidad 82: Georef 06189010; departamento 06189; localidad censal 06189010.
-- Localidad 83: Georef 0644103003; departamento 06441; localidad censal 06441030.
-- Localidad 84: Georef 06231020; departamento 06231; localidad censal 06231020.
-- Localidad 85: Georef 0651501001; departamento 06515; localidad censal 06515010.
-- Localidad 86: Georef 0603501001; departamento 06035; localidad censal 06035010.
-- Localidad 87: Georef 06476010; departamento 06476; localidad censal 06476010.
-- Localidad 88: Georef 06469010; departamento 06469; localidad censal 06469010.
-- Localidad 89: Georef 06875010; departamento 06875; localidad censal 06875010.
-- Localidad 90: Georef 06049010; departamento 06049; localidad censal 06049010.
-- Localidad 91: Georef 06077010; departamento 06077; localidad censal 06077010.
-- Localidad 92: Georef 06294010; departamento 06294; localidad censal 06294010.
-- Localidad 93: Georef 06700010; departamento 06700; localidad censal 06700010.
-- Localidad 94: Georef 06714010; departamento 06714; localidad censal 06714010.
-- Localidad 95: Georef 06399010; departamento 06399; localidad censal 06399010.
-- Localidad 96: Georef 0644103004; departamento 06441; localidad censal 06441030.
-- Localidad 97: Georef 06294020; departamento 06294; localidad censal 06294020.
-- Localidad 98: Georef 06505010; departamento 06505; localidad censal 06505010.
-- Localidad 99: Georef 0651806001; departamento 06518; localidad censal 06518060.
-- Localidad 100: Georef 06035010; departamento 06035; localidad censal 06035010.
-- Localidad 101: Georef 06042010; departamento 06042; localidad censal 06042010.
-- Localidad 102: Georef 06728010; departamento 06728; localidad censal 06728010.
-- Localidad 103: Georef 06651010; departamento 06651; localidad censal 06651010.
-- Localidad 104: Georef 06049020; departamento 06049; localidad censal 06049020.
-- Localidad 105: Georef 06056010; departamento 06056; localidad censal 06056010.
-- Localidad 106: Georef 06602010; departamento 06602; localidad censal 06602010.
-- Localidad 107: Georef 06385010; departamento 06385; localidad censal 06385010.
-- Localidad 108: Georef 06182010; departamento 06182; localidad censal 06182010.
-- Localidad 109: Georef 06063010; departamento 06063; localidad censal 06063010.
-- Localidad 110: Georef 06833010; departamento 06833; localidad censal 06833010.
-- Localidad 111: Georef 06182020; departamento 06182; localidad censal 06182020.
-- Localidad 112: Georef 06742010; departamento 06742; localidad censal 06742010.
-- Localidad 113: Georef 06553010; departamento 06553; localidad censal 06553010.
-- Localidad 114: Georef 06392010; departamento 06392; localidad censal 06392010.
-- Localidad 115: Georef 0649001001; departamento 06490; localidad censal 06490010.
-- Localidad 116: Georef 06070010; departamento 06070; localidad censal 06070010.
-- Localidad 117: Georef 0664801001; departamento 06648; localidad censal 06648010.
-- Localidad 118: Georef 0609801001; departamento 06098; localidad censal 06098010.
-- Localidad 119: Georef 0613405001; departamento 06134; localidad censal 06134050.
-- Localidad 120: Georef 0635712001; departamento 06357; localidad censal 06357120.
-- Localidad 121: Georef 06357020; departamento 06357; localidad censal 06357020.
-- Localidad 122: Georef 0609801002; departamento 06098; localidad censal 06098010.
-- Localidad 123: Georef 0644103005; departamento 06441; localidad censal 06441030.
-- Localidad 124: Georef 0635711005; departamento 06357; localidad censal 06357110.
-- Localidad 125: Georef 0635712002; departamento 06357; localidad censal 06357120.
-- Localidad 126: Georef 06119015; departamento 06119; localidad censal 06119015.
-- Localidad 127: Georef 06134020; departamento 06134; localidad censal 06134020.
-- Localidad 128: Georef 0644103006; departamento 06441; localidad censal 06441030.
-- Localidad 129: Georef 0631501001; departamento 06315; localidad censal 06315010.
-- Localidad 130: Georef 0635712003; departamento 06357; localidad censal 06357120.
-- Localidad 131: Georef 0649706001; departamento 06497; localidad censal 06497060.
-- Localidad 132: Georef 06119020; departamento 06119; localidad censal 06119020.
-- Localidad 133: Georef 0644103007; departamento 06441; localidad censal 06441030.
-- Localidad 134: Georef 0668606001; departamento 06686; localidad censal 06686060.
-- Localidad 135: Georef 0644103008; departamento 06441; localidad censal 06441030.
-- Localidad 136: Georef 0652502001; departamento 06525; localidad censal 06525020.
-- Localidad 137: Georef 06218003; departamento 06218; localidad censal 06218003.
-- Localidad 138: Georef 06119030; departamento 06119; localidad censal 06119030.
-- Localidad 139: Georef 06126020; departamento 06126; localidad censal 06126020.
-- Localidad 140: Georef 0636403001; departamento 06364; localidad censal 06364030.
-- Localidad 141: Georef 0637101001; departamento 06371; localidad censal 06371010.
-- Localidad 142: Georef 06119040; departamento 06119; localidad censal 06119040.
-- Localidad 143: Georef 0636403002; departamento 06364; localidad censal 06364030.
-- Localidad 144: Georef 0688205001; departamento 06882; localidad censal 06882050.
-- Localidad 145: Georef 0621801003; departamento 06218; localidad censal 06218010.
-- Localidad 146: Georef 06357060; departamento 06357; localidad censal 06357060.
-- Localidad 147: Georef 06525010; departamento 06525; localidad censal 06525010.
-- Localidad 148: Georef 0609801003; departamento 06098; localidad censal 06098010.
-- Localidad 149: Georef 06469020; departamento 06469; localidad censal 06469020.
-- Localidad 150: Georef 0675601002; departamento 06756; localidad censal 06756010.
-- Localidad 151: Georef 0625201001; departamento 06252; localidad censal 06252010.
-- Localidad 152: Georef 0676001001; departamento 06760; localidad censal 06760010.
-- Localidad 153: Georef 06147010; departamento 06147; localidad censal 06147010.
-- Localidad 154: Georef 0680501001; departamento 06805; localidad censal 06805010.
-- Localidad 155: Georef 06224005; departamento 06224; localidad censal 06224005.
-- Localidad 156: Georef 06084020; departamento 06084; localidad censal 06084020.
-- Localidad 157: Georef 06091010; departamento 06091; localidad censal 06091010.
-- Localidad 158: Georef 0609101002; departamento 06091; localidad censal 06091010.
-- Localidad 159: Georef 06714020; departamento 06714; localidad censal 06714020.
-- Localidad 160: Georef 06098010; departamento 06098; localidad censal 06098010.
-- Localidad 161: Georef 06469030; departamento 06469; localidad censal 06469030.
-- Localidad 162: Georef 0665801001; departamento 06658; localidad censal 06658010.
-- Localidad 163: Georef 0665801002; departamento 06658; localidad censal 06658010.
-- Localidad 164: Georef 06826010; departamento 06826; localidad censal 06826010.
-- Localidad 165: Georef 0637101002; departamento 06371; localidad censal 06371010.
-- Localidad 166: Georef 06595010; departamento 06595; localidad censal 06595010.
-- Localidad 167: Georef 06277010; departamento 06277; localidad censal 06277010.
-- Localidad 168: Georef 06616010; departamento 06616; localidad censal 06616010.
-- Localidad 169: Georef 06651020; departamento 06651; localidad censal 06651020.
-- Localidad 170: Georef 0627401001; departamento 06274; localidad censal 06274010.
-- Localidad 171: Georef 0675601003; departamento 06756; localidad censal 06756010.
-- Localidad 172: Georef 06112010; departamento 06112; localidad censal 06112010.
-- Localidad 173: Georef 0602801002; departamento 06028; localidad censal 06028010.
-- Localidad 174: Georef 06056020; departamento 06056; localidad censal 06056020.
-- Localidad 175: Georef 06049030; departamento 06049; localidad censal 06049030.
-- Localidad 176: Georef 06147020; departamento 06147; localidad censal 06147020.
-- Localidad 177: Georef 0635711001; departamento 06357; localidad censal 06357110.
-- Localidad 178: Georef 0651806002; departamento 06518; localidad censal 06518060.
-- Localidad 179: Georef 06126030; departamento 06126; localidad censal 06126030.
-- Localidad 180: Georef 0676001002; departamento 06760; localidad censal 06760010.
-- Localidad 181: Georef 06119045; departamento 06119; localidad censal 06119045.
-- Localidad 182: Georef 0676305001; departamento 06763; localidad censal 06763050.
-- Localidad 183: Georef 06392020; departamento 06392; localidad censal 06392020.
-- Localidad 184: Georef 0626001001; departamento 06260; localidad censal 06260010.
-- Localidad 185: Georef 0627001002; departamento 06270; localidad censal 06270010.
-- Localidad 186: Georef 06134030; departamento 06134; localidad censal 06134030.
-- Localidad 187: Georef 06266020; departamento 06266; localidad censal 06266020.
-- Localidad 188: Georef 06609010; departamento 06609; localidad censal 06609010.
-- Localidad 189: Georef 06140010; departamento 06140; localidad censal 06140010.
-- Localidad 190: Georef 0686101001; departamento 06861; localidad censal 06861010.
-- Localidad 191: Georef 06602020; departamento 06602; localidad censal 06602020.
-- Localidad 192: Georef 06007010; departamento 06007; localidad censal 06007010.
-- Localidad 193: Georef 0664401001; departamento 06644; localidad censal 06644010.
-- Localidad 194: Georef 06693010; departamento 06693; localidad censal 06693010.
-- Localidad 195: Georef 06147030; departamento 06147; localidad censal 06147030.
-- Localidad 196: Georef 06497020; departamento 06497; localidad censal 06497020.
-- Localidad 197: Georef 06588020; departamento 06588; localidad censal 06588020.
-- Localidad 198: Georef 06469040; departamento 06469; localidad censal 06469040.
-- Localidad 199: Georef 0627001003; departamento 06270; localidad censal 06270010.
-- Localidad 200: Georef 06154010; departamento 06154; localidad censal 06154010.
-- Localidad 201: Georef 0609101003; departamento 06091; localidad censal 06091010.
-- Localidad 202: Georef 06161010; departamento 06161; localidad censal 06161010.
-- Localidad 203: Georef 06602030; departamento 06602; localidad censal 06602030.
-- Localidad 204: Georef 06630010; departamento 06630; localidad censal 06630010.
-- Localidad 205: Georef 06399020; departamento 06399; localidad censal 06399020.
-- Localidad 206: Georef 06203010; departamento 06203; localidad censal 06203010.
-- Localidad 207: Georef 0684001001; departamento 06840; localidad censal 06840010.
-- Localidad 208: Georef 0656801001; departamento 06568; localidad censal 06568010.
-- Localidad 209: Georef 06168010; departamento 06168; localidad censal 06168010.
-- Localidad 210: Georef 06210010; departamento 06210; localidad censal 06210010.
-- Localidad 211: Georef 06707030; departamento 06707; localidad censal 06707030.
-- Localidad 212: Georef 06280005; departamento 06280; localidad censal 06280005.
-- Localidad 213: Georef 06168020; departamento 06168; localidad censal 06168020.
-- Localidad 214: Georef 06168030; departamento 06168; localidad censal 06168030.
-- Localidad 215: Georef 06210020; departamento 06210; localidad censal 06210020.
-- Localidad 216: Georef 06357080; departamento 06357; localidad censal 06357080.
-- Localidad 217: Georef 06218010; departamento 06218; localidad censal 06218010.
-- Localidad 218: Georef 06819010; departamento 06819; localidad censal 06819010.
-- Localidad 219: Georef 06049040; departamento 06049; localidad censal 06049040.
-- Localidad 220: Georef 06224010; departamento 06224; localidad censal 06224010.
-- Localidad 221: Georef 0684001002; departamento 06840; localidad censal 06840010.
-- Localidad 222: Georef 0644103009; departamento 06441; localidad censal 06441030.
-- Localidad 223: Georef 0637101003; departamento 06371; localidad censal 06371010.
-- Localidad 224: Georef 0642701002; departamento 06427; localidad censal 06427010.
-- Localidad 225: Georef 0637101004; departamento 06371; localidad censal 06371010.
-- Localidad 226: Georef 0684001003; departamento 06840; localidad censal 06840010.
-- Localidad 227: Georef 0684001004; departamento 06840; localidad censal 06840010.
-- Localidad 228: Georef 06581010; departamento 06581; localidad censal 06581010.
-- Localidad 229: Georef 06833020; departamento 06833; localidad censal 06833020.
-- Localidad 230: Georef 0602801003; departamento 06028; localidad censal 06028010.
-- Localidad 231: Georef 06497025; departamento 06497; localidad censal 06497025.
-- Localidad 232: Georef 06175010; departamento 06175; localidad censal 06175010.
-- Localidad 233: Georef 0659506001; departamento 06595; localidad censal 06595060.
-- Localidad 234: Georef 06147040; departamento 06147; localidad censal 06147040.
-- Localidad 235: Georef 06595030; departamento 06595; localidad censal 06595030.
-- Localidad 236: Georef 06700020; departamento 06700; localidad censal 06700020.
-- Localidad 237: Georef 06595040; departamento 06595; localidad censal 06595040.
-- Localidad 238: Georef 06007020; departamento 06007; localidad censal 06007020.
-- Localidad 239: Georef 06351010; departamento 06351; localidad censal 06351010.
-- Localidad 240: Georef 06154020; departamento 06154; localidad censal 06154020.
-- Localidad 241: Georef 06280010; departamento 06280; localidad censal 06280010.
-- Localidad 242: Georef 06112020; departamento 06112; localidad censal 06112020.
-- Localidad 243: Georef 06763010; departamento 06763; localidad censal 06763010.
-- Localidad 244: Georef 06833030; departamento 06833; localidad censal 06833030.
-- Localidad 245: Georef 06455010; departamento 06455; localidad censal 06455010.
-- Localidad 246: Georef 06119050; departamento 06119; localidad censal 06119050.
-- Localidad 247: Georef 06392030; departamento 06392; localidad censal 06392030.
-- Localidad 248: Georef 06189030; departamento 06189; localidad censal 06189030.
-- Localidad 249: Georef 06469050; departamento 06469; localidad censal 06469050.
-- Localidad 250: Georef 06196010; departamento 06196; localidad censal 06196010.
-- Localidad 251: Georef 06021020; departamento 06021; localidad censal 06021020.
-- Localidad 252: Georef 06203020; departamento 06203; localidad censal 06203020.
-- Localidad 253: Georef 06518010; departamento 06518; localidad censal 06518010.
-- Localidad 254: Georef 0649706002; departamento 06497; localidad censal 06497060.
-- Localidad 255: Georef 0658104003; departamento 06581; localidad censal 06581040.
-- Localidad 256: Georef 0636403003; departamento 06364; localidad censal 06364030.
-- Localidad 257: Georef 0649706007; departamento 06497; localidad censal 06497060.
-- Localidad 258: Georef 06875025; departamento 06875; localidad censal 06875025.
-- Localidad 259: Georef 06798010; departamento 06798; localidad censal 06798010.
-- Localidad 260: Georef 0603501003; departamento 06035; localidad censal 06035010.
-- Localidad 261: Georef 0656001001; departamento 06560; localidad censal 06560010.
-- Localidad 262: Georef 06728020; departamento 06728; localidad censal 06728020.
-- Localidad 263: Georef 06203030; departamento 06203; localidad censal 06203030.
-- Localidad 264: Georef 06154030; departamento 06154; localidad censal 06154030.
-- Localidad 265: Georef 06203040; departamento 06203; localidad censal 06203040.
-- Localidad 266: Georef 06231030; departamento 06231; localidad censal 06231030.
-- Localidad 267: Georef 06651030; departamento 06651; localidad censal 06651030.
-- Localidad 268: Georef 06616020; departamento 06616; localidad censal 06616020.
-- Localidad 269: Georef 06791010; departamento 06791; localidad censal 06791010.
-- Localidad 270: Georef 06014020; departamento 06014; localidad censal 06014020.
-- Localidad 271: Georef 06707040; departamento 06707; localidad censal 06707040.
-- Localidad 272: Georef 06854020; departamento 06854; localidad censal 06854020.
-- Localidad 273: Georef 0641201001; departamento 06412; localidad censal 06412010.
-- Localidad 274: Georef 0663804001; departamento 06638; localidad censal 06638040.
-- Localidad 275: Georef 06007030; departamento 06007; localidad censal 06007030.
-- Localidad 276: Georef 06266030; departamento 06266; localidad censal 06266030.
-- Localidad 277: Georef 0680501002; departamento 06805; localidad censal 06805010.
-- Localidad 278: Georef 0624501001; departamento 06245; localidad censal 06245010.
-- Localidad 279: Georef 0603501004; departamento 06035; localidad censal 06035010.
-- Localidad 280: Georef 06238010; departamento 06238; localidad censal 06238010.
-- Localidad 281: Georef 0677802003; departamento 06778; localidad censal 06778020.
-- Localidad 282: Georef 0665801003; departamento 06658; localidad censal 06658010.
-- Localidad 283: Georef 0602801004; departamento 06028; localidad censal 06028010.
-- Localidad 284: Georef 0680501003; departamento 06805; localidad censal 06805010.
-- Localidad 285: Georef 0680501004; departamento 06805; localidad censal 06805010.
-- Localidad 286: Georef 06588040; departamento 06588; localidad censal 06588040.
-- Localidad 287: Georef 06700030; departamento 06700; localidad censal 06700030.
-- Localidad 288: Georef 06735010; departamento 06735; localidad censal 06735010.
-- Localidad 289: Georef 0683302002; departamento 06833; localidad censal 06833020.
-- Localidad 290: Georef 0625201002; departamento 06252; localidad censal 06252010.
-- Localidad 291: Georef 06196020; departamento 06196; localidad censal 06196020.
-- Localidad 292: Georef 06462020; departamento 06462; localidad censal 06462020.
-- Localidad 293: Georef 0626001002; departamento 06260; localidad censal 06260010.
-- Localidad 294: Georef 0684001005; departamento 06840; localidad censal 06840010.
-- Localidad 295: Georef 06357090; departamento 06357; localidad censal 06357090.
-- Localidad 296: Georef 0656801002; departamento 06568; localidad censal 06568010.
-- Localidad 297: Georef 06665010; departamento 06665; localidad censal 06665010.
-- Localidad 298: Georef 0609101004; departamento 06091; localidad censal 06091010.
-- Localidad 299: Georef 06196030; departamento 06196; localidad censal 06196030.
-- Localidad 300: Georef 06189040; departamento 06189; localidad censal 06189040.
-- Localidad 301: Georef 0626606001; departamento 06266; localidad censal 06266060.
-- Localidad 302: Georef 0644103010; departamento 06441; localidad censal 06441030.
-- Localidad 303: Georef 0680501005; departamento 06805; localidad censal 06805010.
-- Localidad 304: Georef 06455020; departamento 06455; localidad censal 06455020.
-- Localidad 305: Georef 06469060; departamento 06469; localidad censal 06469060.
-- Localidad 306: Georef 0627401010; departamento 06274; localidad censal 06274010.
-- Localidad 307: Georef 06483020; departamento 06483; localidad censal 06483020.
-- Localidad 308: Georef 06224020; departamento 06224; localidad censal 06224020.
-- Localidad 309: Georef 06392040; departamento 06392; localidad censal 06392040.
-- Localidad 310: Georef 06581025; departamento 06581; localidad censal 06581025.
-- Localidad 311: Georef 06245010; departamento 06245; localidad censal 06245010.
-- Localidad 312: Georef 06763020; departamento 06763; localidad censal 06763020.
-- Localidad 313: Georef 06854030; departamento 06854; localidad censal 06854030.
-- Localidad 314: Georef 06252010; departamento 06252; localidad censal 06252010.
-- Localidad 315: Georef 06007040; departamento 06007; localidad censal 06007040.
-- Localidad 316: Georef 06700040; departamento 06700; localidad censal 06700040.
-- Localidad 317: Georef 06595050; departamento 06595; localidad censal 06595050.
-- Localidad 318: Georef 06294030; departamento 06294; localidad censal 06294030.
-- Localidad 319: Georef 0635711002; departamento 06357; localidad censal 06357110.
-- Localidad 320: Georef 0627401002; departamento 06274; localidad censal 06274010.
-- Localidad 321: Georef 06007050; departamento 06007; localidad censal 06007050.
-- Localidad 322: Georef 06260010; departamento 06260; localidad censal 06260010.
-- Localidad 323: Georef 06651050; departamento 06651; localidad censal 06651050.
-- Localidad 324: Georef 06270010; departamento 06270; localidad censal 06270010.
-- Localidad 325: Georef 0665801004; departamento 06658; localidad censal 06658010.
-- Localidad 326: Georef 0665801005; departamento 06658; localidad censal 06658010.
-- Localidad 327: Georef 06189050; departamento 06189; localidad censal 06189050.
-- Localidad 328: Georef 0663804002; departamento 06638; localidad censal 06638040.
-- Localidad 329: Georef 06651060; departamento 06651; localidad censal 06651060.
-- Localidad 330: Georef 06294040; departamento 06294; localidad censal 06294040.
-- Localidad 331: Georef 06274010; departamento 06274; localidad censal 06274010.
-- Localidad 332: Georef 06277020; departamento 06277; localidad censal 06277020.
-- Localidad 333: Georef 0686101002; departamento 06861; localidad censal 06861010.
-- Localidad 334: Georef 0686101003; departamento 06861; localidad censal 06861010.
-- Localidad 335: Georef 06623020; departamento 06623; localidad censal 06623020.
-- Localidad 336: Georef 06462030; departamento 06462; localidad censal 06462030.
-- Localidad 337: Georef 06679020; departamento 06679; localidad censal 06679020.
-- Localidad 338: Georef 06413040; departamento 06413; localidad censal 06413040.
-- Localidad 339: Georef 0656001002; departamento 06560; localidad censal 06560010.
-- Localidad 340: Georef 06609030; departamento 06609; localidad censal 06609030.
-- Localidad 341: Georef 06728030; departamento 06728; localidad censal 06728030.
-- Localidad 342: Georef 0651806003; departamento 06518; localidad censal 06518060.
-- Localidad 343: Georef 06714030; departamento 06714; localidad censal 06714030.
-- Localidad 344: Georef 06791030; departamento 06791; localidad censal 06791030.
-- Localidad 345: Georef 0625201003; departamento 06252; localidad censal 06252010.
-- Localidad 346: Georef 06399030; departamento 06399; localidad censal 06399030.
-- Localidad 347: Georef 06287010; departamento 06287; localidad censal 06287010.
-- Localidad 348: Georef 06294050; departamento 06294; localidad censal 06294050.
-- Localidad 349: Georef 06301010; departamento 06301; localidad censal 06301010.
-- Localidad 350: Georef 06812010; departamento 06812; localidad censal 06812010.
-- Localidad 351: Georef 06056030; departamento 06056; localidad censal 06056030.
-- Localidad 352: Georef 06308010; departamento 06308; localidad censal 06308010.
-- Localidad 353: Georef 06315010; departamento 06315; localidad censal 06315010.
-- Localidad 354: Georef 06322010; departamento 06322; localidad censal 06322010.
-- Localidad 355: Georef 06329020; departamento 06329; localidad censal 06329020.
-- Localidad 356: Georef 06336020; departamento 06336; localidad censal 06336020.
-- Localidad 357: Georef 06505020; departamento 06505; localidad censal 06505020.
-- Localidad 358: Georef 06112030; departamento 06112; localidad censal 06112030.
-- Localidad 359: Georef 0680501006; departamento 06805; localidad censal 06805010.
-- Localidad 360: Georef 06351020; departamento 06351; localidad censal 06351020.
-- Localidad 361: Georef 06518020; departamento 06518; localidad censal 06518020.
-- Localidad 362: Georef 06784010; departamento 06784; localidad censal 06784010.
-- Localidad 363: Georef 06364030; departamento 06364; localidad censal 06364030.
-- Localidad 364: Georef 06763030; departamento 06763; localidad censal 06763030.
-- Localidad 365: Georef 06371010; departamento 06371; localidad censal 06371010.
-- Localidad 366: Georef 06392050; departamento 06392; localidad censal 06392050.
-- Localidad 367: Georef 0603501005; departamento 06035; localidad censal 06035010.
-- Localidad 368: Georef 0643401001; departamento 06434; localidad censal 06434010.
-- Localidad 369: Georef 06351030; departamento 06351; localidad censal 06351030.
-- Localidad 370: Georef 06826020; departamento 06826; localidad censal 06826020.
-- Localidad 371: Georef 0602801005; departamento 06028; localidad censal 06028010.
-- Localidad 372: Georef 06770010; departamento 06770; localidad censal 06770010.
-- Localidad 373: Georef 0627401004; departamento 06274; localidad censal 06274010.
-- Localidad 374: Georef 06134040; departamento 06134; localidad censal 06134040.
-- Localidad 375: Georef 06854040; departamento 06854; localidad censal 06854040.
-- Localidad 376: Georef 06532005; departamento 06532; localidad censal 06532005.
-- Localidad 377: Georef 06119060; departamento 06119; localidad censal 06119060.
-- Localidad 378: Georef 0642701003; departamento 06427; localidad censal 06427010.
-- Localidad 379: Georef 06679030; departamento 06679; localidad censal 06679030.
-- Localidad 380: Georef 06301020; departamento 06301; localidad censal 06301020.
-- Localidad 381: Georef 06224030; departamento 06224; localidad censal 06224030.
-- Localidad 382: Georef 06700050; departamento 06700; localidad censal 06700050.
-- Localidad 383: Georef 0651501002; departamento 06515; localidad censal 06515010.
-- Localidad 384: Georef 0642701004; departamento 06427; localidad censal 06427010.
-- Localidad 385: Georef 0605601002; departamento 06056; localidad censal 06056010.
-- Localidad 386: Georef 06399040; departamento 06399; localidad censal 06399040.
-- Localidad 387: Georef 0664801002; departamento 06648; localidad censal 06648010.
-- Localidad 388: Georef 06623030; departamento 06623; localidad censal 06623030.
-- Localidad 389: Georef 0609101005; departamento 06091; localidad censal 06091010.
-- Localidad 390: Georef 06351035; departamento 06351; localidad censal 06351035.
-- Localidad 391: Georef 0656801003; departamento 06568; localidad censal 06568010.
-- Localidad 392: Georef 06105010; departamento 06105; localidad censal 06105010.
-- Localidad 393: Georef 06406010; departamento 06406; localidad censal 06406010.
-- Localidad 394: Georef 06406020; departamento 06406; localidad censal 06406020.
-- Localidad 395: Georef 06875030; departamento 06875; localidad censal 06875030.
-- Localidad 396: Georef 06595060; departamento 06595; localidad censal 06595060.
-- Localidad 397: Georef 06147050; departamento 06147; localidad censal 06147050.
-- Localidad 398: Georef 06203050; departamento 06203; localidad censal 06203050.
-- Localidad 399: Georef 06408010; departamento 06408; localidad censal 06408010.
-- Localidad 400: Georef 06196040; departamento 06196; localidad censal 06196040.
-- Localidad 401: Georef 06714040; departamento 06714; localidad censal 06714040.
-- Localidad 402: Georef 0651501003; departamento 06515; localidad censal 06515010.
-- Localidad 403: Georef 0627401005; departamento 06274; localidad censal 06274010.
-- Localidad 404: Georef 0625201004; departamento 06252; localidad censal 06252010.
-- Localidad 405: Georef 06770015; departamento 06770; localidad censal 06770015.
-- Localidad 406: Georef 0651501004; departamento 06515; localidad censal 06515010.
-- Localidad 407: Georef 06847010; departamento 06847; localidad censal 06847010.
-- Localidad 408: Georef 0605601003; departamento 06056; localidad censal 06056010.
-- Localidad 409: Georef 06609035; departamento 06609; localidad censal 06609035.
-- Localidad 410: Georef 06112040; departamento 06112; localidad censal 06112040.
-- Localidad 411: Georef 06189060; departamento 06189; localidad censal 06189060.
-- Localidad 412: Georef 06070020; departamento 06070; localidad censal 06070020.
-- Localidad 413: Georef 0642701005; departamento 06427; localidad censal 06427010.
-- Localidad 414: Georef 0624501003; departamento 06245; localidad censal 06245010.
-- Localidad 415: Georef 06410010; departamento 06410; localidad censal 06410010.
-- Localidad 416: Georef 0641001001; departamento 06410; localidad censal 06410010.
-- Localidad 417: Georef 0641001002; departamento 06410; localidad censal 06410010.
-- Localidad 418: Georef 06119070; departamento 06119; localidad censal 06119070.
-- Localidad 419: Georef 0644103011; departamento 06441; localidad censal 06441030.
-- Localidad 420: Georef 06532030; departamento 06532; localidad censal 06532030.
-- Localidad 421: Georef 06602040; departamento 06602; localidad censal 06602040.
-- Localidad 422: Georef 06412010; departamento 06412; localidad censal 06412010.
-- Localidad 423: Georef 0644103012; departamento 06441; localidad censal 06441030.
-- Localidad 424: Georef 0684001006; departamento 06840; localidad censal 06840010.
-- Localidad 425: Georef 06574010; departamento 06574; localidad censal 06574010.
-- Localidad 426: Georef 0627001004; departamento 06270; localidad censal 06270010.
-- Localidad 427: Georef 0602801006; departamento 06028; localidad censal 06028010.
-- Localidad 428: Georef 0644103013; departamento 06441; localidad censal 06441030.
-- Localidad 429: Georef 06623040; departamento 06623; localidad censal 06623040.
-- Localidad 430: Georef 06602050; departamento 06602; localidad censal 06602050.
-- Localidad 431: Georef 06623050; departamento 06623; localidad censal 06623050.
-- Localidad 432: Georef 06462040; departamento 06462; localidad censal 06462040.
-- Localidad 433: Georef 06875040; departamento 06875; localidad censal 06875040.
-- Localidad 434: Georef 06014030; departamento 06014; localidad censal 06014030.
-- Localidad 435: Georef 06105020; departamento 06105; localidad censal 06105020.
-- Localidad 436: Georef 06112060; departamento 06112; localidad censal 06112060.
-- Localidad 437: Georef 06609040; departamento 06609; localidad censal 06609040.
-- Localidad 438: Georef 0609101006; departamento 06091; localidad censal 06091010.
-- Localidad 439: Georef 06581030; departamento 06581; localidad censal 06581030.
-- Localidad 440: Georef 06413050; departamento 06413; localidad censal 06413050.
-- Localidad 441: Georef 06294060; departamento 06294; localidad censal 06294060.
-- Localidad 442: Georef 06518030; departamento 06518; localidad censal 06518030.
-- Localidad 443: Georef 06588050; departamento 06588; localidad censal 06588050.
-- Localidad 444: Georef 0651805001; departamento 06518; localidad censal 06518050.
-- Localidad 445: Georef 06686010; departamento 06686; localidad censal 06686010.
-- Localidad 446: Georef 0651805002; departamento 06518; localidad censal 06518050.
-- Localidad 447: Georef 0627401011; departamento 06274; localidad censal 06274010.
-- Localidad 448: Georef 06826030; departamento 06826; localidad censal 06826030.
-- Localidad 449: Georef 06329030; departamento 06329; localidad censal 06329030.
-- Localidad 450: Georef 06322020; departamento 06322; localidad censal 06322020.
-- Localidad 451: Georef 06042020; departamento 06042; localidad censal 06042020.
-- Localidad 452: Georef 0644103014; departamento 06441; localidad censal 06441030.
-- Localidad 453: Georef 06385020; departamento 06385; localidad censal 06385020.
-- Localidad 454: Georef 06819060; departamento 06819; localidad censal 06819060.
-- Localidad 455: Georef 06714050; departamento 06714; localidad censal 06714050.
-- Localidad 456: Georef 06231040; departamento 06231; localidad censal 06231040.
-- Localidad 457: Georef 06112050; departamento 06112; localidad censal 06112050.
-- Localidad 458: Georef 0663804003; departamento 06638; localidad censal 06638040.
-- Localidad 459: Georef 0686101004; departamento 06861; localidad censal 06861010.
-- Localidad 460: Georef 06140020; departamento 06140; localidad censal 06140020.
-- Localidad 461: Georef 06427010; departamento 06427; localidad censal 06427010.
-- Localidad 462: Georef 06007060; departamento 06007; localidad censal 06007060.
-- Localidad 463: Georef 06441030; departamento 06441; localidad censal 06441030.
-- Localidad 464: Georef 0656001003; departamento 06560; localidad censal 06560010.
-- Localidad 465: Georef 06224040; departamento 06224; localidad censal 06224040.
-- Localidad 466: Georef 06147060; departamento 06147; localidad censal 06147060.
-- Localidad 467: Georef 0642701006; departamento 06427; localidad censal 06427010.
-- Localidad 468: Georef 0627001005; departamento 06270; localidad censal 06270010.
-- Localidad 469: Georef 06623060; departamento 06623; localidad censal 06623060.
-- Localidad 470: Georef 06308020; departamento 06308; localidad censal 06308020.
-- Localidad 471: Georef 06399050; departamento 06399; localidad censal 06399050.
-- Localidad 472: Georef 06218015; departamento 06218; localidad censal 06218015.
-- Localidad 473: Georef 06434010; departamento 06434; localidad censal 06434010.
-- Localidad 474: Georef 0643401002; departamento 06434; localidad censal 06434010.
-- Localidad 475: Georef 0643401003; departamento 06434; localidad censal 06434010.
-- Localidad 476: Georef 06413060; departamento 06413; localidad censal 06413060.
-- Localidad 477: Georef 06448010; departamento 06448; localidad censal 06448010.
-- Localidad 478: Georef 06196050; departamento 06196; localidad censal 06196050.
-- Localidad 479: Georef 06511010; departamento 06511; localidad censal 06511010.
-- Localidad 480: Georef 06665020; departamento 06665; localidad censal 06665020.
-- Localidad 481: Georef 06686020; departamento 06686; localidad censal 06686020.
-- Localidad 482: Georef 06700055; departamento 06700; localidad censal 06700055.
-- Localidad 483: Georef 06455030; departamento 06455; localidad censal 06455030.
-- Localidad 484: Georef 06574020; departamento 06574; localidad censal 06574020.
-- Localidad 485: Georef 06322030; departamento 06322; localidad censal 06322030.
-- Localidad 486: Georef 06469070; departamento 06469; localidad censal 06469070.
-- Localidad 487: Georef 06462050; departamento 06462; localidad censal 06462050.
-- Localidad 488: Georef 0649706003; departamento 06497; localidad censal 06497060.
-- Localidad 489: Georef 06322040; departamento 06322; localidad censal 06322040.
-- Localidad 490: Georef 0653901001; departamento 06539; localidad censal 06539010.
-- Localidad 491: Georef 06476020; departamento 06476; localidad censal 06476020.
-- Localidad 492: Georef 06882040; departamento 06882; localidad censal 06882040.
-- Localidad 493: Georef 06469080; departamento 06469; localidad censal 06469080.
-- Localidad 494: Georef 0644103016; departamento 06441; localidad censal 06441030.
-- Localidad 495: Georef 0649001002; departamento 06490; localidad censal 06490010.
-- Localidad 496: Georef 06476030; departamento 06476; localidad censal 06476030.
-- Localidad 497: Georef 06483040; departamento 06483; localidad censal 06483040.
-- Localidad 498: Georef 0684001007; departamento 06840; localidad censal 06840010.
-- Localidad 499: Georef 0625201005; departamento 06252; localidad censal 06252010.
-- Localidad 500: Georef 06343020; departamento 06343; localidad censal 06343020.
-- Localidad 501: Georef 06490010; departamento 06490; localidad censal 06490010.
-- Localidad 502: Georef 0642701007; departamento 06427; localidad censal 06427010.
-- Localidad 503: Georef 0602801007; departamento 06028; localidad censal 06028010.
-- Localidad 504: Georef 06084030; departamento 06084; localidad censal 06084030.
-- Localidad 505: Georef 06651070; departamento 06651; localidad censal 06651070.
-- Localidad 506: Georef 06210030; departamento 06210; localidad censal 06210030.
-- Localidad 507: Georef 0663804004; departamento 06638; localidad censal 06638040.
-- Localidad 508: Georef 06266040; departamento 06266; localidad censal 06266040.
-- Localidad 509: Georef 0644103017; departamento 06441; localidad censal 06441030.
-- Localidad 510: Georef 06686030; departamento 06686; localidad censal 06686030.
-- Localidad 511: Georef 06505030; departamento 06505; localidad censal 06505030.
-- Localidad 512: Georef 06063020; departamento 06063; localidad censal 06063020.
-- Localidad 513: Georef 0651501005; departamento 06515; localidad censal 06515010.
-- Localidad 514: Georef 0609801005; departamento 06098; localidad censal 06098010.
-- Localidad 515: Georef 06385030; departamento 06385; localidad censal 06385030.
-- Localidad 516: Georef 06854050; departamento 06854; localidad censal 06854050.
-- Localidad 517: Georef 0626001003; departamento 06260; localidad censal 06260010.
-- Localidad 518: Georef 06497060; departamento 06497; localidad censal 06497060.
-- Localidad 519: Georef 06609050; departamento 06609; localidad censal 06609050.
-- Localidad 520: Georef 06505040; departamento 06505; localidad censal 06505040.
-- Localidad 521: Georef 06511020; departamento 06511; localidad censal 06511020.
-- Localidad 522: Georef 0602801008; departamento 06028; localidad censal 06028010.
-- Localidad 523: Georef 06515010; departamento 06515; localidad censal 06515010.
-- Localidad 524: Georef 0644103018; departamento 06441; localidad censal 06441030.
-- Localidad 525: Georef 06588060; departamento 06588; localidad censal 06588060.
-- Localidad 526: Georef 06466020; departamento 06466; localidad censal 06466020.
-- Localidad 527: Georef 06623070; departamento 06623; localidad censal 06623070.
-- Localidad 528: Georef 0663804005; departamento 06638; localidad censal 06638040.
-- Localidad 529: Georef 0663804006; departamento 06638; localidad censal 06638040.
-- Localidad 530: Georef 0625201007; departamento 06252; localidad censal 06252010.
-- Localidad 531: Georef 0663804007; departamento 06638; localidad censal 06638040.
-- Localidad 532: Georef 06518040; departamento 06518; localidad censal 06518040.
-- Localidad 533: Georef 06518050; departamento 06518; localidad censal 06518050.
-- Localidad 534: Georef 06357110; departamento 06357; localidad censal 06357110.
-- Localidad 535: Georef 06280020; departamento 06280; localidad censal 06280020.
-- Localidad 536: Georef 0642004001; departamento 06420; localidad censal 06420040.
-- Localidad 537: Georef 06588070; departamento 06588; localidad censal 06588070.
-- Localidad 538: Georef 06525020; departamento 06525; localidad censal 06525020.
-- Localidad 539: Georef 06791040; departamento 06791; localidad censal 06791040.
-- Localidad 540: Georef 0653901002; departamento 06539; localidad censal 06539010.
-- Localidad 541: Georef 06623080; departamento 06623; localidad censal 06623080.
-- Localidad 542: Georef 06623090; departamento 06623; localidad censal 06623090.
-- Localidad 543: Georef 06189020; departamento 06189; localidad censal 06189020.
-- Localidad 544: Georef 0684001008; departamento 06840; localidad censal 06840010.
-- Localidad 545: Georef 0675601004; departamento 06756; localidad censal 06756010.
-- Localidad 546: Georef 06392060; departamento 06392; localidad censal 06392060.
-- Localidad 547: Georef 0625201006; departamento 06252; localidad censal 06252010.
-- Localidad 548: Georef 06147070; departamento 06147; localidad censal 06147070.
-- Localidad 549: Georef 06134050; departamento 06134; localidad censal 06134050.
-- Localidad 550: Georef 06875050; departamento 06875; localidad censal 06875050.
-- Localidad 551: Georef 06007070; departamento 06007; localidad censal 06007070.
-- Localidad 552: Georef 06021030; departamento 06021; localidad censal 06021030.
-- Localidad 553: Georef 06112070; departamento 06112; localidad censal 06112070.
-- Localidad 554: Georef 06280030; departamento 06280; localidad censal 06280030.
-- Localidad 555: Georef 06875060; departamento 06875; localidad censal 06875060.
-- Localidad 556: Georef 06532020; departamento 06532; localidad censal 06532020.
-- Localidad 557: Georef 06539010; departamento 06539; localidad censal 06539010.
-- Localidad 558: Georef 06833050; departamento 06833; localidad censal 06833050.
-- Localidad 559: Georef 0602801009; departamento 06028; localidad censal 06028010.
-- Localidad 560: Georef 06679040; departamento 06679; localidad censal 06679040.
-- Localidad 561: Georef 06280040; departamento 06280; localidad censal 06280040.
-- Localidad 562: Georef 06147080; departamento 06147; localidad censal 06147080.
-- Localidad 563: Georef 06609060; departamento 06609; localidad censal 06609060.
-- Localidad 564: Georef 0643401004; departamento 06434; localidad censal 06434010.
-- Localidad 565: Georef 0626001004; departamento 06260; localidad censal 06260010.
-- Localidad 566: Georef 06553020; departamento 06553; localidad censal 06553020.
-- Localidad 567: Georef 06224050; departamento 06224; localidad censal 06224050.
-- Localidad 568: Georef 06588080; departamento 06588; localidad censal 06588080.
-- Localidad 569: Georef 06560010; departamento 06560; localidad censal 06560010.
-- Localidad 570: Georef 06568010; departamento 06568; localidad censal 06568010.
-- Localidad 571: Georef 06413070; departamento 06413; localidad censal 06413070.
-- Localidad 572: Georef 0676001003; departamento 06760; localidad censal 06760010.
-- Localidad 573: Georef 0686101005; departamento 06861; localidad censal 06861010.
-- Localidad 574: Georef 06063030; departamento 06063; localidad censal 06063030.
-- Localidad 575: Georef 06574030; departamento 06574; localidad censal 06574030.
-- Localidad 576: Georef 0658104001; departamento 06581; localidad censal 06581040.
-- Localidad 577: Georef 06581040; departamento 06581; localidad censal 06581040.
-- Localidad 578: Georef 06581050; departamento 06581; localidad censal 06581050.
-- Localidad 579: Georef 06854060; departamento 06854; localidad censal 06854060.
-- Localidad 580: Georef 06588090; departamento 06588; localidad censal 06588090.
-- Localidad 581: Georef 06609070; departamento 06609; localidad censal 06609070.
-- Localidad 582: Georef 06210040; departamento 06210; localidad censal 06210040.
-- Localidad 583: Georef 06770020; departamento 06770; localidad censal 06770020.
-- Localidad 584: Georef 06742020; departamento 06742; localidad censal 06742020.
-- Localidad 585: Georef 06112080; departamento 06112; localidad censal 06112080.
-- Localidad 586: Georef 06595070; departamento 06595; localidad censal 06595070.
-- Localidad 587: Georef 06119080; departamento 06119; localidad censal 06119080.
-- Localidad 588: Georef 06497070; departamento 06497; localidad censal 06497070.
-- Localidad 589: Georef 0686101006; departamento 06861; localidad censal 06861010.
-- Localidad 590: Georef 0649706008; departamento 06497; localidad censal 06497060.
-- Localidad 591: Georef 06147090; departamento 06147; localidad censal 06147090.
-- Localidad 592: Georef 06833060; departamento 06833; localidad censal 06833060.
-- Localidad 593: Georef 06189070; departamento 06189; localidad censal 06189070.
-- Localidad 594: Georef 0664401002; departamento 06644; localidad censal 06644010.
-- Localidad 595: Georef 0684001010; departamento 06840; localidad censal 06840010.
-- Localidad 596: Georef 0626606002; departamento 06266; localidad censal 06266060.
-- Localidad 597: Georef 06266060; departamento 06266; localidad censal 06266060.
-- Localidad 598: Georef 06413055; departamento 06413; localidad censal 06413055.
-- Localidad 599: Georef 06189075; departamento 06189; localidad censal 06189075.
-- Localidad 600: Georef 06455040; departamento 06455; localidad censal 06455040.
-- Localidad 601: Georef 06203060; departamento 06203; localidad censal 06203060.
-- Localidad 602: Georef 0656001005; departamento 06560; localidad censal 06560010.
-- Localidad 603: Georef 06469090; departamento 06469; localidad censal 06469090.
-- Localidad 604: Georef 06588110; departamento 06588; localidad censal 06588110.
-- Localidad 605: Georef 06105040; departamento 06105; localidad censal 06105040.
-- Localidad 606: Georef 0626606003; departamento 06266; localidad censal 06266060.
-- Localidad 607: Georef 06336030; departamento 06336; localidad censal 06336030.
-- Localidad 608: Georef 06175030; departamento 06175; localidad censal 06175030.
-- Localidad 609: Georef 06854070; departamento 06854; localidad censal 06854070.
-- Localidad 610: Georef 06875070; departamento 06875; localidad censal 06875070.
-- Localidad 611: Georef 06609080; departamento 06609; localidad censal 06609080.
-- Localidad 612: Georef 06616030; departamento 06616; localidad censal 06616030.
-- Localidad 613: Georef 0609101007; departamento 06091; localidad censal 06091010.
-- Localidad 614: Georef 06665030; departamento 06665; localidad censal 06665030.
-- Localidad 615: Georef 06623100; departamento 06623; localidad censal 06623100.
-- Localidad 616: Georef 06392070; departamento 06392; localidad censal 06392070.
-- Localidad 617: Georef 06392080; departamento 06392; localidad censal 06392080.
-- Localidad 618: Georef 06476040; departamento 06476; localidad censal 06476040.
-- Localidad 619: Georef 06700060; departamento 06700; localidad censal 06700060.
-- Localidad 620: Georef 06630020; departamento 06630; localidad censal 06630020.
-- Localidad 621: Georef 06638040; departamento 06638; localidad censal 06638040.
-- Localidad 622: Georef 06644010; departamento 06644; localidad censal 06644010.
-- Localidad 623: Georef 0603501006; departamento 06035; localidad censal 06035010.
-- Localidad 624: Georef 06623110; departamento 06623; localidad censal 06623110.
-- Localidad 625: Georef 06655030; departamento 06655; localidad censal 06655030.
-- Localidad 626: Georef 06105050; departamento 06105; localidad censal 06105050.
-- Localidad 627: Georef 06021040; departamento 06021; localidad censal 06021040.
-- Localidad 628: Georef 0609101008; departamento 06091; localidad censal 06091010.
-- Localidad 629: Georef 0651806004; departamento 06518; localidad censal 06518060.
-- Localidad 630: Georef 06329050; departamento 06329; localidad censal 06329050.
-- Localidad 631: Georef 06707050; departamento 06707; localidad censal 06707050.
-- Localidad 632: Georef 06322050; departamento 06322; localidad censal 06322050.
-- Localidad 633: Georef 0653901004; departamento 06539; localidad censal 06539010.
-- Localidad 634: Georef 06277030; departamento 06277; localidad censal 06277030.
-- Localidad 635: Georef 0663804009; departamento 06638; localidad censal 06638040.
-- Localidad 636: Georef 06648010; departamento 06648; localidad censal 06648010.
-- Localidad 637: Georef 06651080; departamento 06651; localidad censal 06651080.
-- Localidad 638: Georef 06770030; departamento 06770; localidad censal 06770030.
-- Localidad 639: Georef 06161020; departamento 06161; localidad censal 06161020.
-- Localidad 640: Georef 06448020; departamento 06448; localidad censal 06448020.
-- Localidad 641: Georef 06448030; departamento 06448; localidad censal 06448030.
-- Localidad 642: Georef 06182030; departamento 06182; localidad censal 06182030.
-- Localidad 643: Georef 06655040; departamento 06655; localidad censal 06655040.
-- Localidad 644: Georef 0624501004; departamento 06245; localidad censal 06245010.
-- Localidad 645: Georef 0635711004; departamento 06357; localidad censal 06357110.
-- Localidad 646: Georef 06721010; departamento 06721; localidad censal 06721010.
-- Localidad 647: Georef 0658104002; departamento 06581; localidad censal 06581040.
-- Localidad 648: Georef 06658010; departamento 06658; localidad censal 06658010.
-- Localidad 649: Georef 0665801007; departamento 06658; localidad censal 06658010.
-- Localidad 650: Georef 0602801010; departamento 06028; localidad censal 06028010.
-- Localidad 651: Georef 0642701008; departamento 06427; localidad censal 06427010.
-- Localidad 652: Georef 06686040; departamento 06686; localidad censal 06686040.
-- Localidad 653: Georef 06665040; departamento 06665; localidad censal 06665040.
-- Localidad 654: Georef 06224060; departamento 06224; localidad censal 06224060.
-- Localidad 655: Georef 06581060; departamento 06581; localidad censal 06581060.
-- Localidad 656: Georef 0642701009; departamento 06427; localidad censal 06427010.
-- Localidad 657: Georef 06063040; departamento 06063; localidad censal 06063040.
-- Localidad 658: Georef 06623120; departamento 06623; localidad censal 06623120.
-- Localidad 659: Georef 06343030; departamento 06343; localidad censal 06343030.
-- Localidad 660: Georef 0609101009; departamento 06091; localidad censal 06091010.
-- Localidad 661: Georef 06672010; departamento 06672; localidad censal 06672010.
-- Localidad 662: Georef 06210050; departamento 06210; localidad censal 06210050.
-- Localidad 663: Georef 06595080; departamento 06595; localidad censal 06595080.
-- Localidad 664: Georef 0684001011; departamento 06840; localidad censal 06840010.
-- Localidad 665: Georef 0643401005; departamento 06434; localidad censal 06434010.
-- Localidad 666: Georef 06833070; departamento 06833; localidad censal 06833070.
-- Localidad 667: Georef 0680501008; departamento 06805; localidad censal 06805010.
-- Localidad 668: Georef 0680501009; departamento 06805; localidad censal 06805010.
-- Localidad 669: Georef 0644103019; departamento 06441; localidad censal 06441030.
-- Localidad 670: Georef 06770040; departamento 06770; localidad censal 06770040.
-- Localidad 671: Georef 06007080; departamento 06007; localidad censal 06007080.
-- Localidad 672: Georef 06686050; departamento 06686; localidad censal 06686050.
-- Localidad 673: Georef 0663804010; departamento 06638; localidad censal 06638040.
-- Localidad 674: Georef 06469100; departamento 06469; localidad censal 06469100.
-- Localidad 675: Georef 06686060; departamento 06686; localidad censal 06686060.
-- Localidad 676: Georef 06679050; departamento 06679; localidad censal 06679050.
-- Localidad 677: Georef 06693020; departamento 06693; localidad censal 06693020.
-- Localidad 678: Georef 0644103020; departamento 06441; localidad censal 06441030.
-- Localidad 679: Georef 06700070; departamento 06700; localidad censal 06700070.
-- Localidad 680: Georef 0684001012; departamento 06840; localidad censal 06840010.
-- Localidad 681: Georef 06413080; departamento 06413; localidad censal 06413080.
-- Localidad 682: Georef 06707060; departamento 06707; localidad censal 06707060.
-- Localidad 683: Georef 06231060; departamento 06231; localidad censal 06231060.
-- Localidad 684: Georef 06819020; departamento 06819; localidad censal 06819020.
-- Localidad 685: Georef 06721020; departamento 06721; localidad censal 06721020.
-- Localidad 686: Georef 06714060; departamento 06714; localidad censal 06714060.
-- Localidad 687: Georef 06483050; departamento 06483; localidad censal 06483050.
-- Localidad 688: Georef 06063050; departamento 06063; localidad censal 06063050.
-- Localidad 689: Georef 06728040; departamento 06728; localidad censal 06728040.
-- Localidad 690: Georef 06735020; departamento 06735; localidad censal 06735020.
-- Localidad 691: Georef 0653901005; departamento 06539; localidad censal 06539010.
-- Localidad 692: Georef 06609090; departamento 06609; localidad censal 06609090.
-- Localidad 693: Georef 06105060; departamento 06105; localidad censal 06105060.
-- Localidad 694: Georef 06742030; departamento 06742; localidad censal 06742030.
-- Localidad 695: Georef 06420030; departamento 06420; localidad censal 06420030.
-- Localidad 696: Georef 06385040; departamento 06385; localidad censal 06385040.
-- Localidad 697: Georef 06854080; departamento 06854; localidad censal 06854080.
-- Localidad 698: Georef 06609020; departamento 06609; localidad censal 06609020.
-- Localidad 699: Georef 06749010; departamento 06749; localidad censal 06749010.
-- Localidad 700: Georef 06833080; departamento 06833; localidad censal 06833080.
-- Localidad 701: Georef 0602801011; departamento 06028; localidad censal 06028010.
-- Localidad 702: Georef 0665801008; departamento 06658; localidad censal 06658010.
-- Localidad 703: Georef 06651090; departamento 06651; localidad censal 06651090.
-- Localidad 704: Georef 06756010; departamento 06756; localidad censal 06756010.
-- Localidad 705: Georef 0602801012; departamento 06028; localidad censal 06028010.
-- Localidad 706: Georef 0627401003; departamento 06274; localidad censal 06274010.
-- Localidad 707: Georef 0642701010; departamento 06427; localidad censal 06427010.
-- Localidad 708: Georef 06476050; departamento 06476; localidad censal 06476050.
-- Localidad 709: Georef 06679060; departamento 06679; localidad censal 06679060.
-- Localidad 710: Georef 06833090; departamento 06833; localidad censal 06833090.
-- Localidad 711: Georef 06760010; departamento 06760; localidad censal 06760010.
-- Localidad 712: Georef 06547020; departamento 06547; localidad censal 06547020.
-- Localidad 713: Georef 06763050; departamento 06763; localidad censal 06763050.
-- Localidad 714: Georef 06770050; departamento 06770; localidad censal 06770050.
-- Localidad 715: Georef 06189080; departamento 06189; localidad censal 06189080.
-- Localidad 716: Georef 06224070; departamento 06224; localidad censal 06224070.
-- Localidad 717: Georef 06778020; departamento 06778; localidad censal 06778020.
-- Localidad 718: Georef 06679070; departamento 06679; localidad censal 06679070.
-- Localidad 719: Georef 06518060; departamento 06518; localidad censal 06518060.
-- Localidad 720: Georef 06070030; departamento 06070; localidad censal 06070030.
-- Localidad 721: Georef 0651806006; departamento 06518; localidad censal 06518060.
-- Localidad 722: Georef 06392090; departamento 06392; localidad censal 06392090.
-- Localidad 723: Georef 06770060; departamento 06770; localidad censal 06770060.
-- Localidad 724: Georef 06595090; departamento 06595; localidad censal 06595090.
-- Localidad 725: Georef 06203080; departamento 06203; localidad censal 06203080.
-- Localidad 726: Georef 06392100; departamento 06392; localidad censal 06392100.
-- Localidad 727: Georef 06134060; departamento 06134; localidad censal 06134060.
-- Localidad 728: Georef 0663804011; departamento 06638; localidad censal 06638040.
-- Localidad 729: Georef 0642004002; departamento 06420; localidad censal 06420040.
-- Localidad 730: Georef 06420040; departamento 06420; localidad censal 06420040.
-- Localidad 731: Georef 06511030; departamento 06511; localidad censal 06511030.
-- Localidad 732: Georef 06147095; departamento 06147; localidad censal 06147095.
-- Localidad 733: Georef 0684001013; departamento 06840; localidad censal 06840010.
-- Localidad 734: Georef 0603501007; departamento 06035; localidad censal 06035010.
-- Localidad 735: Georef 06175040; departamento 06175; localidad censal 06175040.
-- Localidad 736: Georef 06238020; departamento 06238; localidad censal 06238020.
-- Localidad 737: Georef 06595100; departamento 06595; localidad censal 06595100.
-- Localidad 738: Georef 06819030; departamento 06819; localidad censal 06819030.
-- Localidad 739: Georef 06357120; departamento 06357; localidad censal 06357120.
-- Localidad 740: Georef 06595110; departamento 06595; localidad censal 06595110.
-- Localidad 741: Georef 06147100; departamento 06147; localidad censal 06147100.
-- Localidad 742: Georef 06686070; departamento 06686; localidad censal 06686070.
-- Localidad 743: Georef 06042030; departamento 06042; localidad censal 06042030.
-- Localidad 744: Georef 06728050; departamento 06728; localidad censal 06728050.
-- Localidad 745: Georef 06602060; departamento 06602; localidad censal 06602060.
-- Localidad 746: Georef 06784020; departamento 06784; localidad censal 06784020.
-- Localidad 747: Georef 06679080; departamento 06679; localidad censal 06679080.
-- Localidad 748: Georef 06476060; departamento 06476; localidad censal 06476060.
-- Localidad 749: Georef 06791050; departamento 06791; localidad censal 06791050.
-- Localidad 750: Georef 06798020; departamento 06798; localidad censal 06798020.
-- Localidad 751: Georef 0642701011; departamento 06427; localidad censal 06427010.
-- Localidad 752: Georef 06084040; departamento 06084; localidad censal 06084040.
-- Localidad 753: Georef 0649001004; departamento 06490; localidad censal 06490010.
-- Localidad 754: Georef 06875080; departamento 06875; localidad censal 06875080.
-- Localidad 755: Georef 06805010; departamento 06805; localidad censal 06805010.
-- Localidad 756: Georef 06154040; departamento 06154; localidad censal 06154040.
-- Localidad 757: Georef 0644103021; departamento 06441; localidad censal 06441030.
-- Localidad 758: Georef 06819040; departamento 06819; localidad censal 06819040.
-- Localidad 759: Georef 06497090; departamento 06497; localidad censal 06497090.
-- Localidad 760: Georef 0641201003; departamento 06412; localidad censal 06412010.
-- Localidad 761: Georef 0651501007; departamento 06515; localidad censal 06515010.
-- Localidad 762: Georef 0663804012; departamento 06638; localidad censal 06638040.
-- Localidad 763: Georef 0644103022; departamento 06441; localidad censal 06441030.
-- Localidad 764: Georef 06826050; departamento 06826; localidad censal 06826050.
-- Localidad 765: Georef 06154050; departamento 06154; localidad censal 06154050.
-- Localidad 766: Georef 06833100; departamento 06833; localidad censal 06833100.
-- Localidad 767: Georef 06840010; departamento 06840; localidad censal 06840010.
-- Localidad 768: Georef 06847020; departamento 06847; localidad censal 06847020.
-- Localidad 769: Georef 06819050; departamento 06819; localidad censal 06819050.
-- Localidad 770: Georef 06161030; departamento 06161; localidad censal 06161030.
-- Localidad 771: Georef 0627001006; departamento 06270; localidad censal 06270010.
-- Localidad 772: Georef 06469110; departamento 06469; localidad censal 06469110.
-- Localidad 773: Georef 0680501007; departamento 06805; localidad censal 06805010.
-- Localidad 774: Georef 06826060; departamento 06826; localidad censal 06826060.
-- Localidad 775: Georef 0656001006; departamento 06560; localidad censal 06560010.
-- Localidad 776: Georef 0649001005; departamento 06490; localidad censal 06490010.
-- Localidad 777: Georef 06042040; departamento 06042; localidad censal 06042040.
-- Localidad 778: Georef 06105070; departamento 06105; localidad censal 06105070.
-- Localidad 779: Georef 06134070; departamento 06134; localidad censal 06134070.
-- Localidad 780: Georef 0637101022; departamento 06371; localidad censal 06371010.
-- Localidad 781: Georef 0637101023; departamento 06371; localidad censal 06371010.
-- Localidad 782: Georef 06854090; departamento 06854; localidad censal 06854090.
-- Localidad 783: Georef 0643401006; departamento 06434; localidad censal 06434010.
-- Localidad 784: Georef 0664401004; departamento 06644; localidad censal 06644010.
-- Localidad 785: Georef 06014040; departamento 06014; localidad censal 06014040.
-- Localidad 786: Georef 06462060; departamento 06462; localidad censal 06462060.
-- Localidad 787: Georef 06798030; departamento 06798; localidad censal 06798030.
-- Localidad 788: Georef 06655050; departamento 06655; localidad censal 06655050.
-- Localidad 789: Georef 06861010; departamento 06861; localidad censal 06861010.
-- Localidad 790: Georef 0674901002; departamento 06749; localidad censal 06749010.
-- Localidad 791: Georef 06505060; departamento 06505; localidad censal 06505060.
-- Localidad 792: Georef 0675601006; departamento 06756; localidad censal 06756010.
-- Localidad 793: Georef 0686101008; departamento 06861; localidad censal 06861010.
-- Localidad 794: Georef 06595120; departamento 06595; localidad censal 06595120.
-- Localidad 795: Georef 06070040; departamento 06070; localidad censal 06070040.
-- Localidad 796: Georef 06623130; departamento 06623; localidad censal 06623130.
-- Localidad 797: Georef 0609801006; departamento 06098; localidad censal 06098010.
-- Localidad 798: Georef 0659511002; departamento 06595; localidad censal 06595110.
-- Localidad 799: Georef 0663804013; departamento 06638; localidad censal 06638040.
-- Localidad 800: Georef 0637101005; departamento 06371; localidad censal 06371010.
-- Localidad 801: Georef 0637101006; departamento 06371; localidad censal 06371010.
-- Localidad 802: Georef 0637101007; departamento 06371; localidad censal 06371010.
-- Localidad 803: Georef 0605601004; departamento 06056; localidad censal 06056010.
-- Localidad 804: Georef 0684001014; departamento 06840; localidad censal 06840010.
-- Localidad 805: Georef 0627401006; departamento 06274; localidad censal 06274010.
-- Localidad 806: Georef 06084050; departamento 06084; localidad censal 06084050.
-- Localidad 807: Georef 06651100; departamento 06651; localidad censal 06651100.
-- Localidad 808: Georef 0624501005; departamento 06245; localidad censal 06245010.
-- Localidad 809: Georef 0649001006; departamento 06490; localidad censal 06490010.
-- Localidad 810: Georef 0637101008; departamento 06371; localidad censal 06371010.
-- Localidad 811: Georef 0637101009; departamento 06371; localidad censal 06371010.
-- Localidad 812: Georef 0651501008; departamento 06515; localidad censal 06515010.
-- Localidad 813: Georef 0618203002; departamento 06182; localidad censal 06182030.
-- Localidad 814: Georef 0609801007; departamento 06098; localidad censal 06098010.
-- Localidad 815: Georef 0603501008; departamento 06035; localidad censal 06035010.
-- Localidad 816: Georef 0642701013; departamento 06427; localidad censal 06427010.
-- Localidad 817: Georef 0644103023; departamento 06441; localidad censal 06441030.
-- Localidad 818: Georef 0644103024; departamento 06441; localidad censal 06441030.
-- Localidad 819: Georef 0609101010; departamento 06091; localidad censal 06091010.
-- Localidad 820: Georef 06763060; departamento 06763; localidad censal 06763060.
-- Localidad 821: Georef 06728060; departamento 06728; localidad censal 06728060.
-- Localidad 822: Georef 0605601005; departamento 06056; localidad censal 06056010.
-- Localidad 823: Georef 0649001007; departamento 06490; localidad censal 06490010.
-- Localidad 824: Georef 0649706005; departamento 06497; localidad censal 06497060.
-- Localidad 825: Georef 0649706006; departamento 06497; localidad censal 06497060.
-- Localidad 826: Georef 06351040; departamento 06351; localidad censal 06351040.
-- Localidad 827: Georef 0644103025; departamento 06441; localidad censal 06441030.
-- Localidad 828: Georef 0637101010; departamento 06371; localidad censal 06371010.
-- Localidad 829: Georef 0637101011; departamento 06371; localidad censal 06371010.
-- Localidad 830: Georef 0637101012; departamento 06371; localidad censal 06371010.
-- Localidad 831: Georef 0637101013; departamento 06371; localidad censal 06371010.
-- Localidad 832: Georef 06665050; departamento 06665; localidad censal 06665050.
-- Localidad 833: Georef 06868020; departamento 06868; localidad censal 06868020.
-- Localidad 834: Georef 0641001003; departamento 06410; localidad censal 06410010.
-- Localidad 835: Georef 0637101014; departamento 06371; localidad censal 06371010.
-- Localidad 836: Georef 0637101015; departamento 06371; localidad censal 06371010.
-- Localidad 837: Georef 0637101016; departamento 06371; localidad censal 06371010.
-- Localidad 838: Georef 06021050; departamento 06021; localidad censal 06021050.
-- Localidad 839: Georef 0609801008; departamento 06098; localidad censal 06098010.
-- Localidad 840: Georef 06651110; departamento 06651; localidad censal 06651110.
-- Localidad 841: Georef 0637101017; departamento 06371; localidad censal 06371010.
-- Localidad 842: Georef 0637101018; departamento 06371; localidad censal 06371010.
-- Localidad 843: Georef 06203100; departamento 06203; localidad censal 06203100.
-- Localidad 844: Georef 0665801009; departamento 06658; localidad censal 06658010.
-- Localidad 845: Georef 06063060; departamento 06063; localidad censal 06063060.
-- Localidad 846: Georef 06735030; departamento 06735; localidad censal 06735030.
-- Localidad 847: Georef 0637101019; departamento 06371; localidad censal 06371010.
-- Localidad 848: Georef 0642701014; departamento 06427; localidad censal 06427010.
-- Localidad 849: Georef 0637101020; departamento 06371; localidad censal 06371010.
-- Localidad 850: Georef 06105080; departamento 06105; localidad censal 06105080.
-- Localidad 851: Georef 0637101021; departamento 06371; localidad censal 06371010.
-- Localidad 852: Georef 06175020; departamento 06175; localidad censal 06175020.
-- Localidad 853: Georef 06686080; departamento 06686; localidad censal 06686080.
-- Localidad 854: Georef 06021060; departamento 06021; localidad censal 06021060.
-- Localidad 855: Georef 0686101009; departamento 06861; localidad censal 06861010.
-- Localidad 856: Georef 06574040; departamento 06574; localidad censal 06574040.
-- Localidad 857: Georef 0644103026; departamento 06441; localidad censal 06441030.
-- Localidad 858: Georef 0609801009; departamento 06098; localidad censal 06098010.
-- Localidad 859: Georef 06021070; departamento 06021; localidad censal 06021070.
-- Localidad 860: Georef 0668606003; departamento 06686; localidad censal 06686060.
-- Localidad 861: Georef 06218030; departamento 06218; localidad censal 06218030.
-- Localidad 862: Georef 0637101024; departamento 06371; localidad censal 06371010.
-- Localidad 863: Georef 0637101025; departamento 06371; localidad censal 06371010.
-- Localidad 864: Georef 0644103027; departamento 06441; localidad censal 06441030.
-- Localidad 865: Georef 0609801010; departamento 06098; localidad censal 06098010.
-- Localidad 866: Georef 0609801011; departamento 06098; localidad censal 06098010.
-- Localidad 867: Georef 0684001015; departamento 06840; localidad censal 06840010.
-- Localidad 868: Georef 06833110; departamento 06833; localidad censal 06833110.
-- Localidad 869: Georef 0663804014; departamento 06638; localidad censal 06638040.
-- Localidad 870: Georef 06351050; departamento 06351; localidad censal 06351050.
-- Localidad 871: Georef 06728070; departamento 06728; localidad censal 06728070.
-- Localidad 872: Georef 06392110; departamento 06392; localidad censal 06392110.
-- Localidad 873: Georef 0637101026; departamento 06371; localidad censal 06371010.
-- Localidad 874: Georef 0609801012; departamento 06098; localidad censal 06098010.
-- Localidad 875: Georef 06623140; departamento 06623; localidad censal 06623140.
-- Localidad 876: Georef 0627401007; departamento 06274; localidad censal 06274010.
-- Localidad 877: Georef 0627401008; departamento 06274; localidad censal 06274010.
-- Localidad 878: Georef 0640801002; departamento 06408; localidad censal 06408010.
-- Localidad 879: Georef 0656801005; departamento 06568; localidad censal 06568010.
-- Localidad 880: Georef 06392120; departamento 06392; localidad censal 06392120.
-- Localidad 881: Georef 0627401009; departamento 06274; localidad censal 06274010.
-- Localidad 882: Georef 06819070; departamento 06819; localidad censal 06819070.
-- Localidad 883: Georef 0637101027; departamento 06371; localidad censal 06371010.
-- Localidad 884: Georef 0609801013; departamento 06098; localidad censal 06098010.
-- Localidad 885: Georef 06602070; departamento 06602; localidad censal 06602070.
-- Localidad 886: Georef 06343040; departamento 06343; localidad censal 06343040.
-- Localidad 887: Georef 06329060; departamento 06329; localidad censal 06329060.
-- Localidad 888: Georef 06077030; departamento 06077; localidad censal 06077030.
-- Localidad 889: Georef 0642701015; departamento 06427; localidad censal 06427010.
-- Localidad 890: Georef 0674901003; departamento 06749; localidad censal 06749010.
-- Localidad 891: Georef 06518070; departamento 06518; localidad censal 06518070.
-- Localidad 892: Georef 06112090; departamento 06112; localidad censal 06112090.
-- Localidad 893: Georef 0603501009; departamento 06035; localidad censal 06035010.
-- Localidad 894: Georef 0640801003; departamento 06408; localidad censal 06408010.
-- Localidad 895: Georef 06007110; departamento 06007; localidad censal 06007110.
-- Localidad 896: Georef 06882050; departamento 06882; localidad censal 06882050.
-- Localidad 897: Georef 06385050; departamento 06385; localidad censal 06385050.
-- Localidad 898: Georef 0663804015; departamento 06638; localidad censal 06638040.
-- Localidad 899: Georef 06547030; departamento 06547; localidad censal 06547030.
-- Localidad 900: Georef 1002106001; departamento 10021; localidad censal 10021060.
-- Localidad 901: Georef 10098010; departamento 10098; localidad censal 10098010.
-- Localidad 902: Georef 1002106002; departamento 10021; localidad censal 10021060.
-- Localidad 903: Georef 10077010; departamento 10077; localidad censal 10077010.
-- Localidad 904: Georef 10021010; departamento 10021; localidad censal 10021010.
-- Localidad 905: Georef 10014010; departamento 10014; localidad censal 10014010.
-- Localidad 906: Georef 10021020; departamento 10021; localidad censal 10021020.
-- Localidad 907: Georef 10091010; departamento 10091; localidad censal 10091010.
-- Localidad 908: Georef 10105010; departamento 10105; localidad censal 10105010.
-- Localidad 909: Georef 10014020; departamento 10014; localidad censal 10014020.
-- Localidad 910: Georef 10028010; departamento 10028; localidad censal 10028010.
-- Localidad 911: Georef 10098020; departamento 10098; localidad censal 10098020.
-- Localidad 912: Georef 10035010; departamento 10035; localidad censal 10035010.
-- Localidad 913: Georef 10035020; departamento 10035; localidad censal 10035020.
-- Localidad 914: Georef 1002105001; departamento 10021; localidad censal 10021050.
-- Localidad 915: Georef 10042030; departamento 10042; localidad censal 10042030.
-- Localidad 916: Georef 10070010; departamento 10070; localidad censal 10070010.
-- Localidad 917: Georef 1009114001; departamento 10091; localidad censal 10091140.
-- Localidad 918: Georef 10091030; departamento 10091; localidad censal 10091030.
-- Localidad 919: Georef 10021030; departamento 10021; localidad censal 10021030.
-- Localidad 920: Georef 10021040; departamento 10021; localidad censal 10021040.
-- Localidad 921: Georef 10007010; departamento 10007; localidad censal 10007010.
-- Localidad 922: Georef 10042040; departamento 10042; localidad censal 10042040.
-- Localidad 923: Georef 10084020; departamento 10084; localidad censal 10084020.
-- Localidad 924: Georef 10042050; departamento 10042; localidad censal 10042050.
-- Localidad 925: Georef 10042060; departamento 10042; localidad censal 10042060.
-- Localidad 926: Georef 10007020; departamento 10007; localidad censal 10007020.
-- Localidad 927: Georef 10084030; departamento 10084; localidad censal 10084030.
-- Localidad 928: Georef 10042070; departamento 10042; localidad censal 10042070.
-- Localidad 929: Georef 10035030; departamento 10035; localidad censal 10035030.
-- Localidad 930: Georef 10042080; departamento 10042; localidad censal 10042080.
-- Localidad 931: Georef 10105050; departamento 10105; localidad censal 10105050.
-- Localidad 932: Georef 10035040; departamento 10035; localidad censal 10035040.
-- Localidad 933: Georef 10021050; departamento 10021; localidad censal 10021050.
-- Localidad 934: Georef 10056010; departamento 10056; localidad censal 10056010.
-- Localidad 935: Georef 10070020; departamento 10070; localidad censal 10070020.
-- Localidad 936: Georef 10042090; departamento 10042; localidad censal 10042090.
-- Localidad 937: Georef 10070030; departamento 10070; localidad censal 10070030.
-- Localidad 938: Georef 1011204001; departamento 10112; localidad censal 10112040.
-- Localidad 939: Georef 10007030; departamento 10007; localidad censal 10007030.
-- Localidad 940: Georef 10091040; departamento 10091; localidad censal 10091040.
-- Localidad 941: Georef 1009111001; departamento 10091; localidad censal 10091110.
-- Localidad 942: Georef 10091050; departamento 10091; localidad censal 10091050.
-- Localidad 943: Georef 10070040; departamento 10070; localidad censal 10070040.
-- Localidad 944: Georef 10035050; departamento 10035; localidad censal 10035050.
-- Localidad 945: Georef 1006304001; departamento 10063; localidad censal 10063040.
-- Localidad 946: Georef 10021060; departamento 10021; localidad censal 10021060.
-- Localidad 947: Georef 10084040; departamento 10084; localidad censal 10084040.
-- Localidad 948: Georef 10028030; departamento 10028; localidad censal 10028030.
-- Localidad 949: Georef 10112010; departamento 10112; localidad censal 10112010.
-- Localidad 950: Georef 10021070; departamento 10021; localidad censal 10021070.
-- Localidad 951: Georef 10091060; departamento 10091; localidad censal 10091060.
-- Localidad 952: Georef 10105090; departamento 10105; localidad censal 10105090.
-- Localidad 953: Georef 10070050; departamento 10070; localidad censal 10070050.
-- Localidad 954: Georef 10007040; departamento 10007; localidad censal 10007040.
-- Localidad 955: Georef 10105100; departamento 10105; localidad censal 10105100.
-- Localidad 956: Georef 10070060; departamento 10070; localidad censal 10070060.
-- Localidad 957: Georef 10091070; departamento 10091; localidad censal 10091070.
-- Localidad 958: Georef 10035060; departamento 10035; localidad censal 10035060.
-- Localidad 959: Georef 10105110; departamento 10105; localidad censal 10105110.
-- Localidad 960: Georef 10091080; departamento 10091; localidad censal 10091080.
-- Localidad 961: Georef 10056020; departamento 10056; localidad censal 10056020.
-- Localidad 962: Georef 10035070; departamento 10035; localidad censal 10035070.
-- Localidad 963: Georef 10007050; departamento 10007; localidad censal 10007050.
-- Localidad 964: Georef 10112020; departamento 10112; localidad censal 10112020.
-- Localidad 965: Georef 10042100; departamento 10042; localidad censal 10042100.
-- Localidad 966: Georef 10070070; departamento 10070; localidad censal 10070070.
-- Localidad 967: Georef 10056030; departamento 10056; localidad censal 10056030.
-- Localidad 968: Georef 10035080; departamento 10035; localidad censal 10035080.
-- Localidad 969: Georef 10014030; departamento 10014; localidad censal 10014030.
-- Localidad 970: Georef 1006304002; departamento 10063; localidad censal 10063040.
-- Localidad 971: Georef 10070080; departamento 10070; localidad censal 10070080.
-- Localidad 972: Georef 1006304003; departamento 10063; localidad censal 10063040.
-- Localidad 973: Georef 10070090; departamento 10070; localidad censal 10070090.
-- Localidad 974: Georef 10077040; departamento 10077; localidad censal 10077040.
-- Localidad 975: Georef 10091090; departamento 10091; localidad censal 10091090.
-- Localidad 976: Georef 10014040; departamento 10014; localidad censal 10014040.
-- Localidad 977: Georef 10077050; departamento 10077; localidad censal 10077050.
-- Localidad 978: Georef 1002106004; departamento 10021; localidad censal 10021060.
-- Localidad 979: Georef 10007060; departamento 10007; localidad censal 10007060.
-- Localidad 980: Georef 10035090; departamento 10035; localidad censal 10035090.
-- Localidad 981: Georef 1009114002; departamento 10091; localidad censal 10091140.
-- Localidad 982: Georef 1010505002; departamento 10105; localidad censal 10105050.
-- Localidad 983: Georef 1010511002; departamento 10105; localidad censal 10105110.
-- Localidad 984: Georef 1006304004; departamento 10063; localidad censal 10063040.
-- Localidad 985: Georef 10077060; departamento 10077; localidad censal 10077060.
-- Localidad 986: Georef 1009103002; departamento 10091; localidad censal 10091030.
-- Localidad 987: Georef 10098030; departamento 10098; localidad censal 10098030.
-- Localidad 988: Georef 10007070; departamento 10007; localidad censal 10007070.
-- Localidad 989: Georef 10070100; departamento 10070; localidad censal 10070100.
-- Localidad 990: Georef 10007080; departamento 10007; localidad censal 10007080.
-- Localidad 991: Georef 10035100; departamento 10035; localidad censal 10035100.
-- Localidad 992: Georef 10077070; departamento 10077; localidad censal 10077070.
-- Localidad 993: Georef 10091110; departamento 10091; localidad censal 10091110.
-- Localidad 994: Georef 10070110; departamento 10070; localidad censal 10070110.
-- Localidad 995: Georef 10112030; departamento 10112; localidad censal 10112030.
-- Localidad 996: Georef 10098040; departamento 10098; localidad censal 10098040.
-- Localidad 997: Georef 10035110; departamento 10035; localidad censal 10035110.
-- Localidad 998: Georef 10098050; departamento 10098; localidad censal 10098050.
-- Localidad 999: Georef 10042110; departamento 10042; localidad censal 10042110.
-- Localidad 1000: Georef 1004211001; departamento 10042; localidad censal 10042110.
-- Localidad 1001: Georef 1004211002; departamento 10042; localidad censal 10042110.
-- Localidad 1002: Georef 10007090; departamento 10007; localidad censal 10007090.
-- Localidad 1003: Georef 10056040; departamento 10056; localidad censal 10056040.
-- Localidad 1004: Georef 10028040; departamento 10028; localidad censal 10028040.
-- Localidad 1005: Georef 10035120; departamento 10035; localidad censal 10035120.
-- Localidad 1006: Georef 10007100; departamento 10007; localidad censal 10007100.
-- Localidad 1007: Georef 10007110; departamento 10007; localidad censal 10007110.
-- Localidad 1008: Georef 10098060; departamento 10098; localidad censal 10098060.
-- Localidad 1009: Georef 1009103003; departamento 10091; localidad censal 10091030.
-- Localidad 1010: Georef 10105130; departamento 10105; localidad censal 10105130.
-- Localidad 1011: Georef 10042120; departamento 10042; localidad censal 10042120.
-- Localidad 1012: Georef 10077080; departamento 10077; localidad censal 10077080.
-- Localidad 1013: Georef 10084060; departamento 10084; localidad censal 10084060.
-- Localidad 1014: Georef 10105140; departamento 10105; localidad censal 10105140.
-- Localidad 1015: Georef 10077090; departamento 10077; localidad censal 10077090.
-- Localidad 1016: Georef 1009114003; departamento 10091; localidad censal 10091140.
-- Localidad 1017: Georef 1010511003; departamento 10105; localidad censal 10105110.
-- Localidad 1018: Georef 1011204002; departamento 10112; localidad censal 10112040.
-- Localidad 1019: Georef 10084070; departamento 10084; localidad censal 10084070.
-- Localidad 1020: Georef 10063020; departamento 10063; localidad censal 10063020.
-- Localidad 1021: Georef 10063030; departamento 10063; localidad censal 10063030.
-- Localidad 1022: Georef 1011204003; departamento 10112; localidad censal 10112040.
-- Localidad 1023: Georef 10035130; departamento 10035; localidad censal 10035130.
-- Localidad 1024: Georef 10035140; departamento 10035; localidad censal 10035140.
-- Localidad 1025: Georef 10091130; departamento 10091; localidad censal 10091130.
-- Localidad 1026: Georef 10070120; departamento 10070; localidad censal 10070120.
-- Localidad 1027: Georef 10070130; departamento 10070; localidad censal 10070130.
-- Localidad 1028: Georef 10070140; departamento 10070; localidad censal 10070140.
-- Localidad 1029: Georef 10084080; departamento 10084; localidad censal 10084080.
-- Localidad 1030: Georef 1006304005; departamento 10063; localidad censal 10063040.
-- Localidad 1031: Georef 10070150; departamento 10070; localidad censal 10070150.
-- Localidad 1032: Georef 10077100; departamento 10077; localidad censal 10077100.
-- Localidad 1033: Georef 10049030; departamento 10049; localidad censal 10049030.
-- Localidad 1034: Georef 10112040; departamento 10112; localidad censal 10112040.
-- Localidad 1035: Georef 10063040; departamento 10063; localidad censal 10063040.
-- Localidad 1036: Georef 10091140; departamento 10091; localidad censal 10091140.
-- Localidad 1037: Georef 1009107002; departamento 10091; localidad censal 10091070.
-- Localidad 1038: Georef 1009114004; departamento 10091; localidad censal 10091140.
-- Localidad 1039: Georef 1009114005; departamento 10091; localidad censal 10091140.
-- Localidad 1040: Georef 10042130; departamento 10042; localidad censal 10042130.
-- Localidad 1041: Georef 10084090; departamento 10084; localidad censal 10084090.
-- Localidad 1042: Georef 10042140; departamento 10042; localidad censal 10042140.
-- Localidad 1043: Georef 10042150; departamento 10042; localidad censal 10042150.
-- Localidad 1044: Georef 10098070; departamento 10098; localidad censal 10098070.
-- Localidad 1045: Georef 10112050; departamento 10112; localidad censal 10112050.
-- Localidad 1046: Georef 10091150; departamento 10091; localidad censal 10091150.
-- Localidad 1047: Georef 1011204005; departamento 10112; localidad censal 10112040.
-- Localidad 1048: Georef 10084100; departamento 10084; localidad censal 10084100.
-- Localidad 1049: Georef 10105160; departamento 10105; localidad censal 10105160.
-- Localidad 1050: Georef 10084110; departamento 10084; localidad censal 10084110.
-- Localidad 1051: Georef 10007120; departamento 10007; localidad censal 10007120.
-- Localidad 1052: Georef 1011204006; departamento 10112; localidad censal 10112040.
-- Localidad 1053: Georef 10056050; departamento 10056; localidad censal 10056050.
-- Localidad 1054: Georef 10105180; departamento 10105; localidad censal 10105180.
-- Localidad 1055: Georef 10056060; departamento 10056; localidad censal 10056060.
-- Localidad 1056: Georef 10077110; departamento 10077; localidad censal 10077110.
-- Localidad 1057: Georef 1011204007; departamento 10112; localidad censal 10112040.
-- Localidad 1058: Georef 10063050; departamento 10063; localidad censal 10063050.
-- Localidad 1059: Georef 10035150; departamento 10035; localidad censal 10035150.
-- Localidad 1060: Georef 10091160; departamento 10091; localidad censal 10091160.
-- Localidad 1061: Georef 1402131004; departamento 14021; localidad censal 14021310.
-- Localidad 1062: Georef 14098010; departamento 14098; localidad censal 14098010.
-- Localidad 1063: Georef 14098020; departamento 14098; localidad censal 14098020.
-- Localidad 1064: Georef 14021010; departamento 14021; localidad censal 14021010.
-- Localidad 1065: Georef 14098030; departamento 14098; localidad censal 14098030.
-- Localidad 1066: Georef 14182010; departamento 14182; localidad censal 14182010.
-- Localidad 1067: Georef 14056010; departamento 14056; localidad censal 14056010.
-- Localidad 1068: Georef 14063010; departamento 14063; localidad censal 14063010.
-- Localidad 1069: Georef 14140010; departamento 14140; localidad censal 14140010.
-- Localidad 1070: Georef 1402131005; departamento 14021; localidad censal 14021310.
-- Localidad 1071: Georef 14161010; departamento 14161; localidad censal 14161010.
-- Localidad 1072: Georef 14098040; departamento 14098; localidad censal 14098040.
-- Localidad 1073: Georef 14147010; departamento 14147; localidad censal 14147010.
-- Localidad 1074: Georef 14182020; departamento 14182; localidad censal 14182020.
-- Localidad 1075: Georef 14028010; departamento 14028; localidad censal 14028010.
-- Localidad 1076: Georef 1413319001; departamento 14133; localidad censal 14133190.
-- Localidad 1077: Georef 14140020; departamento 14140; localidad censal 14140020.
-- Localidad 1078: Georef 14007010; departamento 14007; localidad censal 14007010.
-- Localidad 1079: Georef 14126010; departamento 14126; localidad censal 14126010.
-- Localidad 1080: Georef 14182030; departamento 14182; localidad censal 14182030.
-- Localidad 1081: Georef 14147020; departamento 14147; localidad censal 14147020.
-- Localidad 1082: Georef 14063020; departamento 14063; localidad censal 14063020.
-- Localidad 1083: Georef 14140030; departamento 14140; localidad censal 14140030.
-- Localidad 1084: Georef 14042010; departamento 14042; localidad censal 14042010.
-- Localidad 1085: Georef 14042020; departamento 14042; localidad censal 14042020.
-- Localidad 1086: Georef 14126020; departamento 14126; localidad censal 14126020.
-- Localidad 1087: Georef 14007020; departamento 14007; localidad censal 14007020.
-- Localidad 1088: Georef 14021020; departamento 14021; localidad censal 14021020.
-- Localidad 1089: Georef 14056020; departamento 14056; localidad censal 14056020.
-- Localidad 1090: Georef 14105010; departamento 14105; localidad censal 14105010.
-- Localidad 1091: Georef 14042030; departamento 14042; localidad censal 14042030.
-- Localidad 1092: Georef 14049010; departamento 14049; localidad censal 14049010.
-- Localidad 1093: Georef 14182040; departamento 14182; localidad censal 14182040.
-- Localidad 1094: Georef 14182050; departamento 14182; localidad censal 14182050.
-- Localidad 1095: Georef 14140040; departamento 14140; localidad censal 14140040.
-- Localidad 1096: Georef 14028020; departamento 14028; localidad censal 14028020.
-- Localidad 1097: Georef 1414703001; departamento 14147; localidad censal 14147030.
-- Localidad 1098: Georef 14147030; departamento 14147; localidad censal 14147030.
-- Localidad 1099: Georef 1414731001; departamento 14147; localidad censal 14147310.
-- Localidad 1100: Georef 14182060; departamento 14182; localidad censal 14182060.
-- Localidad 1101: Georef 14056030; departamento 14056; localidad censal 14056030.
-- Localidad 1102: Georef 14182070; departamento 14182; localidad censal 14182070.
-- Localidad 1103: Georef 14098050; departamento 14098; localidad censal 14098050.
-- Localidad 1104: Georef 14091020; departamento 14091; localidad censal 14091020.
-- Localidad 1105: Georef 14147050; departamento 14147; localidad censal 14147050.
-- Localidad 1106: Georef 14140050; departamento 14140; localidad censal 14140050.
-- Localidad 1107: Georef 14098060; departamento 14098; localidad censal 14098060.
-- Localidad 1108: Georef 14091030; departamento 14091; localidad censal 14091030.
-- Localidad 1109: Georef 14119010; departamento 14119; localidad censal 14119010.
-- Localidad 1110: Georef 14119020; departamento 14119; localidad censal 14119020.
-- Localidad 1111: Georef 14063030; departamento 14063; localidad censal 14063030.
-- Localidad 1112: Georef 14154010; departamento 14154; localidad censal 14154010.
-- Localidad 1113: Georef 14168020; departamento 14168; localidad censal 14168020.
-- Localidad 1114: Georef 14105020; departamento 14105; localidad censal 14105020.
-- Localidad 1115: Georef 14049020; departamento 14049; localidad censal 14049020.
-- Localidad 1116: Georef 14007030; departamento 14007; localidad censal 14007030.
-- Localidad 1117: Georef 14182080; departamento 14182; localidad censal 14182080.
-- Localidad 1118: Georef 14168010; departamento 14168; localidad censal 14168010.
-- Localidad 1119: Georef 14105030; departamento 14105; localidad censal 14105030.
-- Localidad 1120: Georef 14168030; departamento 14168; localidad censal 14168030.
-- Localidad 1121: Georef 14119030; departamento 14119; localidad censal 14119030.
-- Localidad 1122: Georef 14091040; departamento 14091; localidad censal 14091040.
-- Localidad 1123: Georef 14063040; departamento 14063; localidad censal 14063040.
-- Localidad 1124: Georef 14056040; departamento 14056; localidad censal 14056040.
-- Localidad 1125: Georef 14119040; departamento 14119; localidad censal 14119040.
-- Localidad 1126: Georef 14091050; departamento 14091; localidad censal 14091050.
-- Localidad 1127: Georef 14147060; departamento 14147; localidad censal 14147060.
-- Localidad 1128: Georef 14063050; departamento 14063; localidad censal 14063050.
-- Localidad 1129: Georef 14112010; departamento 14112; localidad censal 14112010.
-- Localidad 1130: Georef 14098070; departamento 14098; localidad censal 14098070.
-- Localidad 1131: Georef 14105040; departamento 14105; localidad censal 14105040.
-- Localidad 1132: Georef 14112020; departamento 14112; localidad censal 14112020.
-- Localidad 1133: Georef 14077010; departamento 14077; localidad censal 14077010.
-- Localidad 1134: Georef 14091060; departamento 14091; localidad censal 14091060.
-- Localidad 1135: Georef 14056050; departamento 14056; localidad censal 14056050.
-- Localidad 1136: Georef 14042040; departamento 14042; localidad censal 14042040.
-- Localidad 1137: Georef 14182090; departamento 14182; localidad censal 14182090.
-- Localidad 1138: Georef 14098080; departamento 14098; localidad censal 14098080.
-- Localidad 1139: Georef 14049030; departamento 14049; localidad censal 14049030.
-- Localidad 1140: Georef 14154030; departamento 14154; localidad censal 14154030.
-- Localidad 1141: Georef 14175020; departamento 14175; localidad censal 14175020.
-- Localidad 1142: Georef 14070010; departamento 14070; localidad censal 14070010.
-- Localidad 1143: Georef 14182100; departamento 14182; localidad censal 14182100.
-- Localidad 1144: Georef 1402131006; departamento 14021; localidad censal 14021310.
-- Localidad 1145: Georef 14119050; departamento 14119; localidad censal 14119050.
-- Localidad 1146: Georef 14140070; departamento 14140; localidad censal 14140070.
-- Localidad 1147: Georef 14161020; departamento 14161; localidad censal 14161020.
-- Localidad 1148: Georef 14140060; departamento 14140; localidad censal 14140060.
-- Localidad 1149: Georef 14063060; departamento 14063; localidad censal 14063060.
-- Localidad 1150: Georef 14182110; departamento 14182; localidad censal 14182110.
-- Localidad 1151: Georef 14182120; departamento 14182; localidad censal 14182120.
-- Localidad 1152: Georef 14021050; departamento 14021; localidad censal 14021050.
-- Localidad 1153: Georef 14063070; departamento 14063; localidad censal 14063070.
-- Localidad 1154: Georef 14140215; departamento 14140; localidad censal 14140215.
-- Localidad 1155: Georef 14105050; departamento 14105; localidad censal 14105050.
-- Localidad 1156: Georef 14140080; departamento 14140; localidad censal 14140080.
-- Localidad 1157: Georef 14140090; departamento 14140; localidad censal 14140090.
-- Localidad 1158: Georef 14140100; departamento 14140; localidad censal 14140100.
-- Localidad 1159: Georef 14140110; departamento 14140; localidad censal 14140110.
-- Localidad 1160: Georef 14140120; departamento 14140; localidad censal 14140120.
-- Localidad 1161: Georef 14140130; departamento 14140; localidad censal 14140130.
-- Localidad 1162: Georef 14021060; departamento 14021; localidad censal 14021060.
-- Localidad 1163: Georef 14140140; departamento 14140; localidad censal 14140140.
-- Localidad 1164: Georef 14063080; departamento 14063; localidad censal 14063080.
-- Localidad 1165: Georef 14021070; departamento 14021; localidad censal 14021070.
-- Localidad 1166: Georef 14119060; departamento 14119; localidad censal 14119060.
-- Localidad 1167: Georef 14140150; departamento 14140; localidad censal 14140150.
-- Localidad 1168: Georef 14133010; departamento 14133; localidad censal 14133010.
-- Localidad 1169: Georef 14049040; departamento 14049; localidad censal 14049040.
-- Localidad 1170: Georef 14014010; departamento 14014; localidad censal 14014010.
-- Localidad 1171: Georef 14098090; departamento 14098; localidad censal 14098090.
-- Localidad 1172: Georef 14098100; departamento 14098; localidad censal 14098100.
-- Localidad 1173: Georef 14063090; departamento 14063; localidad censal 14063090.
-- Localidad 1174: Georef 14161030; departamento 14161; localidad censal 14161030.
-- Localidad 1175: Georef 14091070; departamento 14091; localidad censal 14091070.
-- Localidad 1176: Georef 14119070; departamento 14119; localidad censal 14119070.
-- Localidad 1177: Georef 14063100; departamento 14063; localidad censal 14063100.
-- Localidad 1178: Georef 14028040; departamento 14028; localidad censal 14028040.
-- Localidad 1179: Georef 14028050; departamento 14028; localidad censal 14028050.
-- Localidad 1180: Georef 14091080; departamento 14091; localidad censal 14091080.
-- Localidad 1181: Georef 14161040; departamento 14161; localidad censal 14161040.
-- Localidad 1182: Georef 14049050; departamento 14049; localidad censal 14049050.
-- Localidad 1183: Georef 14035010; departamento 14035; localidad censal 14035010.
-- Localidad 1184: Georef 14147080; departamento 14147; localidad censal 14147080.
-- Localidad 1185: Georef 14140160; departamento 14140; localidad censal 14140160.
-- Localidad 1186: Georef 14105060; departamento 14105; localidad censal 14105060.
-- Localidad 1187: Georef 14147090; departamento 14147; localidad censal 14147090.
-- Localidad 1188: Georef 1402115001; departamento 14021; localidad censal 14021150.
-- Localidad 1189: Georef 14105070; departamento 14105; localidad censal 14105070.
-- Localidad 1190: Georef 14140170; departamento 14140; localidad censal 14140170.
-- Localidad 1191: Georef 14028060; departamento 14028; localidad censal 14028060.
-- Localidad 1192: Georef 14070020; departamento 14070; localidad censal 14070020.
-- Localidad 1193: Georef 14007050; departamento 14007; localidad censal 14007050.
-- Localidad 1194: Georef 14105080; departamento 14105; localidad censal 14105080.
-- Localidad 1195: Georef 1402115003; departamento 14021; localidad censal 14021150.
-- Localidad 1196: Georef 14007055; departamento 14007; localidad censal 14007055.
-- Localidad 1197: Georef 14140180; departamento 14140; localidad censal 14140180.
-- Localidad 1198: Georef 14140190; departamento 14140; localidad censal 14140190.
-- Localidad 1199: Georef 14021110; departamento 14021; localidad censal 14021110.
-- Localidad 1200: Georef 1402125001; departamento 14021; localidad censal 14021250.
-- Localidad 1201: Georef 1413319002; departamento 14133; localidad censal 14133190.
-- Localidad 1202: Georef 14056060; departamento 14056; localidad censal 14056060.
-- Localidad 1203: Georef 14175030; departamento 14175; localidad censal 14175030.
-- Localidad 1204: Georef 14140200; departamento 14140; localidad censal 14140200.
-- Localidad 1205: Georef 14175040; departamento 14175; localidad censal 14175040.
-- Localidad 1206: Georef 1413319003; departamento 14133; localidad censal 14133190.
-- Localidad 1207: Georef 14098110; departamento 14098; localidad censal 14098110.
-- Localidad 1208: Georef 14007060; departamento 14007; localidad censal 14007060.
-- Localidad 1209: Georef 14105090; departamento 14105; localidad censal 14105090.
-- Localidad 1210: Georef 14035020; departamento 14035; localidad censal 14035020.
-- Localidad 1211: Georef 14140210; departamento 14140; localidad censal 14140210.
-- Localidad 1212: Georef 14070030; departamento 14070; localidad censal 14070030.
-- Localidad 1213: Georef 14091090; departamento 14091; localidad censal 14091090.
-- Localidad 1214: Georef 14042050; departamento 14042; localidad censal 14042050.
-- Localidad 1215: Georef 14112030; departamento 14112; localidad censal 14112030.
-- Localidad 1216: Georef 14147100; departamento 14147; localidad censal 14147100.
-- Localidad 1217: Georef 14147110; departamento 14147; localidad censal 14147110.
-- Localidad 1218: Georef 14140220; departamento 14140; localidad censal 14140220.
-- Localidad 1219: Georef 14063110; departamento 14063; localidad censal 14063110.
-- Localidad 1220: Georef 14056070; departamento 14056; localidad censal 14056070.
-- Localidad 1221: Georef 14056080; departamento 14056; localidad censal 14056080.
-- Localidad 1222: Georef 14161050; departamento 14161; localidad censal 14161050.
-- Localidad 1223: Georef 14084010; departamento 14084; localidad censal 14084010.
-- Localidad 1224: Georef 14021130; departamento 14021; localidad censal 14021130.
-- Localidad 1225: Georef 14063120; departamento 14063; localidad censal 14063120.
-- Localidad 1226: Georef 14028080; departamento 14028; localidad censal 14028080.
-- Localidad 1227: Georef 14070040; departamento 14070; localidad censal 14070040.
-- Localidad 1228: Georef 14063130; departamento 14063; localidad censal 14063130.
-- Localidad 1229: Georef 1402131001; departamento 14021; localidad censal 14021310.
-- Localidad 1230: Georef 14112040; departamento 14112; localidad censal 14112040.
-- Localidad 1231: Georef 14161060; departamento 14161; localidad censal 14161060.
-- Localidad 1232: Georef 14035030; departamento 14035; localidad censal 14035030.
-- Localidad 1233: Georef 14056090; departamento 14056; localidad censal 14056090.
-- Localidad 1234: Georef 14091100; departamento 14091; localidad censal 14091100.
-- Localidad 1235: Georef 14035040; departamento 14035; localidad censal 14035040.
-- Localidad 1236: Georef 14182130; departamento 14182; localidad censal 14182130.
-- Localidad 1237: Georef 14119080; departamento 14119; localidad censal 14119080.
-- Localidad 1238: Georef 14063140; departamento 14063; localidad censal 14063140.
-- Localidad 1239: Georef 14063150; departamento 14063; localidad censal 14063150.
-- Localidad 1240: Georef 14035050; departamento 14035; localidad censal 14035050.
-- Localidad 1241: Georef 14161070; departamento 14161; localidad censal 14161070.
-- Localidad 1242: Georef 1401401001; departamento 14014; localidad censal 14014010.
-- Localidad 1243: Georef 14021140; departamento 14021; localidad censal 14021140.
-- Localidad 1244: Georef 14147115; departamento 14147; localidad censal 14147115.
-- Localidad 1245: Georef 1402132001; departamento 14021; localidad censal 14021320.
-- Localidad 1246: Georef 14182140; departamento 14182; localidad censal 14182140.
-- Localidad 1247: Georef 14105100; departamento 14105; localidad censal 14105100.
-- Localidad 1248: Georef 14028100; departamento 14028; localidad censal 14028100.
-- Localidad 1249: Georef 14147120; departamento 14147; localidad censal 14147120.
-- Localidad 1250: Georef 14021150; departamento 14021; localidad censal 14021150.
-- Localidad 1251: Georef 14056100; departamento 14056; localidad censal 14056100.
-- Localidad 1252: Georef 14098120; departamento 14098; localidad censal 14098120.
-- Localidad 1253: Georef 14098130; departamento 14098; localidad censal 14098130.
-- Localidad 1254: Georef 14084020; departamento 14084; localidad censal 14084020.
-- Localidad 1255: Georef 14007070; departamento 14007; localidad censal 14007070.
-- Localidad 1256: Georef 14091110; departamento 14091; localidad censal 14091110.
-- Localidad 1257: Georef 14007080; departamento 14007; localidad censal 14007080.
-- Localidad 1258: Georef 1414732002; departamento 14147; localidad censal 14147320.
-- Localidad 1259: Georef 14091120; departamento 14091; localidad censal 14091120.
-- Localidad 1260: Georef 1401401002; departamento 14014; localidad censal 14014010.
-- Localidad 1261: Georef 14140230; departamento 14140; localidad censal 14140230.
-- Localidad 1262: Georef 14098140; departamento 14098; localidad censal 14098140.
-- Localidad 1263: Georef 14021160; departamento 14021; localidad censal 14021160.
-- Localidad 1264: Georef 14028110; departamento 14028; localidad censal 14028110.
-- Localidad 1265: Georef 14042060; departamento 14042; localidad censal 14042060.
-- Localidad 1266: Georef 14147150; departamento 14147; localidad censal 14147150.
-- Localidad 1267: Georef 14042070; departamento 14042; localidad censal 14042070.
-- Localidad 1268: Georef 14168040; departamento 14168; localidad censal 14168040.
-- Localidad 1269: Georef 14140240; departamento 14140; localidad censal 14140240.
-- Localidad 1270: Georef 14105110; departamento 14105; localidad censal 14105110.
-- Localidad 1271: Georef 14133060; departamento 14133; localidad censal 14133060.
-- Localidad 1272: Georef 14070050; departamento 14070; localidad censal 14070050.
-- Localidad 1273: Georef 14042080; departamento 14042; localidad censal 14042080.
-- Localidad 1274: Georef 14133070; departamento 14133; localidad censal 14133070.
-- Localidad 1275: Georef 14105120; departamento 14105; localidad censal 14105120.
-- Localidad 1276: Georef 14021170; departamento 14021; localidad censal 14021170.
-- Localidad 1277: Georef 14105130; departamento 14105; localidad censal 14105130.
-- Localidad 1278: Georef 14105140; departamento 14105; localidad censal 14105140.
-- Localidad 1279: Georef 14147170; departamento 14147; localidad censal 14147170.
-- Localidad 1280: Georef 14112050; departamento 14112; localidad censal 14112050.
-- Localidad 1281: Georef 14147180; departamento 14147; localidad censal 14147180.
-- Localidad 1282: Georef 14140250; departamento 14140; localidad censal 14140250.
-- Localidad 1283: Georef 14133090; departamento 14133; localidad censal 14133090.
-- Localidad 1284: Georef 14182150; departamento 14182; localidad censal 14182150.
-- Localidad 1285: Georef 14084030; departamento 14084; localidad censal 14084030.
-- Localidad 1286: Georef 14119090; departamento 14119; localidad censal 14119090.
-- Localidad 1287: Georef 14098150; departamento 14098; localidad censal 14098150.
-- Localidad 1288: Georef 14098160; departamento 14098; localidad censal 14098160.
-- Localidad 1289: Georef 14175050; departamento 14175; localidad censal 14175050.
-- Localidad 1290: Georef 14007090; departamento 14007; localidad censal 14007090.
-- Localidad 1291: Georef 14007100; departamento 14007; localidad censal 14007100.
-- Localidad 1292: Georef 14126050; departamento 14126; localidad censal 14126050.
-- Localidad 1293: Georef 14028120; departamento 14028; localidad censal 14028120.
-- Localidad 1294: Georef 1413319004; departamento 14133; localidad censal 14133190.
-- Localidad 1295: Georef 14105150; departamento 14105; localidad censal 14105150.
-- Localidad 1296: Georef 14098170; departamento 14098; localidad censal 14098170.
-- Localidad 1297: Georef 14161080; departamento 14161; localidad censal 14161080.
-- Localidad 1298: Georef 14091130; departamento 14091; localidad censal 14091130.
-- Localidad 1299: Georef 14119100; departamento 14119; localidad censal 14119100.
-- Localidad 1300: Georef 14042090; departamento 14042; localidad censal 14042090.
-- Localidad 1301: Georef 14077020; departamento 14077; localidad censal 14077020.
-- Localidad 1302: Georef 14098180; departamento 14098; localidad censal 14098180.
-- Localidad 1303: Georef 14168060; departamento 14168; localidad censal 14168060.
-- Localidad 1304: Georef 14161090; departamento 14161; localidad censal 14161090.
-- Localidad 1305: Georef 14028130; departamento 14028; localidad censal 14028130.
-- Localidad 1306: Georef 1414719001; departamento 14147; localidad censal 14147190.
-- Localidad 1307: Georef 14126070; departamento 14126; localidad censal 14126070.
-- Localidad 1308: Georef 14105160; departamento 14105; localidad censal 14105160.
-- Localidad 1309: Georef 14133100; departamento 14133; localidad censal 14133100.
-- Localidad 1310: Georef 14140260; departamento 14140; localidad censal 14140260.
-- Localidad 1311: Georef 14140270; departamento 14140; localidad censal 14140270.
-- Localidad 1312: Georef 14098190; departamento 14098; localidad censal 14098190.
-- Localidad 1313: Georef 14084040; departamento 14084; localidad censal 14084040.
-- Localidad 1314: Georef 14063160; departamento 14063; localidad censal 14063160.
-- Localidad 1315: Georef 14147190; departamento 14147; localidad censal 14147190.
-- Localidad 1316: Georef 14133110; departamento 14133; localidad censal 14133110.
-- Localidad 1317: Georef 14028140; departamento 14028; localidad censal 14028140.
-- Localidad 1318: Georef 14119110; departamento 14119; localidad censal 14119110.
-- Localidad 1319: Georef 14056110; departamento 14056; localidad censal 14056110.
-- Localidad 1320: Georef 14091140; departamento 14091; localidad censal 14091140.
-- Localidad 1321: Georef 14007110; departamento 14007; localidad censal 14007110.
-- Localidad 1322: Georef 14133120; departamento 14133; localidad censal 14133120.
-- Localidad 1323: Georef 14112060; departamento 14112; localidad censal 14112060.
-- Localidad 1324: Georef 14168070; departamento 14168; localidad censal 14168070.
-- Localidad 1325: Georef 14007120; departamento 14007; localidad censal 14007120.
-- Localidad 1326: Georef 14049080; departamento 14049; localidad censal 14049080.
-- Localidad 1327: Georef 14007130; departamento 14007; localidad censal 14007130.
-- Localidad 1328: Georef 14063170; departamento 14063; localidad censal 14063170.
-- Localidad 1329: Georef 14077030; departamento 14077; localidad censal 14077030.
-- Localidad 1330: Georef 14161100; departamento 14161; localidad censal 14161100.
-- Localidad 1331: Georef 14147200; departamento 14147; localidad censal 14147200.
-- Localidad 1332: Georef 14042100; departamento 14042; localidad censal 14042100.
-- Localidad 1333: Georef 14175060; departamento 14175; localidad censal 14175060.
-- Localidad 1334: Georef 14119120; departamento 14119; localidad censal 14119120.
-- Localidad 1335: Georef 14007140; departamento 14007; localidad censal 14007140.
-- Localidad 1336: Georef 14133150; departamento 14133; localidad censal 14133150.
-- Localidad 1337: Georef 14147210; departamento 14147; localidad censal 14147210.
-- Localidad 1338: Georef 14098200; departamento 14098; localidad censal 14098200.
-- Localidad 1339: Georef 14091150; departamento 14091; localidad censal 14091150.
-- Localidad 1340: Georef 14021190; departamento 14021; localidad censal 14021190.
-- Localidad 1341: Georef 14119130; departamento 14119; localidad censal 14119130.
-- Localidad 1342: Georef 14105170; departamento 14105; localidad censal 14105170.
-- Localidad 1343: Georef 14063180; departamento 14063; localidad censal 14063180.
-- Localidad 1344: Georef 14140280; departamento 14140; localidad censal 14140280.
-- Localidad 1345: Georef 14119140; departamento 14119; localidad censal 14119140.
-- Localidad 1346: Georef 14035060; departamento 14035; localidad censal 14035060.
-- Localidad 1347: Georef 14091160; departamento 14091; localidad censal 14091160.
-- Localidad 1348: Georef 14028150; departamento 14028; localidad censal 14028150.
-- Localidad 1349: Georef 14084050; departamento 14084; localidad censal 14084050.
-- Localidad 1350: Georef 14021200; departamento 14021; localidad censal 14021200.
-- Localidad 1351: Georef 14021210; departamento 14021; localidad censal 14021210.
-- Localidad 1352: Georef 1414735001; departamento 14147; localidad censal 14147350.
-- Localidad 1353: Georef 14126090; departamento 14126; localidad censal 14126090.
-- Localidad 1354: Georef 14140290; departamento 14140; localidad censal 14140290.
-- Localidad 1355: Georef 14063190; departamento 14063; localidad censal 14063190.
-- Localidad 1356: Georef 14098210; departamento 14098; localidad censal 14098210.
-- Localidad 1357: Georef 14105180; departamento 14105; localidad censal 14105180.
-- Localidad 1358: Georef 14182160; departamento 14182; localidad censal 14182160.
-- Localidad 1359: Georef 14182170; departamento 14182; localidad censal 14182170.
-- Localidad 1360: Georef 14147220; departamento 14147; localidad censal 14147220.
-- Localidad 1361: Georef 14105190; departamento 14105; localidad censal 14105190.
-- Localidad 1362: Georef 14182180; departamento 14182; localidad censal 14182180.
-- Localidad 1363: Georef 14140300; departamento 14140; localidad censal 14140300.
-- Localidad 1364: Georef 14126100; departamento 14126; localidad censal 14126100.
-- Localidad 1365: Georef 14035070; departamento 14035; localidad censal 14035070.
-- Localidad 1366: Georef 14182190; departamento 14182; localidad censal 14182190.
-- Localidad 1367: Georef 14126110; departamento 14126; localidad censal 14126110.
-- Localidad 1368: Georef 14105200; departamento 14105; localidad censal 14105200.
-- Localidad 1369: Georef 14056120; departamento 14056; localidad censal 14056120.
-- Localidad 1370: Georef 14161110; departamento 14161; localidad censal 14161110.
-- Localidad 1371: Georef 14049090; departamento 14049; localidad censal 14049090.
-- Localidad 1372: Georef 14035080; departamento 14035; localidad censal 14035080.
-- Localidad 1373: Georef 14119150; departamento 14119; localidad censal 14119150.
-- Localidad 1374: Georef 14182200; departamento 14182; localidad censal 14182200.
-- Localidad 1375: Georef 14056130; departamento 14056; localidad censal 14056130.
-- Localidad 1376: Georef 14161120; departamento 14161; localidad censal 14161120.
-- Localidad 1377: Georef 14161130; departamento 14161; localidad censal 14161130.
-- Localidad 1378: Georef 14126120; departamento 14126; localidad censal 14126120.
-- Localidad 1379: Georef 14007160; departamento 14007; localidad censal 14007160.
-- Localidad 1380: Georef 1402131002; departamento 14021; localidad censal 14021310.
-- Localidad 1381: Georef 14021310; departamento 14021; localidad censal 14021310.
-- Localidad 1382: Georef 14182210; departamento 14182; localidad censal 14182210.
-- Localidad 1383: Georef 14042110; departamento 14042; localidad censal 14042110.
-- Localidad 1384: Georef 14056140; departamento 14056; localidad censal 14056140.
-- Localidad 1385: Georef 14098220; departamento 14098; localidad censal 14098220.
-- Localidad 1386: Georef 14028160; departamento 14028; localidad censal 14028160.
-- Localidad 1387: Georef 14119160; departamento 14119; localidad censal 14119160.
-- Localidad 1388: Georef 14035090; departamento 14035; localidad censal 14035090.
-- Localidad 1389: Georef 14105210; departamento 14105; localidad censal 14105210.
-- Localidad 1390: Georef 14105220; departamento 14105; localidad censal 14105220.
-- Localidad 1391: Georef 14140310; departamento 14140; localidad censal 14140310.
-- Localidad 1392: Georef 14140320; departamento 14140; localidad censal 14140320.
-- Localidad 1393: Georef 14140330; departamento 14140; localidad censal 14140330.
-- Localidad 1394: Georef 14147230; departamento 14147; localidad censal 14147230.
-- Localidad 1395: Georef 14119170; departamento 14119; localidad censal 14119170.
-- Localidad 1396: Georef 14154040; departamento 14154; localidad censal 14154040.
-- Localidad 1397: Georef 14105230; departamento 14105; localidad censal 14105230.
-- Localidad 1398: Georef 14182220; departamento 14182; localidad censal 14182220.
-- Localidad 1399: Georef 14112070; departamento 14112; localidad censal 14112070.
-- Localidad 1400: Georef 14161140; departamento 14161; localidad censal 14161140.
-- Localidad 1401: Georef 14140340; departamento 14140; localidad censal 14140340.
-- Localidad 1402: Georef 14049100; departamento 14049; localidad censal 14049100.
-- Localidad 1403: Georef 14147240; departamento 14147; localidad censal 14147240.
-- Localidad 1404: Georef 14182230; departamento 14182; localidad censal 14182230.
-- Localidad 1405: Georef 14035100; departamento 14035; localidad censal 14035100.
-- Localidad 1406: Georef 14112080; departamento 14112; localidad censal 14112080.
-- Localidad 1407: Georef 14119180; departamento 14119; localidad censal 14119180.
-- Localidad 1408: Georef 14084060; departamento 14084; localidad censal 14084060.
-- Localidad 1409: Georef 14021230; departamento 14021; localidad censal 14021230.
-- Localidad 1410: Georef 14098230; departamento 14098; localidad censal 14098230.
-- Localidad 1411: Georef 14007170; departamento 14007; localidad censal 14007170.
-- Localidad 1412: Georef 14105240; departamento 14105; localidad censal 14105240.
-- Localidad 1413: Georef 14119190; departamento 14119; localidad censal 14119190.
-- Localidad 1414: Georef 14161150; departamento 14161; localidad censal 14161150.
-- Localidad 1415: Georef 14084070; departamento 14084; localidad censal 14084070.
-- Localidad 1416: Georef 14175070; departamento 14175; localidad censal 14175070.
-- Localidad 1417: Georef 14140350; departamento 14140; localidad censal 14140350.
-- Localidad 1418: Georef 14105250; departamento 14105; localidad censal 14105250.
-- Localidad 1419: Georef 14063210; departamento 14063; localidad censal 14063210.
-- Localidad 1420: Georef 14063220; departamento 14063; localidad censal 14063220.
-- Localidad 1421: Georef 14021240; departamento 14021; localidad censal 14021240.
-- Localidad 1422: Georef 14077040; departamento 14077; localidad censal 14077040.
-- Localidad 1423: Georef 14021250; departamento 14021; localidad censal 14021250.
-- Localidad 1424: Georef 14098240; departamento 14098; localidad censal 14098240.
-- Localidad 1425: Georef 14007180; departamento 14007; localidad censal 14007180.
-- Localidad 1426: Georef 14091180; departamento 14091; localidad censal 14091180.
-- Localidad 1427: Georef 14182240; departamento 14182; localidad censal 14182240.
-- Localidad 1428: Georef 14098250; departamento 14098; localidad censal 14098250.
-- Localidad 1429: Georef 14070060; departamento 14070; localidad censal 14070060.
-- Localidad 1430: Georef 14147250; departamento 14147; localidad censal 14147250.
-- Localidad 1431: Georef 14091190; departamento 14091; localidad censal 14091190.
-- Localidad 1432: Georef 14140360; departamento 14140; localidad censal 14140360.
-- Localidad 1433: Georef 14154050; departamento 14154; localidad censal 14154050.
-- Localidad 1434: Georef 14077050; departamento 14077; localidad censal 14077050.
-- Localidad 1435: Georef 14007190; departamento 14007; localidad censal 14007190.
-- Localidad 1436: Georef 1400721003; departamento 14007; localidad censal 14007210.
-- Localidad 1437: Georef 14133170; departamento 14133; localidad censal 14133170.
-- Localidad 1438: Georef 14084080; departamento 14084; localidad censal 14084080.
-- Localidad 1439: Georef 14133180; departamento 14133; localidad censal 14133180.
-- Localidad 1440: Georef 14175080; departamento 14175; localidad censal 14175080.
-- Localidad 1441: Georef 14175090; departamento 14175; localidad censal 14175090.
-- Localidad 1442: Georef 14126140; departamento 14126; localidad censal 14126140.
-- Localidad 1443: Georef 14182250; departamento 14182; localidad censal 14182250.
-- Localidad 1444: Georef 14028170; departamento 14028; localidad censal 14028170.
-- Localidad 1445: Georef 14126150; departamento 14126; localidad censal 14126150.
-- Localidad 1446: Georef 14126160; departamento 14126; localidad censal 14126160.
-- Localidad 1447: Georef 14112090; departamento 14112; localidad censal 14112090.
-- Localidad 1448: Georef 14049110; departamento 14049; localidad censal 14049110.
-- Localidad 1449: Georef 14175100; departamento 14175; localidad censal 14175100.
-- Localidad 1450: Georef 14091200; departamento 14091; localidad censal 14091200.
-- Localidad 1451: Georef 1409102002; departamento 14091; localidad censal 14091020.
-- Localidad 1452: Georef 14182260; departamento 14182; localidad censal 14182260.
-- Localidad 1453: Georef 14126170; departamento 14126; localidad censal 14126170.
-- Localidad 1454: Georef 14042120; departamento 14042; localidad censal 14042120.
-- Localidad 1455: Georef 14168080; departamento 14168; localidad censal 14168080.
-- Localidad 1456: Georef 14098260; departamento 14098; localidad censal 14098260.
-- Localidad 1457: Georef 14112100; departamento 14112; localidad censal 14112100.
-- Localidad 1458: Georef 14056150; departamento 14056; localidad censal 14056150.
-- Localidad 1459: Georef 14035110; departamento 14035; localidad censal 14035110.
-- Localidad 1460: Georef 14091210; departamento 14091; localidad censal 14091210.
-- Localidad 1461: Georef 1400721001; departamento 14007; localidad censal 14007210.
-- Localidad 1462: Georef 14007210; departamento 14007; localidad censal 14007210.
-- Localidad 1463: Georef 14105260; departamento 14105; localidad censal 14105260.
-- Localidad 1464: Georef 14119200; departamento 14119; localidad censal 14119200.
-- Localidad 1465: Georef 14168090; departamento 14168; localidad censal 14168090.
-- Localidad 1466: Georef 14140370; departamento 14140; localidad censal 14140370.
-- Localidad 1467: Georef 14126180; departamento 14126; localidad censal 14126180.
-- Localidad 1468: Georef 14112110; departamento 14112; localidad censal 14112110.
-- Localidad 1469: Georef 14140380; departamento 14140; localidad censal 14140380.
-- Localidad 1470: Georef 14007220; departamento 14007; localidad censal 14007220.
-- Localidad 1471: Georef 14084090; departamento 14084; localidad censal 14084090.
-- Localidad 1472: Georef 14028180; departamento 14028; localidad censal 14028180.
-- Localidad 1473: Georef 14042130; departamento 14042; localidad censal 14042130.
-- Localidad 1474: Georef 14168100; departamento 14168; localidad censal 14168100.
-- Localidad 1475: Georef 14168110; departamento 14168; localidad censal 14168110.
-- Localidad 1476: Georef 14147270; departamento 14147; localidad censal 14147270.
-- Localidad 1477: Georef 14007230; departamento 14007; localidad censal 14007230.
-- Localidad 1478: Georef 14098270; departamento 14098; localidad censal 14098270.
-- Localidad 1479: Georef 14077060; departamento 14077; localidad censal 14077060.
-- Localidad 1480: Georef 14091220; departamento 14091; localidad censal 14091220.
-- Localidad 1481: Georef 14070070; departamento 14070; localidad censal 14070070.
-- Localidad 1482: Georef 14161160; departamento 14161; localidad censal 14161160.
-- Localidad 1483: Georef 14091230; departamento 14091; localidad censal 14091230.
-- Localidad 1484: Georef 1414703002; departamento 14147; localidad censal 14147030.
-- Localidad 1485: Georef 14042140; departamento 14042; localidad censal 14042140.
-- Localidad 1486: Georef 14021270; departamento 14021; localidad censal 14021270.
-- Localidad 1487: Georef 14042150; departamento 14042; localidad censal 14042150.
-- Localidad 1488: Georef 14147280; departamento 14147; localidad censal 14147280.
-- Localidad 1489: Georef 14140390; departamento 14140; localidad censal 14140390.
-- Localidad 1490: Georef 14070080; departamento 14070; localidad censal 14070080.
-- Localidad 1491: Georef 14098280; departamento 14098; localidad censal 14098280.
-- Localidad 1492: Georef 14140400; departamento 14140; localidad censal 14140400.
-- Localidad 1493: Georef 14028190; departamento 14028; localidad censal 14028190.
-- Localidad 1494: Georef 14056160; departamento 14056; localidad censal 14056160.
-- Localidad 1495: Georef 14021280; departamento 14021; localidad censal 14021280.
-- Localidad 1496: Georef 1400727002; departamento 14007; localidad censal 14007270.
-- Localidad 1497: Georef 1400727003; departamento 14007; localidad censal 14007270.
-- Localidad 1498: Georef 14147300; departamento 14147; localidad censal 14147300.
-- Localidad 1499: Georef 14091240; departamento 14091; localidad censal 14091240.
-- Localidad 1500: Georef 14182270; departamento 14182; localidad censal 14182270.
-- Localidad 1501: Georef 14098290; departamento 14098; localidad censal 14098290.
-- Localidad 1502: Georef 14021290; departamento 14021; localidad censal 14021290.
-- Localidad 1503: Georef 14007240; departamento 14007; localidad censal 14007240.
-- Localidad 1504: Georef 14007250; departamento 14007; localidad censal 14007250.
-- Localidad 1505: Georef 14161170; departamento 14161; localidad censal 14161170.
-- Localidad 1506: Georef 14007260; departamento 14007; localidad censal 14007260.
-- Localidad 1507: Georef 14112120; departamento 14112; localidad censal 14112120.
-- Localidad 1508: Georef 14091250; departamento 14091; localidad censal 14091250.
-- Localidad 1509: Georef 14021300; departamento 14021; localidad censal 14021300.
-- Localidad 1510: Georef 14147310; departamento 14147; localidad censal 14147310.
-- Localidad 1511: Georef 14007270; departamento 14007; localidad censal 14007270.
-- Localidad 1512: Georef 14140420; departamento 14140; localidad censal 14140420.
-- Localidad 1513: Georef 14021075; departamento 14021; localidad censal 14021075.
-- Localidad 1514: Georef 14126200; departamento 14126; localidad censal 14126200.
-- Localidad 1515: Georef 14133190; departamento 14133; localidad censal 14133190.
-- Localidad 1516: Georef 14112130; departamento 14112; localidad censal 14112130.
-- Localidad 1517: Georef 14077080; departamento 14077; localidad censal 14077080.
-- Localidad 1518: Georef 14028200; departamento 14028; localidad censal 14028200.
-- Localidad 1519: Georef 14007290; departamento 14007; localidad censal 14007290.
-- Localidad 1520: Georef 14147320; departamento 14147; localidad censal 14147320.
-- Localidad 1521: Georef 14119210; departamento 14119; localidad censal 14119210.
-- Localidad 1522: Georef 14168120; departamento 14168; localidad censal 14168120.
-- Localidad 1523: Georef 14140430; departamento 14140; localidad censal 14140430.
-- Localidad 1524: Georef 14133200; departamento 14133; localidad censal 14133200.
-- Localidad 1525: Georef 14098300; departamento 14098; localidad censal 14098300.
-- Localidad 1526: Georef 14007300; departamento 14007; localidad censal 14007300.
-- Localidad 1527: Georef 14063230; departamento 14063; localidad censal 14063230.
-- Localidad 1528: Georef 14091260; departamento 14091; localidad censal 14091260.
-- Localidad 1529: Georef 14105270; departamento 14105; localidad censal 14105270.
-- Localidad 1530: Georef 14007310; departamento 14007; localidad censal 14007310.
-- Localidad 1531: Georef 14091270; departamento 14091; localidad censal 14091270.
-- Localidad 1532: Georef 14049120; departamento 14049; localidad censal 14049120.
-- Localidad 1533: Georef 14035120; departamento 14035; localidad censal 14035120.
-- Localidad 1534: Georef 14147330; departamento 14147; localidad censal 14147330.
-- Localidad 1535: Georef 14007320; departamento 14007; localidad censal 14007320.
-- Localidad 1536: Georef 14133210; departamento 14133; localidad censal 14133210.
-- Localidad 1537: Georef 14091280; departamento 14091; localidad censal 14091280.
-- Localidad 1538: Georef 14147340; departamento 14147; localidad censal 14147340.
-- Localidad 1539: Georef 1402132002; departamento 14021; localidad censal 14021320.
-- Localidad 1540: Georef 14021320; departamento 14021; localidad censal 14021320.
-- Localidad 1541: Georef 14182280; departamento 14182; localidad censal 14182280.
-- Localidad 1542: Georef 14042170; departamento 14042; localidad censal 14042170.
-- Localidad 1543: Georef 14042180; departamento 14042; localidad censal 14042180.
-- Localidad 1544: Georef 14042190; departamento 14042; localidad censal 14042190.
-- Localidad 1545: Georef 14147350; departamento 14147; localidad censal 14147350.
-- Localidad 1546: Georef 14091290; departamento 14091; localidad censal 14091290.
-- Localidad 1547: Georef 1402131007; departamento 14021; localidad censal 14021310.
-- Localidad 1548: Georef 14007330; departamento 14007; localidad censal 14007330.
-- Localidad 1549: Georef 14056170; departamento 14056; localidad censal 14056170.
-- Localidad 1550: Georef 14091300; departamento 14091; localidad censal 14091300.
-- Localidad 1551: Georef 14084100; departamento 14084; localidad censal 14084100.
-- Localidad 1552: Georef 14007340; departamento 14007; localidad censal 14007340.
-- Localidad 1553: Georef 14140440; departamento 14140; localidad censal 14140440.
-- Localidad 1554: Georef 14147360; departamento 14147; localidad censal 14147360.
-- Localidad 1555: Georef 1400712002; departamento 14007; localidad censal 14007120.
-- Localidad 1556: Georef 14091320; departamento 14091; localidad censal 14091320.
-- Localidad 1557: Georef 14035130; departamento 14035; localidad censal 14035130.
-- Localidad 1558: Georef 14126210; departamento 14126; localidad censal 14126210.
-- Localidad 1559: Georef 14175110; departamento 14175; localidad censal 14175110.
-- Localidad 1560: Georef 14035140; departamento 14035; localidad censal 14035140.
-- Localidad 1561: Georef 14007360; departamento 14007; localidad censal 14007360.
-- Localidad 1562: Georef 14098320; departamento 14098; localidad censal 14098320.
-- Localidad 1563: Georef 14182290; departamento 14182; localidad censal 14182290.
-- Localidad 1564: Georef 18161030; departamento 18161; localidad censal 18161030.
-- Localidad 1565: Georef 18056010; departamento 18056; localidad censal 18056010.
-- Localidad 1566: Georef 18007010; departamento 18007; localidad censal 18007010.
-- Localidad 1567: Georef 18014010; departamento 18014; localidad censal 18014010.
-- Localidad 1568: Georef 18119010; departamento 18119; localidad censal 18119010.
-- Localidad 1569: Georef 18070010; departamento 18070; localidad censal 18070010.
-- Localidad 1570: Georef 18035010; departamento 18035; localidad censal 18035010.
-- Localidad 1571: Georef 18161010; departamento 18161; localidad censal 18161010.
-- Localidad 1572: Georef 18147010; departamento 18147; localidad censal 18147010.
-- Localidad 1573: Georef 18112010; departamento 18112; localidad censal 18112010.
-- Localidad 1574: Georef 18084010; departamento 18084; localidad censal 18084010.
-- Localidad 1575: Georef 18161020; departamento 18161; localidad censal 18161020.
-- Localidad 1576: Georef 18028010; departamento 18028; localidad censal 18028010.
-- Localidad 1577: Georef 18021020; departamento 18021; localidad censal 18021020.
-- Localidad 1578: Georef 18091010; departamento 18091; localidad censal 18091010.
-- Localidad 1579: Georef 18035020; departamento 18035; localidad censal 18035020.
-- Localidad 1580: Georef 18042010; departamento 18042; localidad censal 18042010.
-- Localidad 1581: Georef 18042020; departamento 18042; localidad censal 18042020.
-- Localidad 1582: Georef 18049010; departamento 18049; localidad censal 18049010.
-- Localidad 1583: Georef 18112020; departamento 18112; localidad censal 18112020.
-- Localidad 1584: Georef 18056020; departamento 18056; localidad censal 18056020.
-- Localidad 1585: Georef 18105010; departamento 18105; localidad censal 18105010.
-- Localidad 1586: Georef 18168020; departamento 18168; localidad censal 18168020.
-- Localidad 1587: Georef 18168030; departamento 18168; localidad censal 18168030.
-- Localidad 1588: Georef 18091020; departamento 18091; localidad censal 18091020.
-- Localidad 1589: Georef 18070020; departamento 18070; localidad censal 18070020.
-- Localidad 1590: Georef 18147020; departamento 18147; localidad censal 18147020.
-- Localidad 1591: Georef 18133010; departamento 18133; localidad censal 18133010.
-- Localidad 1592: Georef 18063010; departamento 18063; localidad censal 18063010.
-- Localidad 1593: Georef 18077010; departamento 18077; localidad censal 18077010.
-- Localidad 1594: Georef 18084020; departamento 18084; localidad censal 18084020.
-- Localidad 1595: Georef 18168010; departamento 18168; localidad censal 18168010.
-- Localidad 1596: Georef 18112030; departamento 18112; localidad censal 18112030.
-- Localidad 1597: Georef 18147030; departamento 18147; localidad censal 18147030.
-- Localidad 1598: Georef 18091030; departamento 18091; localidad censal 18091030.
-- Localidad 1599: Georef 18063020; departamento 18063; localidad censal 18063020.
-- Localidad 1600: Georef 18154010; departamento 18154; localidad censal 18154010.
-- Localidad 1601: Georef 18105020; departamento 18105; localidad censal 18105020.
-- Localidad 1602: Georef 18098010; departamento 18098; localidad censal 18098010.
-- Localidad 1603: Georef 18105030; departamento 18105; localidad censal 18105030.
-- Localidad 1604: Georef 18112040; departamento 18112; localidad censal 18112040.
-- Localidad 1605: Georef 18112050; departamento 18112; localidad censal 18112050.
-- Localidad 1606: Georef 18063030; departamento 18063; localidad censal 18063030.
-- Localidad 1607: Georef 18063040; departamento 18063; localidad censal 18063040.
-- Localidad 1608: Georef 18112060; departamento 18112; localidad censal 18112060.
-- Localidad 1609: Georef 18112070; departamento 18112; localidad censal 18112070.
-- Localidad 1610: Georef 18119020; departamento 18119; localidad censal 18119020.
-- Localidad 1611: Georef 18133020; departamento 18133; localidad censal 18133020.
-- Localidad 1612: Georef 18119030; departamento 18119; localidad censal 18119030.
-- Localidad 1613: Georef 18161040; departamento 18161; localidad censal 18161040.
-- Localidad 1614: Georef 18035030; departamento 18035; localidad censal 18035030.
-- Localidad 1615: Georef 18049020; departamento 18049; localidad censal 18049020.
-- Localidad 1616: Georef 18077020; departamento 18077; localidad censal 18077020.
-- Localidad 1617: Georef 18021040; departamento 18021; localidad censal 18021040.
-- Localidad 1618: Georef 18126010; departamento 18126; localidad censal 18126010.
-- Localidad 1619: Georef 18084030; departamento 18084; localidad censal 18084030.
-- Localidad 1620: Georef 18084040; departamento 18084; localidad censal 18084040.
-- Localidad 1621: Georef 18021050; departamento 18021; localidad censal 18021050.
-- Localidad 1622: Georef 18133030; departamento 18133; localidad censal 18133030.
-- Localidad 1623: Georef 18126020; departamento 18126; localidad censal 18126020.
-- Localidad 1624: Georef 18140010; departamento 18140; localidad censal 18140010.
-- Localidad 1625: Georef 18154020; departamento 18154; localidad censal 18154020.
-- Localidad 1626: Georef 18161050; departamento 18161; localidad censal 18161050.
-- Localidad 1627: Georef 18133040; departamento 18133; localidad censal 18133040.
-- Localidad 1628: Georef 18091040; departamento 18091; localidad censal 18091040.
-- Localidad 1629: Georef 18028020; departamento 18028; localidad censal 18028020.
-- Localidad 1630: Georef 18168040; departamento 18168; localidad censal 18168040.
-- Localidad 1631: Georef 18175010; departamento 18175; localidad censal 18175010.
-- Localidad 1632: Georef 18028030; departamento 18028; localidad censal 18028030.
-- Localidad 1633: Georef 18119040; departamento 18119; localidad censal 18119040.
-- Localidad 1634: Georef 18028040; departamento 18028; localidad censal 18028040.
-- Localidad 1635: Georef 18091050; departamento 18091; localidad censal 18091050.
-- Localidad 1636: Georef 18084050; departamento 18084; localidad censal 18084050.
-- Localidad 1637: Georef 18014020; departamento 18014; localidad censal 18014020.
-- Localidad 1638: Georef 18147040; departamento 18147; localidad censal 18147040.
-- Localidad 1639: Georef 18091060; departamento 18091; localidad censal 18091060.
-- Localidad 1640: Georef 22070010; departamento 22070; localidad censal 22070010.
-- Localidad 1641: Georef 22140010; departamento 22140; localidad censal 22140010.
-- Localidad 1642: Georef 22126010; departamento 22126; localidad censal 22126010.
-- Localidad 1643: Georef 22140020; departamento 22140; localidad censal 22140020.
-- Localidad 1644: Georef 22070020; departamento 22070; localidad censal 22070020.
-- Localidad 1645: Georef 22154010; departamento 22154; localidad censal 22154010.
-- Localidad 1646: Georef 22161010; departamento 22161; localidad censal 22161010.
-- Localidad 1647: Georef 22028010; departamento 22028; localidad censal 22028010.
-- Localidad 1648: Georef 22043010; departamento 22043; localidad censal 22043010.
-- Localidad 1649: Georef 22084010; departamento 22084; localidad censal 22084010.
-- Localidad 1650: Georef 22168010; departamento 22168; localidad censal 22168010.
-- Localidad 1651: Georef 22140030; departamento 22140; localidad censal 22140030.
-- Localidad 1652: Georef 22126020; departamento 22126; localidad censal 22126020.
-- Localidad 1653: Georef 22154020; departamento 22154; localidad censal 22154020.
-- Localidad 1654: Georef 22077010; departamento 22077; localidad censal 22077010.
-- Localidad 1655: Georef 22154030; departamento 22154; localidad censal 22154030.
-- Localidad 1656: Georef 22007010; departamento 22007; localidad censal 22007010.
-- Localidad 1657: Georef 22098010; departamento 22098; localidad censal 22098010.
-- Localidad 1658: Georef 22049010; departamento 22049; localidad censal 22049010.
-- Localidad 1659: Georef 22161020; departamento 22161; localidad censal 22161020.
-- Localidad 1660: Georef 22063010; departamento 22063; localidad censal 22063010.
-- Localidad 1661: Georef 22063020; departamento 22063; localidad censal 22063020.
-- Localidad 1662: Georef 22063030; departamento 22063; localidad censal 22063030.
-- Localidad 1663: Georef 22098020; departamento 22098; localidad censal 22098020.
-- Localidad 1664: Georef 22077020; departamento 22077; localidad censal 22077020.
-- Localidad 1665: Georef 22140040; departamento 22140; localidad censal 22140040.
-- Localidad 1666: Georef 22070030; departamento 22070; localidad censal 22070030.
-- Localidad 1667: Georef 22063040; departamento 22063; localidad censal 22063040.
-- Localidad 1668: Georef 22063050; departamento 22063; localidad censal 22063050.
-- Localidad 1669: Georef 22036010; departamento 22036; localidad censal 22036010.
-- Localidad 1670: Georef 22036020; departamento 22036; localidad censal 22036020.
-- Localidad 1671: Georef 22084020; departamento 22084; localidad censal 22084020.
-- Localidad 1672: Georef 22036030; departamento 22036; localidad censal 22036030.
-- Localidad 1673: Georef 22014010; departamento 22014; localidad censal 22014010.
-- Localidad 1674: Georef 22161030; departamento 22161; localidad censal 22161030.
-- Localidad 1675: Georef 22039010; departamento 22039; localidad censal 22039010.
-- Localidad 1676: Georef 22161040; departamento 22161; localidad censal 22161040.
-- Localidad 1677: Georef 22154040; departamento 22154; localidad censal 22154040.
-- Localidad 1678: Georef 22014020; departamento 22014; localidad censal 22014020.
-- Localidad 1679: Georef 22039020; departamento 22039; localidad censal 22039020.
-- Localidad 1680: Georef 22063060; departamento 22063; localidad censal 22063060.
-- Localidad 1681: Georef 22112010; departamento 22112; localidad censal 22112010.
-- Localidad 1682: Georef 22084030; departamento 22084; localidad censal 22084030.
-- Localidad 1683: Georef 22056010; departamento 22056; localidad censal 22056010.
-- Localidad 1684: Georef 22014030; departamento 22014; localidad censal 22014030.
-- Localidad 1685: Georef 22161050; departamento 22161; localidad censal 22161050.
-- Localidad 1686: Georef 22112020; departamento 22112; localidad censal 22112020.
-- Localidad 1687: Georef 22056020; departamento 22056; localidad censal 22056020.
-- Localidad 1688: Georef 22077030; departamento 22077; localidad censal 22077030.
-- Localidad 1689: Georef 22084040; departamento 22084; localidad censal 22084040.
-- Localidad 1690: Georef 22056030; departamento 22056; localidad censal 22056030.
-- Localidad 1691: Georef 22105010; departamento 22105; localidad censal 22105010.
-- Localidad 1692: Georef 22154050; departamento 22154; localidad censal 22154050.
-- Localidad 1693: Georef 22014040; departamento 22014; localidad censal 22014040.
-- Localidad 1694: Georef 22007020; departamento 22007; localidad censal 22007020.
-- Localidad 1695: Georef 22168020; departamento 22168; localidad censal 22168020.
-- Localidad 1696: Georef 22056040; departamento 22056; localidad censal 22056040.
-- Localidad 1697: Georef 22126030; departamento 22126; localidad censal 22126030.
-- Localidad 1698: Georef 22036040; departamento 22036; localidad censal 22036040.
-- Localidad 1699: Georef 22063070; departamento 22063; localidad censal 22063070.
-- Localidad 1700: Georef 22168030; departamento 22168; localidad censal 22168030.
-- Localidad 1701: Georef 22070040; departamento 22070; localidad censal 22070040.
-- Localidad 1702: Georef 22063080; departamento 22063; localidad censal 22063080.
-- Localidad 1703: Georef 22084050; departamento 22084; localidad censal 22084050.
-- Localidad 1704: Georef 22084060; departamento 22084; localidad censal 22084060.
-- Localidad 1705: Georef 22007030; departamento 22007; localidad censal 22007030.
-- Localidad 1706: Georef 22036050; departamento 22036; localidad censal 22036050.
-- Localidad 1707: Georef 22119010; departamento 22119; localidad censal 22119010.
-- Localidad 1708: Georef 22084070; departamento 22084; localidad censal 22084070.
-- Localidad 1709: Georef 22021010; departamento 22021; localidad censal 22021010.
-- Localidad 1710: Georef 22014050; departamento 22014; localidad censal 22014050.
-- Localidad 1711: Georef 22014060; departamento 22014; localidad censal 22014060.
-- Localidad 1712: Georef 22014070; departamento 22014; localidad censal 22014070.
-- Localidad 1713: Georef 22077040; departamento 22077; localidad censal 22077040.
-- Localidad 1714: Georef 22140050; departamento 22140; localidad censal 22140050.
-- Localidad 1715: Georef 22133010; departamento 22133; localidad censal 22133010.
-- Localidad 1716: Georef 22140060; departamento 22140; localidad censal 22140060.
-- Localidad 1717: Georef 22007040; departamento 22007; localidad censal 22007040.
-- Localidad 1718: Georef 22147010; departamento 22147; localidad censal 22147010.
-- Localidad 1719: Georef 22112030; departamento 22112; localidad censal 22112030.
-- Localidad 1720: Georef 22043020; departamento 22043; localidad censal 22043020.
-- Localidad 1721: Georef 22084080; departamento 22084; localidad censal 22084080.
-- Localidad 1722: Georef 22007050; departamento 22007; localidad censal 22007050.
-- Localidad 1723: Georef 22091010; departamento 22091; localidad censal 22091010.
-- Localidad 1724: Georef 22043030; departamento 22043; localidad censal 22043030.
-- Localidad 1725: Georef 22098030; departamento 22098; localidad censal 22098030.
-- Localidad 1726: Georef 22147020; departamento 22147; localidad censal 22147020.
-- Localidad 1727: Georef 22133020; departamento 22133; localidad censal 22133020.
-- Localidad 1728: Georef 22063100; departamento 22063; localidad censal 22063100.
-- Localidad 1729: Georef 22063110; departamento 22063; localidad censal 22063110.
-- Localidad 1730: Georef 22063120; departamento 22063; localidad censal 22063120.
-- Localidad 1731: Georef 26042040; departamento 26042; localidad censal 26042040.
-- Localidad 1732: Georef 2602103001; departamento 26021; localidad censal 26021030.
-- Localidad 1733: Georef 26084010; departamento 26084; localidad censal 26084010.
-- Localidad 1734: Georef 26084020; departamento 26084; localidad censal 26084020.
-- Localidad 1735: Georef 26056010; departamento 26056; localidad censal 26056010.
-- Localidad 1736: Georef 26035010; departamento 26035; localidad censal 26035010.
-- Localidad 1737: Georef 26084030; departamento 26084; localidad censal 26084030.
-- Localidad 1738: Georef 26007010; departamento 26007; localidad censal 26007010.
-- Localidad 1739: Georef 26021010; departamento 26021; localidad censal 26021010.
-- Localidad 1740: Georef 26021020; departamento 26021; localidad censal 26021020.
-- Localidad 1741: Georef 2602103016; departamento 26021; localidad censal 26021030.
-- Localidad 1742: Georef 2602103002; departamento 26021; localidad censal 26021030.
-- Localidad 1743: Georef 2602103004; departamento 26021; localidad censal 26021030.
-- Localidad 1744: Georef 2602103005; departamento 26021; localidad censal 26021030.
-- Localidad 1745: Georef 2602103006; departamento 26021; localidad censal 26021030.
-- Localidad 1746: Georef 2602103007; departamento 26021; localidad censal 26021030.
-- Localidad 1747: Georef 2602103008; departamento 26021; localidad censal 26021030.
-- Localidad 1748: Georef 2602103009; departamento 26021; localidad censal 26021030.
-- Localidad 1749: Georef 2602103010; departamento 26021; localidad censal 26021030.
-- Localidad 1750: Georef 2602103011; departamento 26021; localidad censal 26021030.
-- Localidad 1751: Georef 2602103012; departamento 26021; localidad censal 26021030.
-- Localidad 1752: Georef 2602103013; departamento 26021; localidad censal 26021030.
-- Localidad 1753: Georef 2602103014; departamento 26021; localidad censal 26021030.
-- Localidad 1754: Georef 2602103015; departamento 26021; localidad censal 26021030.
-- Localidad 1755: Georef 2602103017; departamento 26021; localidad censal 26021030.
-- Localidad 1756: Georef 26049010; departamento 26049; localidad censal 26049010.
-- Localidad 1757: Georef 26091010; departamento 26091; localidad censal 26091010.
-- Localidad 1758: Georef 26014010; departamento 26014; localidad censal 26014010.
-- Localidad 1759: Georef 2602103003; departamento 26021; localidad censal 26021030.
-- Localidad 1760: Georef 26028010; departamento 26028; localidad censal 26028010.
-- Localidad 1761: Georef 26056020; departamento 26056; localidad censal 26056020.
-- Localidad 1762: Georef 26070010; departamento 26070; localidad censal 26070010.
-- Localidad 1763: Georef 26014020; departamento 26014; localidad censal 26014020.
-- Localidad 1764: Georef 26056030; departamento 26056; localidad censal 26056030.
-- Localidad 1765: Georef 26021030; departamento 26021; localidad censal 26021030.
-- Localidad 1766: Georef 26035020; departamento 26035; localidad censal 26035020.
-- Localidad 1767: Georef 26014025; departamento 26014; localidad censal 26014025.
-- Localidad 1768: Georef 26014030; departamento 26014; localidad censal 26014030.
-- Localidad 1769: Georef 26021040; departamento 26021; localidad censal 26021040.
-- Localidad 1770: Georef 26042010; departamento 26042; localidad censal 26042010.
-- Localidad 1771: Georef 26098010; departamento 26098; localidad censal 26098010.
-- Localidad 1772: Georef 26084040; departamento 26084; localidad censal 26084040.
-- Localidad 1773: Georef 26042020; departamento 26042; localidad censal 26042020.
-- Localidad 1774: Georef 26049020; departamento 26049; localidad censal 26049020.
-- Localidad 1775: Georef 26014040; departamento 26014; localidad censal 26014040.
-- Localidad 1776: Georef 26014050; departamento 26014; localidad censal 26014050.
-- Localidad 1777: Georef 26063010; departamento 26063; localidad censal 26063010.
-- Localidad 1778: Georef 26014060; departamento 26014; localidad censal 26014060.
-- Localidad 1779: Georef 26035030; departamento 26035; localidad censal 26035030.
-- Localidad 1780: Georef 26084050; departamento 26084; localidad censal 26084050.
-- Localidad 1781: Georef 26014065; departamento 26014; localidad censal 26014065.
-- Localidad 1782: Georef 26042030; departamento 26042; localidad censal 26042030.
-- Localidad 1783: Georef 26105010; departamento 26105; localidad censal 26105010.
-- Localidad 1784: Georef 26028020; departamento 26028; localidad censal 26028020.
-- Localidad 1785: Georef 26049030; departamento 26049; localidad censal 26049030.
-- Localidad 1786: Georef 26098020; departamento 26098; localidad censal 26098020.
-- Localidad 1787: Georef 26014070; departamento 26014; localidad censal 26014070.
-- Localidad 1788: Georef 26098030; departamento 26098; localidad censal 26098030.
-- Localidad 1789: Georef 2602103021; departamento 26021; localidad censal 26021030.
-- Localidad 1790: Georef 2602103022; departamento 26021; localidad censal 26021030.
-- Localidad 1791: Georef 2602103019; departamento 26021; localidad censal 26021030.
-- Localidad 1792: Georef 2602103020; departamento 26021; localidad censal 26021030.
-- Localidad 1793: Georef 26084060; departamento 26084; localidad censal 26084060.
-- Localidad 1794: Georef 26014080; departamento 26014; localidad censal 26014080.
-- Localidad 1795: Georef 26014090; departamento 26014; localidad censal 26014090.
-- Localidad 1796: Georef 26035040; departamento 26035; localidad censal 26035040.
-- Localidad 1797: Georef 26049040; departamento 26049; localidad censal 26049040.
-- Localidad 1798: Georef 26063020; departamento 26063; localidad censal 26063020.
-- Localidad 1799: Georef 26014100; departamento 26014; localidad censal 26014100.
-- Localidad 1800: Georef 26070020; departamento 26070; localidad censal 26070020.
-- Localidad 1801: Georef 26035050; departamento 26035; localidad censal 26035050.
-- Localidad 1802: Georef 26070030; departamento 26070; localidad censal 26070030.
-- Localidad 1803: Georef 26056040; departamento 26056; localidad censal 26056040.
-- Localidad 1804: Georef 26077010; departamento 26077; localidad censal 26077010.
-- Localidad 1805: Georef 26077020; departamento 26077; localidad censal 26077020.
-- Localidad 1806: Georef 26007020; departamento 26007; localidad censal 26007020.
-- Localidad 1807: Georef 26007030; departamento 26007; localidad censal 26007030.
-- Localidad 1808: Georef 26007040; departamento 26007; localidad censal 26007040.
-- Localidad 1809: Georef 26021050; departamento 26021; localidad censal 26021050.
-- Localidad 1810: Georef 26077030; departamento 26077; localidad censal 26077030.
-- Localidad 1811: Georef 26007050; departamento 26007; localidad censal 26007050.
-- Localidad 1812: Georef 26084070; departamento 26084; localidad censal 26084070.
-- Localidad 1813: Georef 26098040; departamento 26098; localidad censal 26098040.
-- Localidad 1814: Georef 26091020; departamento 26091; localidad censal 26091020.
-- Localidad 1815: Georef 26056050; departamento 26056; localidad censal 26056050.
-- Localidad 1816: Georef 26105020; departamento 26105; localidad censal 26105020.
-- Localidad 1817: Georef 26077040; departamento 26077; localidad censal 26077040.
-- Localidad 1818: Georef 26035060; departamento 26035; localidad censal 26035060.
-- Localidad 1819: Georef 26035070; departamento 26035; localidad censal 26035070.
-- Localidad 1820: Georef 26049050; departamento 26049; localidad censal 26049050.
-- Localidad 1821: Georef 30098090; departamento 30098; localidad censal 30098090.
-- Localidad 1822: Georef 30070005; departamento 30070; localidad censal 30070005.
-- Localidad 1823: Georef 30049010; departamento 30049; localidad censal 30049010.
-- Localidad 1824: Georef 30021010; departamento 30021; localidad censal 30021010.
-- Localidad 1825: Georef 30021015; departamento 30021; localidad censal 30021015.
-- Localidad 1826: Georef 30084010; departamento 30084; localidad censal 30084010.
-- Localidad 1827: Georef 30021020; departamento 30021; localidad censal 30021020.
-- Localidad 1828: Georef 30021030; departamento 30021; localidad censal 30021030.
-- Localidad 1829: Georef 30056010; departamento 30056; localidad censal 30056010.
-- Localidad 1830: Georef 30021040; departamento 30021; localidad censal 30021040.
-- Localidad 1831: Georef 30035020; departamento 30035; localidad censal 30035020.
-- Localidad 1832: Georef 30056020; departamento 30056; localidad censal 30056020.
-- Localidad 1833: Georef 30084015; departamento 30084; localidad censal 30084015.
-- Localidad 1834: Georef 30084020; departamento 30084; localidad censal 30084020.
-- Localidad 1835: Georef 30084030; departamento 30084; localidad censal 30084030.
-- Localidad 1836: Georef 30084040; departamento 30084; localidad censal 30084040.
-- Localidad 1837: Georef 30021050; departamento 30021; localidad censal 30021050.
-- Localidad 1838: Georef 30021060; departamento 30021; localidad censal 30021060.
-- Localidad 1839: Georef 30091010; departamento 30091; localidad censal 30091010.
-- Localidad 1840: Georef 30105010; departamento 30105; localidad censal 30105010.
-- Localidad 1841: Georef 30077010; departamento 30077; localidad censal 30077010.
-- Localidad 1842: Georef 30008010; departamento 30008; localidad censal 30008010.
-- Localidad 1843: Georef 30098010; departamento 30098; localidad censal 30098010.
-- Localidad 1844: Georef 3001506001; departamento 30015; localidad censal 30015060.
-- Localidad 1845: Georef 30077020; departamento 30077; localidad censal 30077020.
-- Localidad 1846: Georef 30070010; departamento 30070; localidad censal 30070010.
-- Localidad 1847: Georef 30015010; departamento 30015; localidad censal 30015010.
-- Localidad 1848: Georef 30098020; departamento 30098; localidad censal 30098020.
-- Localidad 1849: Georef 30063020; departamento 30063; localidad censal 30063020.
-- Localidad 1850: Georef 30084050; departamento 30084; localidad censal 30084050.
-- Localidad 1851: Georef 30028010; departamento 30028; localidad censal 30028010.
-- Localidad 1852: Georef 30015020; departamento 30015; localidad censal 30015020.
-- Localidad 1853: Georef 30008020; departamento 30008; localidad censal 30008020.
-- Localidad 1854: Georef 30028020; departamento 30028; localidad censal 30028020.
-- Localidad 1855: Georef 30084060; departamento 30084; localidad censal 30084060.
-- Localidad 1856: Georef 30070020; departamento 30070; localidad censal 30070020.
-- Localidad 1857: Georef 30015030; departamento 30015; localidad censal 30015030.
-- Localidad 1858: Georef 30084065; departamento 30084; localidad censal 30084065.
-- Localidad 1859: Georef 30098030; departamento 30098; localidad censal 30098030.
-- Localidad 1860: Georef 30021070; departamento 30021; localidad censal 30021070.
-- Localidad 1861: Georef 30015040; departamento 30015; localidad censal 30015040.
-- Localidad 1862: Georef 30008030; departamento 30008; localidad censal 30008030.
-- Localidad 1863: Georef 30028040; departamento 30028; localidad censal 30028040.
-- Localidad 1864: Georef 30098040; departamento 30098; localidad censal 30098040.
-- Localidad 1865: Georef 30015060; departamento 30015; localidad censal 30015060.
-- Localidad 1866: Georef 30035010; departamento 30035; localidad censal 30035010.
-- Localidad 1867: Georef 30084070; departamento 30084; localidad censal 30084070.
-- Localidad 1868: Georef 30021080; departamento 30021; localidad censal 30021080.
-- Localidad 1869: Georef 30077030; departamento 30077; localidad censal 30077030.
-- Localidad 1870: Georef 30091020; departamento 30091; localidad censal 30091020.
-- Localidad 1871: Georef 3000809001; departamento 30008; localidad censal 30008090.
-- Localidad 1872: Georef 3000809002; departamento 30008; localidad censal 30008090.
-- Localidad 1873: Georef 30084080; departamento 30084; localidad censal 30084080.
-- Localidad 1874: Georef 30084090; departamento 30084; localidad censal 30084090.
-- Localidad 1875: Georef 30084095; departamento 30084; localidad censal 30084095.
-- Localidad 1876: Georef 30070030; departamento 30070; localidad censal 30070030.
-- Localidad 1877: Georef 30056030; departamento 30056; localidad censal 30056030.
-- Localidad 1878: Georef 30091030; departamento 30091; localidad censal 30091030.
-- Localidad 1879: Georef 30021090; departamento 30021; localidad censal 30021090.
-- Localidad 1880: Georef 30056035; departamento 30056; localidad censal 30056035.
-- Localidad 1881: Georef 30049020; departamento 30049; localidad censal 30049020.
-- Localidad 1882: Georef 3002115001; departamento 30021; localidad censal 30021150.
-- Localidad 1883: Georef 30113010; departamento 30113; localidad censal 30113010.
-- Localidad 1884: Georef 30015080; departamento 30015; localidad censal 30015080.
-- Localidad 1885: Georef 30015083; departamento 30015; localidad censal 30015083.
-- Localidad 1886: Georef 30015087; departamento 30015; localidad censal 30015087.
-- Localidad 1887: Georef 30056040; departamento 30056; localidad censal 30056040.
-- Localidad 1888: Georef 30077040; departamento 30077; localidad censal 30077040.
-- Localidad 1889: Georef 30028070; departamento 30028; localidad censal 30028070.
-- Localidad 1890: Georef 30035030; departamento 30035; localidad censal 30035030.
-- Localidad 1891: Georef 30056050; departamento 30056; localidad censal 30056050.
-- Localidad 1892: Georef 30021100; departamento 30021; localidad censal 30021100.
-- Localidad 1893: Georef 30088010; departamento 30088; localidad censal 30088010.
-- Localidad 1894: Georef 30049030; departamento 30049; localidad censal 30049030.
-- Localidad 1895: Georef 30021110; departamento 30021; localidad censal 30021110.
-- Localidad 1896: Georef 30021120; departamento 30021; localidad censal 30021120.
-- Localidad 1897: Georef 30056060; departamento 30056; localidad censal 30056060.
-- Localidad 1898: Georef 30091040; departamento 30091; localidad censal 30091040.
-- Localidad 1899: Georef 30091050; departamento 30091; localidad censal 30091050.
-- Localidad 1900: Georef 30091060; departamento 30091; localidad censal 30091060.
-- Localidad 1901: Georef 30049040; departamento 30049; localidad censal 30049040.
-- Localidad 1902: Georef 30056070; departamento 30056; localidad censal 30056070.
-- Localidad 1903: Georef 30091070; departamento 30091; localidad censal 30091070.
-- Localidad 1904: Georef 30008040; departamento 30008; localidad censal 30008040.
-- Localidad 1905: Georef 30084100; departamento 30084; localidad censal 30084100.
-- Localidad 1906: Georef 30084110; departamento 30084; localidad censal 30084110.
-- Localidad 1907: Georef 30077050; departamento 30077; localidad censal 30077050.
-- Localidad 1908: Georef 30098060; departamento 30098; localidad censal 30098060.
-- Localidad 1909: Georef 30008050; departamento 30008; localidad censal 30008050.
-- Localidad 1910: Georef 30063030; departamento 30063; localidad censal 30063030.
-- Localidad 1911: Georef 30113020; departamento 30113; localidad censal 30113020.
-- Localidad 1912: Georef 30056080; departamento 30056; localidad censal 30056080.
-- Localidad 1913: Georef 30113030; departamento 30113; localidad censal 30113030.
-- Localidad 1914: Georef 30008060; departamento 30008; localidad censal 30008060.
-- Localidad 1915: Georef 30015090; departamento 30015; localidad censal 30015090.
-- Localidad 1916: Georef 30021123; departamento 30021; localidad censal 30021123.
-- Localidad 1917: Georef 30070040; departamento 30070; localidad censal 30070040.
-- Localidad 1918: Georef 30084120; departamento 30084; localidad censal 30084120.
-- Localidad 1919: Georef 30056090; departamento 30056; localidad censal 30056090.
-- Localidad 1920: Georef 30091080; departamento 30091; localidad censal 30091080.
-- Localidad 1921: Georef 30021127; departamento 30021; localidad censal 30021127.
-- Localidad 1922: Georef 30098070; departamento 30098; localidad censal 30098070.
-- Localidad 1923: Georef 3001506003; departamento 30015; localidad censal 30015060.
-- Localidad 1924: Georef 30084130; departamento 30084; localidad censal 30084130.
-- Localidad 1925: Georef 30098080; departamento 30098; localidad censal 30098080.
-- Localidad 1926: Georef 30015100; departamento 30015; localidad censal 30015100.
-- Localidad 1927: Georef 30028080; departamento 30028; localidad censal 30028080.
-- Localidad 1928: Georef 30077060; departamento 30077; localidad censal 30077060.
-- Localidad 1929: Georef 30091090; departamento 30091; localidad censal 30091090.
-- Localidad 1930: Georef 30084140; departamento 30084; localidad censal 30084140.
-- Localidad 1931: Georef 30063040; departamento 30063; localidad censal 30063040.
-- Localidad 1932: Georef 30105040; departamento 30105; localidad censal 30105040.
-- Localidad 1933: Georef 30077070; departamento 30077; localidad censal 30077070.
-- Localidad 1934: Georef 30015110; departamento 30015; localidad censal 30015110.
-- Localidad 1935: Georef 30035040; departamento 30035; localidad censal 30035040.
-- Localidad 1936: Georef 30084150; departamento 30084; localidad censal 30084150.
-- Localidad 1937: Georef 30015120; departamento 30015; localidad censal 30015120.
-- Localidad 1938: Georef 30021130; departamento 30021; localidad censal 30021130.
-- Localidad 1939: Georef 30084160; departamento 30084; localidad censal 30084160.
-- Localidad 1940: Georef 30113050; departamento 30113; localidad censal 30113050.
-- Localidad 1941: Georef 30056095; departamento 30056; localidad censal 30056095.
-- Localidad 1942: Georef 30015130; departamento 30015; localidad censal 30015130.
-- Localidad 1943: Georef 30070050; departamento 30070; localidad censal 30070050.
-- Localidad 1944: Georef 30098100; departamento 30098; localidad censal 30098100.
-- Localidad 1945: Georef 30084170; departamento 30084; localidad censal 30084170.
-- Localidad 1946: Georef 30084180; departamento 30084; localidad censal 30084180.
-- Localidad 1947: Georef 30008070; departamento 30008; localidad censal 30008070.
-- Localidad 1948: Georef 30056100; departamento 30056; localidad censal 30056100.
-- Localidad 1949: Georef 30084190; departamento 30084; localidad censal 30084190.
-- Localidad 1950: Georef 30008080; departamento 30008; localidad censal 30008080.
-- Localidad 1951: Georef 3008405002; departamento 30084; localidad censal 30084050.
-- Localidad 1952: Georef 30021140; departamento 30021; localidad censal 30021140.
-- Localidad 1953: Georef 30049050; departamento 30049; localidad censal 30049050.
-- Localidad 1954: Georef 30015140; departamento 30015; localidad censal 30015140.
-- Localidad 1955: Georef 30098110; departamento 30098; localidad censal 30098110.
-- Localidad 1956: Georef 30091100; departamento 30091; localidad censal 30091100.
-- Localidad 1957: Georef 30084200; departamento 30084; localidad censal 30084200.
-- Localidad 1958: Georef 30070070; departamento 30070; localidad censal 30070070.
-- Localidad 1959: Georef 30028090; departamento 30028; localidad censal 30028090.
-- Localidad 1960: Georef 30008090; departamento 30008; localidad censal 30008090.
-- Localidad 1961: Georef 30042010; departamento 30042; localidad censal 30042010.
-- Localidad 1962: Georef 30028100; departamento 30028; localidad censal 30028100.
-- Localidad 1963: Georef 30028105; departamento 30028; localidad censal 30028105.
-- Localidad 1964: Georef 30088020; departamento 30088; localidad censal 30088020.
-- Localidad 1965: Georef 30042020; departamento 30042; localidad censal 30042020.
-- Localidad 1966: Georef 30028110; departamento 30028; localidad censal 30028110.
-- Localidad 1967: Georef 30098120; departamento 30098; localidad censal 30098120.
-- Localidad 1968: Georef 30070080; departamento 30070; localidad censal 30070080.
-- Localidad 1969: Georef 30035050; departamento 30035; localidad censal 30035050.
-- Localidad 1970: Georef 30084210; departamento 30084; localidad censal 30084210.
-- Localidad 1971: Georef 30084220; departamento 30084; localidad censal 30084220.
-- Localidad 1972: Georef 30084230; departamento 30084; localidad censal 30084230.
-- Localidad 1973: Georef 30070090; departamento 30070; localidad censal 30070090.
-- Localidad 1974: Georef 30084240; departamento 30084; localidad censal 30084240.
-- Localidad 1975: Georef 3002108002; departamento 30021; localidad censal 30021080.
-- Localidad 1976: Georef 30084250; departamento 30084; localidad censal 30084250.
-- Localidad 1977: Georef 30084260; departamento 30084; localidad censal 30084260.
-- Localidad 1978: Georef 30008100; departamento 30008; localidad censal 30008100.
-- Localidad 1979: Georef 30056110; departamento 30056; localidad censal 30056110.
-- Localidad 1980: Georef 30084270; departamento 30084; localidad censal 30084270.
-- Localidad 1981: Georef 30105060; departamento 30105; localidad censal 30105060.
-- Localidad 1982: Georef 3001506002; departamento 30015; localidad censal 30015060.
-- Localidad 1983: Georef 30113060; departamento 30113; localidad censal 30113060.
-- Localidad 1984: Georef 30028120; departamento 30028; localidad censal 30028120.
-- Localidad 1985: Georef 30113070; departamento 30113; localidad censal 30113070.
-- Localidad 1986: Georef 30008110; departamento 30008; localidad censal 30008110.
-- Localidad 1987: Georef 30084280; departamento 30084; localidad censal 30084280.
-- Localidad 1988: Georef 30084290; departamento 30084; localidad censal 30084290.
-- Localidad 1989: Georef 30021150; departamento 30021; localidad censal 30021150.
-- Localidad 1990: Georef 30098130; departamento 30098; localidad censal 30098130.
-- Localidad 1991: Georef 30063060; departamento 30063; localidad censal 30063060.
-- Localidad 1992: Georef 30098140; departamento 30098; localidad censal 30098140.
-- Localidad 1993: Georef 30098150; departamento 30098; localidad censal 30098150.
-- Localidad 1994: Georef 30084300; departamento 30084; localidad censal 30084300.
-- Localidad 1995: Georef 3001506007; departamento 30015; localidad censal 30015060.
-- Localidad 1996: Georef 30113080; departamento 30113; localidad censal 30113080.
-- Localidad 1997: Georef 30077080; departamento 30077; localidad censal 30077080.
-- Localidad 1998: Georef 34021010; departamento 34021; localidad censal 34021010.
-- Localidad 1999: Georef 34035010; departamento 34035; localidad censal 34035010.
-- Localidad 2000: Georef 34042010; departamento 34042; localidad censal 34042010.
-- Localidad 2001: Georef 34049010; departamento 34049; localidad censal 34049010.
-- Localidad 2002: Georef 34056010; departamento 34056; localidad censal 34056010.
-- Localidad 2003: Georef 34014010; departamento 34014; localidad censal 34014010.
-- Localidad 2004: Georef 34035020; departamento 34035; localidad censal 34035020.
-- Localidad 2005: Georef 34035030; departamento 34035; localidad censal 34035030.
-- Localidad 2006: Georef 3403501002; departamento 34035; localidad censal 34035010.
-- Localidad 2007: Georef 34056020; departamento 34056; localidad censal 34056020.
-- Localidad 2008: Georef 34042020; departamento 34042; localidad censal 34042020.
-- Localidad 2009: Georef 34063010; departamento 34063; localidad censal 34063010.
-- Localidad 2010: Georef 34063030; departamento 34063; localidad censal 34063030.
-- Localidad 2011: Georef 34035040; departamento 34035; localidad censal 34035040.
-- Localidad 2012: Georef 34035050; departamento 34035; localidad censal 34035050.
-- Localidad 2013: Georef 34014020; departamento 34014; localidad censal 34014020.
-- Localidad 2014: Georef 34035060; departamento 34035; localidad censal 34035060.
-- Localidad 2015: Georef 34035070; departamento 34035; localidad censal 34035070.
-- Localidad 2016: Georef 34007003; departamento 34007; localidad censal 34007003.
-- Localidad 2017: Georef 34021020; departamento 34021; localidad censal 34021020.
-- Localidad 2018: Georef 34063020; departamento 34063; localidad censal 34063020.
-- Localidad 2019: Georef 34014030; departamento 34014; localidad censal 34014030.
-- Localidad 2020: Georef 34007005; departamento 34007; localidad censal 34007005.
-- Localidad 2021: Georef 34021030; departamento 34021; localidad censal 34021030.
-- Localidad 2022: Georef 34035080; departamento 34035; localidad censal 34035080.
-- Localidad 2023: Georef 34028010; departamento 34028; localidad censal 34028010.
-- Localidad 2024: Georef 34035090; departamento 34035; localidad censal 34035090.
-- Localidad 2025: Georef 34007007; departamento 34007; localidad censal 34007007.
-- Localidad 2026: Georef 34049020; departamento 34049; localidad censal 34049020.
-- Localidad 2027: Georef 34042030; departamento 34042; localidad censal 34042030.
-- Localidad 2028: Georef 34049030; departamento 34049; localidad censal 34049030.
-- Localidad 2029: Georef 34007010; departamento 34007; localidad censal 34007010.
-- Localidad 2030: Georef 34007015; departamento 34007; localidad censal 34007015.
-- Localidad 2031: Georef 34035100; departamento 34035; localidad censal 34035100.
-- Localidad 2032: Georef 34007020; departamento 34007; localidad censal 34007020.
-- Localidad 2033: Georef 34014040; departamento 34014; localidad censal 34014040.
-- Localidad 2034: Georef 34042040; departamento 34042; localidad censal 34042040.
-- Localidad 2035: Georef 34014050; departamento 34014; localidad censal 34014050.
-- Localidad 2036: Georef 34049040; departamento 34049; localidad censal 34049040.
-- Localidad 2037: Georef 34056030; departamento 34056; localidad censal 34056030.
-- Localidad 2038: Georef 34056040; departamento 34056; localidad censal 34056040.
-- Localidad 2039: Georef 34042050; departamento 34042; localidad censal 34042050.
-- Localidad 2040: Georef 34035110; departamento 34035; localidad censal 34035110.
-- Localidad 2041: Georef 34007030; departamento 34007; localidad censal 34007030.
-- Localidad 2042: Georef 34007040; departamento 34007; localidad censal 34007040.
-- Localidad 2043: Georef 34035120; departamento 34035; localidad censal 34035120.
-- Localidad 2044: Georef 34049050; departamento 34049; localidad censal 34049050.
-- Localidad 2045: Georef 34049060; departamento 34049; localidad censal 34049060.
-- Localidad 2046: Georef 34049070; departamento 34049; localidad censal 34049070.
-- Localidad 2047: Georef 34021040; departamento 34021; localidad censal 34021040.
-- Localidad 2048: Georef 34014060; departamento 34014; localidad censal 34014060.
-- Localidad 2049: Georef 34035130; departamento 34035; localidad censal 34035130.
-- Localidad 2050: Georef 34035140; departamento 34035; localidad censal 34035140.
-- Localidad 2051: Georef 34049080; departamento 34049; localidad censal 34049080.
-- Localidad 2052: Georef 34035150; departamento 34035; localidad censal 34035150.
-- Localidad 2053: Georef 34021050; departamento 34021; localidad censal 34021050.
-- Localidad 2054: Georef 34042060; departamento 34042; localidad censal 34042060.
-- Localidad 2055: Georef 34007050; departamento 34007; localidad censal 34007050.
-- Localidad 2056: Georef 34021060; departamento 34021; localidad censal 34021060.
-- Localidad 2057: Georef 34035160; departamento 34035; localidad censal 34035160.
-- Localidad 2058: Georef 34035170; departamento 34035; localidad censal 34035170.
-- Localidad 2059: Georef 34056050; departamento 34056; localidad censal 34056050.
-- Localidad 2060: Georef 38007010; departamento 38007; localidad censal 38007010.
-- Localidad 2061: Georef 38007020; departamento 38007; localidad censal 38007020.
-- Localidad 2062: Georef 38007030; departamento 38007; localidad censal 38007030.
-- Localidad 2063: Georef 38007035; departamento 38007; localidad censal 38007035.
-- Localidad 2064: Georef 38014010; departamento 38014; localidad censal 38014010.
-- Localidad 2065: Georef 38028003; departamento 38028; localidad censal 38028003.
-- Localidad 2066: Georef 38063010; departamento 38063; localidad censal 38063010.
-- Localidad 2067: Georef 38063020; departamento 38063; localidad censal 38063020.
-- Localidad 2068: Georef 38035010; departamento 38035; localidad censal 38035010.
-- Localidad 2069: Georef 38098010; departamento 38098; localidad censal 38098010.
-- Localidad 2070: Georef 38014020; departamento 38014; localidad censal 38014020.
-- Localidad 2071: Georef 38014030; departamento 38014; localidad censal 38014030.
-- Localidad 2072: Georef 38112010; departamento 38112; localidad censal 38112010.
-- Localidad 2073: Georef 38035020; departamento 38035; localidad censal 38035020.
-- Localidad 2074: Georef 38035030; departamento 38035; localidad censal 38035030.
-- Localidad 2075: Georef 38035040; departamento 38035; localidad censal 38035040.
-- Localidad 2076: Georef 38112020; departamento 38112; localidad censal 38112020.
-- Localidad 2077: Georef 38042010; departamento 38042; localidad censal 38042010.
-- Localidad 2078: Georef 38049003; departamento 38049; localidad censal 38049003.
-- Localidad 2079: Georef 38007040; departamento 38007; localidad censal 38007040.
-- Localidad 2080: Georef 38077010; departamento 38077; localidad censal 38077010.
-- Localidad 2081: Georef 38105010; departamento 38105; localidad censal 38105010.
-- Localidad 2082: Georef 38084010; departamento 38084; localidad censal 38084010.
-- Localidad 2083: Georef 38042020; departamento 38042; localidad censal 38042020.
-- Localidad 2084: Georef 38035050; departamento 38035; localidad censal 38035050.
-- Localidad 2085: Georef 38028007; departamento 38028; localidad censal 38028007.
-- Localidad 2086: Georef 38077020; departamento 38077; localidad censal 38077020.
-- Localidad 2087: Georef 38077030; departamento 38077; localidad censal 38077030.
-- Localidad 2088: Georef 38007050; departamento 38007; localidad censal 38007050.
-- Localidad 2089: Georef 38028010; departamento 38028; localidad censal 38028010.
-- Localidad 2090: Georef 38094010; departamento 38094; localidad censal 38094010.
-- Localidad 2091: Georef 38084020; departamento 38084; localidad censal 38084020.
-- Localidad 2092: Georef 38049007; departamento 38049; localidad censal 38049007.
-- Localidad 2093: Georef 38077040; departamento 38077; localidad censal 38077040.
-- Localidad 2094: Georef 38063030; departamento 38063; localidad censal 38063030.
-- Localidad 2095: Georef 38063040; departamento 38063; localidad censal 38063040.
-- Localidad 2096: Georef 38028020; departamento 38028; localidad censal 38028020.
-- Localidad 2097: Georef 38077045; departamento 38077; localidad censal 38077045.
-- Localidad 2098: Georef 38014040; departamento 38014; localidad censal 38014040.
-- Localidad 2099: Georef 38056010; departamento 38056; localidad censal 38056010.
-- Localidad 2100: Georef 38112030; departamento 38112; localidad censal 38112030.
-- Localidad 2101: Georef 38070010; departamento 38070; localidad censal 38070010.
-- Localidad 2102: Georef 38098020; departamento 38098; localidad censal 38098020.
-- Localidad 2103: Georef 38070020; departamento 38070; localidad censal 38070020.
-- Localidad 2104: Georef 38063050; departamento 38063; localidad censal 38063050.
-- Localidad 2105: Georef 38063060; departamento 38063; localidad censal 38063060.
-- Localidad 2106: Georef 38070030; departamento 38070; localidad censal 38070030.
-- Localidad 2107: Georef 38084030; departamento 38084; localidad censal 38084030.
-- Localidad 2108: Georef 3801401002; departamento 38014; localidad censal 38014010.
-- Localidad 2109: Georef 38035060; departamento 38035; localidad censal 38035060.
-- Localidad 2110: Georef 38021010; departamento 38021; localidad censal 38021010.
-- Localidad 2111: Georef 38028030; departamento 38028; localidad censal 38028030.
-- Localidad 2112: Georef 38094020; departamento 38094; localidad censal 38094020.
-- Localidad 2113: Georef 38084040; departamento 38084; localidad censal 38084040.
-- Localidad 2114: Georef 38028040; departamento 38028; localidad censal 38028040.
-- Localidad 2115: Georef 38084045; departamento 38084; localidad censal 38084045.
-- Localidad 2116: Georef 38094030; departamento 38094; localidad censal 38094030.
-- Localidad 2117: Georef 38021020; departamento 38021; localidad censal 38021020.
-- Localidad 2118: Georef 38077050; departamento 38077; localidad censal 38077050.
-- Localidad 2119: Georef 38063070; departamento 38063; localidad censal 38063070.
-- Localidad 2120: Georef 38112040; departamento 38112; localidad censal 38112040.
-- Localidad 2121: Georef 38063080; departamento 38063; localidad censal 38063080.
-- Localidad 2122: Georef 38063090; departamento 38063; localidad censal 38063090.
-- Localidad 2123: Georef 38112050; departamento 38112; localidad censal 38112050.
-- Localidad 2124: Georef 38007055; departamento 38007; localidad censal 38007055.
-- Localidad 2125: Georef 38049010; departamento 38049; localidad censal 38049010.
-- Localidad 2126: Georef 38021030; departamento 38021; localidad censal 38021030.
-- Localidad 2127: Georef 38035070; departamento 38035; localidad censal 38035070.
-- Localidad 2128: Georef 38035080; departamento 38035; localidad censal 38035080.
-- Localidad 2129: Georef 38049020; departamento 38049; localidad censal 38049020.
-- Localidad 2130: Georef 38112060; departamento 38112; localidad censal 38112060.
-- Localidad 2131: Georef 38049025; departamento 38049; localidad censal 38049025.
-- Localidad 2132: Georef 38056017; departamento 38056; localidad censal 38056017.
-- Localidad 2133: Georef 38014050; departamento 38014; localidad censal 38014050.
-- Localidad 2134: Georef 3802107001; departamento 38021; localidad censal 38021070.
-- Localidad 2135: Georef 38056020; departamento 38056; localidad censal 38056020.
-- Localidad 2136: Georef 38021040; departamento 38021; localidad censal 38021040.
-- Localidad 2137: Georef 38094040; departamento 38094; localidad censal 38094040.
-- Localidad 2138: Georef 38035090; departamento 38035; localidad censal 38035090.
-- Localidad 2139: Georef 38014060; departamento 38014; localidad censal 38014060.
-- Localidad 2140: Georef 38084050; departamento 38084; localidad censal 38084050.
-- Localidad 2141: Georef 38077060; departamento 38077; localidad censal 38077060.
-- Localidad 2142: Georef 38014070; departamento 38014; localidad censal 38014070.
-- Localidad 2143: Georef 38056025; departamento 38056; localidad censal 38056025.
-- Localidad 2144: Georef 38049030; departamento 38049; localidad censal 38049030.
-- Localidad 2145: Georef 38021050; departamento 38021; localidad censal 38021050.
-- Localidad 2146: Georef 38084055; departamento 38084; localidad censal 38084055.
-- Localidad 2147: Georef 38084060; departamento 38084; localidad censal 38084060.
-- Localidad 2148: Georef 38077070; departamento 38077; localidad censal 38077070.
-- Localidad 2149: Georef 38049035; departamento 38049; localidad censal 38049035.
-- Localidad 2150: Georef 38077080; departamento 38077; localidad censal 38077080.
-- Localidad 2151: Georef 38028043; departamento 38028; localidad censal 38028043.
-- Localidad 2152: Georef 38028045; departamento 38028; localidad censal 38028045.
-- Localidad 2153: Georef 38070040; departamento 38070; localidad censal 38070040.
-- Localidad 2154: Georef 38063110; departamento 38063; localidad censal 38063110.
-- Localidad 2155: Georef 38042040; departamento 38042; localidad censal 38042040.
-- Localidad 2156: Georef 38014080; departamento 38014; localidad censal 38014080.
-- Localidad 2157: Georef 38105020; departamento 38105; localidad censal 38105020.
-- Localidad 2158: Georef 38084070; departamento 38084; localidad censal 38084070.
-- Localidad 2159: Georef 38035100; departamento 38035; localidad censal 38035100.
-- Localidad 2160: Georef 38014090; departamento 38014; localidad censal 38014090.
-- Localidad 2161: Georef 38063130; departamento 38063; localidad censal 38063130.
-- Localidad 2162: Georef 3801401003; departamento 38014; localidad censal 38014010.
-- Localidad 2163: Georef 3803508002; departamento 38035; localidad censal 38035080.
-- Localidad 2164: Georef 38070050; departamento 38070; localidad censal 38070050.
-- Localidad 2165: Georef 38098025; departamento 38098; localidad censal 38098025.
-- Localidad 2166: Georef 38007060; departamento 38007; localidad censal 38007060.
-- Localidad 2167: Georef 38084080; departamento 38084; localidad censal 38084080.
-- Localidad 2168: Georef 38014100; departamento 38014; localidad censal 38014100.
-- Localidad 2169: Georef 38112070; departamento 38112; localidad censal 38112070.
-- Localidad 2170: Georef 38098030; departamento 38098; localidad censal 38098030.
-- Localidad 2171: Georef 38007063; departamento 38007; localidad censal 38007063.
-- Localidad 2172: Georef 38007067; departamento 38007; localidad censal 38007067.
-- Localidad 2173: Georef 38049040; departamento 38049; localidad censal 38049040.
-- Localidad 2174: Georef 38007070; departamento 38007; localidad censal 38007070.
-- Localidad 2175: Georef 3804204002; departamento 38042; localidad censal 38042040.
-- Localidad 2176: Georef 38063140; departamento 38063; localidad censal 38063140.
-- Localidad 2177: Georef 38028047; departamento 38028; localidad censal 38028047.
-- Localidad 2178: Georef 38063150; departamento 38063; localidad censal 38063150.
-- Localidad 2179: Georef 38056030; departamento 38056; localidad censal 38056030.
-- Localidad 2180: Georef 38063160; departamento 38063; localidad censal 38063160.
-- Localidad 2181: Georef 38105030; departamento 38105; localidad censal 38105030.
-- Localidad 2182: Georef 38007080; departamento 38007; localidad censal 38007080.
-- Localidad 2183: Georef 38014110; departamento 38014; localidad censal 38014110.
-- Localidad 2184: Georef 3811208001; departamento 38112; localidad censal 38112080.
-- Localidad 2185: Georef 38077090; departamento 38077; localidad censal 38077090.
-- Localidad 2186: Georef 38084090; departamento 38084; localidad censal 38084090.
-- Localidad 2187: Georef 38014120; departamento 38014; localidad censal 38014120.
-- Localidad 2188: Georef 38063170; departamento 38063; localidad censal 38063170.
-- Localidad 2189: Georef 3802107002; departamento 38021; localidad censal 38021070.
-- Localidad 2190: Georef 38063180; departamento 38063; localidad censal 38063180.
-- Localidad 2191: Georef 38021060; departamento 38021; localidad censal 38021060.
-- Localidad 2192: Georef 38105040; departamento 38105; localidad censal 38105040.
-- Localidad 2193: Georef 38007085; departamento 38007; localidad censal 38007085.
-- Localidad 2194: Georef 38077100; departamento 38077; localidad censal 38077100.
-- Localidad 2195: Georef 38070060; departamento 38070; localidad censal 38070060.
-- Localidad 2196: Georef 38007090; departamento 38007; localidad censal 38007090.
-- Localidad 2197: Georef 38084100; departamento 38084; localidad censal 38084100.
-- Localidad 2198: Georef 38007095; departamento 38007; localidad censal 38007095.
-- Localidad 2199: Georef 38021065; departamento 38021; localidad censal 38021065.
-- Localidad 2200: Georef 38094050; departamento 38094; localidad censal 38094050.
-- Localidad 2201: Georef 38028050; departamento 38028; localidad censal 38028050.
-- Localidad 2202: Georef 38098040; departamento 38098; localidad censal 38098040.
-- Localidad 2203: Georef 38007100; departamento 38007; localidad censal 38007100.
-- Localidad 2204: Georef 38028060; departamento 38028; localidad censal 38028060.
-- Localidad 2205: Georef 38105050; departamento 38105; localidad censal 38105050.
-- Localidad 2206: Georef 38105060; departamento 38105; localidad censal 38105060.
-- Localidad 2207: Georef 38070070; departamento 38070; localidad censal 38070070.
-- Localidad 2208: Georef 38098050; departamento 38098; localidad censal 38098050.
-- Localidad 2209: Georef 3809401002; departamento 38094; localidad censal 38094010.
-- Localidad 2210: Georef 38021070; departamento 38021; localidad censal 38021070.
-- Localidad 2211: Georef 38112080; departamento 38112; localidad censal 38112080.
-- Localidad 2212: Georef 38112090; departamento 38112; localidad censal 38112090.
-- Localidad 2213: Georef 38077110; departamento 38077; localidad censal 38077110.
-- Localidad 2214: Georef 38035110; departamento 38035; localidad censal 38035110.
-- Localidad 2215: Georef 42112020; departamento 42112; localidad censal 42112020.
-- Localidad 2216: Georef 42077010; departamento 42077; localidad censal 42077010.
-- Localidad 2217: Georef 42133010; departamento 42133; localidad censal 42133010.
-- Localidad 2218: Georef 42105010; departamento 42105; localidad censal 42105010.
-- Localidad 2219: Georef 42063010; departamento 42063; localidad censal 42063010.
-- Localidad 2220: Georef 42070010; departamento 42070; localidad censal 42070010.
-- Localidad 2221: Georef 42133020; departamento 42133; localidad censal 42133020.
-- Localidad 2222: Georef 42021010; departamento 42021; localidad censal 42021010.
-- Localidad 2223: Georef 42014010; departamento 42014; localidad censal 42014010.
-- Localidad 2224: Georef 42147010; departamento 42147; localidad censal 42147010.
-- Localidad 2225: Georef 42154010; departamento 42154; localidad censal 42154010.
-- Localidad 2226: Georef 42056010; departamento 42056; localidad censal 42056010.
-- Localidad 2227: Georef 42077020; departamento 42077; localidad censal 42077020.
-- Localidad 2228: Georef 42140005; departamento 42140; localidad censal 42140005.
-- Localidad 2229: Georef 42126010; departamento 42126; localidad censal 42126010.
-- Localidad 2230: Georef 42098010; departamento 42098; localidad censal 42098010.
-- Localidad 2231: Georef 42112005; departamento 42112; localidad censal 42112005.
-- Localidad 2232: Georef 42028010; departamento 42028; localidad censal 42028010.
-- Localidad 2233: Georef 42056020; departamento 42056; localidad censal 42056020.
-- Localidad 2234: Georef 42154020; departamento 42154; localidad censal 42154020.
-- Localidad 2235: Georef 42119010; departamento 42119; localidad censal 42119010.
-- Localidad 2236: Georef 42119020; departamento 42119; localidad censal 42119020.
-- Localidad 2237: Georef 42154030; departamento 42154; localidad censal 42154030.
-- Localidad 2238: Georef 42035010; departamento 42035; localidad censal 42035010.
-- Localidad 2239: Georef 42056030; departamento 42056; localidad censal 42056030.
-- Localidad 2240: Georef 42084010; departamento 42084; localidad censal 42084010.
-- Localidad 2241: Georef 42133030; departamento 42133; localidad censal 42133030.
-- Localidad 2242: Georef 42007010; departamento 42007; localidad censal 42007010.
-- Localidad 2243: Georef 42105020; departamento 42105; localidad censal 42105020.
-- Localidad 2244: Georef 42035020; departamento 42035; localidad censal 42035020.
-- Localidad 2245: Georef 42133040; departamento 42133; localidad censal 42133040.
-- Localidad 2246: Georef 42133050; departamento 42133; localidad censal 42133050.
-- Localidad 2247: Georef 42154040; departamento 42154; localidad censal 42154040.
-- Localidad 2248: Georef 42070020; departamento 42070; localidad censal 42070020.
-- Localidad 2249: Georef 42105030; departamento 42105; localidad censal 42105030.
-- Localidad 2250: Georef 42077030; departamento 42077; localidad censal 42077030.
-- Localidad 2251: Georef 42042010; departamento 42042; localidad censal 42042010.
-- Localidad 2252: Georef 42070030; departamento 42070; localidad censal 42070030.
-- Localidad 2253: Georef 42077040; departamento 42077; localidad censal 42077040.
-- Localidad 2254: Georef 42126020; departamento 42126; localidad censal 42126020.
-- Localidad 2255: Georef 42133060; departamento 42133; localidad censal 42133060.
-- Localidad 2256: Georef 42056040; departamento 42056; localidad censal 42056040.
-- Localidad 2257: Georef 42077050; departamento 42077; localidad censal 42077050.
-- Localidad 2258: Georef 42014020; departamento 42014; localidad censal 42014020.
-- Localidad 2259: Georef 42028020; departamento 42028; localidad censal 42028020.
-- Localidad 2260: Georef 42063020; departamento 42063; localidad censal 42063020.
-- Localidad 2261: Georef 42126030; departamento 42126; localidad censal 42126030.
-- Localidad 2262: Georef 42091010; departamento 42091; localidad censal 42091010.
-- Localidad 2263: Georef 42091020; departamento 42091; localidad censal 42091020.
-- Localidad 2264: Georef 42028030; departamento 42028; localidad censal 42028030.
-- Localidad 2265: Georef 42098020; departamento 42098; localidad censal 42098020.
-- Localidad 2266: Georef 42098030; departamento 42098; localidad censal 42098030.
-- Localidad 2267: Georef 42007020; departamento 42007; localidad censal 42007020.
-- Localidad 2268: Georef 42035030; departamento 42035; localidad censal 42035030.
-- Localidad 2269: Georef 42147020; departamento 42147; localidad censal 42147020.
-- Localidad 2270: Georef 42119030; departamento 42119; localidad censal 42119030.
-- Localidad 2271: Georef 42007030; departamento 42007; localidad censal 42007030.
-- Localidad 2272: Georef 42035040; departamento 42035; localidad censal 42035040.
-- Localidad 2273: Georef 42140010; departamento 42140; localidad censal 42140010.
-- Localidad 2274: Georef 42133070; departamento 42133; localidad censal 42133070.
-- Localidad 2275: Georef 42126040; departamento 42126; localidad censal 42126040.
-- Localidad 2276: Georef 42070040; departamento 42070; localidad censal 42070040.
-- Localidad 2277: Georef 42126050; departamento 42126; localidad censal 42126050.
-- Localidad 2278: Georef 42042020; departamento 42042; localidad censal 42042020.
-- Localidad 2279: Georef 42112010; departamento 42112; localidad censal 42112010.
-- Localidad 2280: Georef 42154050; departamento 42154; localidad censal 42154050.
-- Localidad 2281: Georef 42119040; departamento 42119; localidad censal 42119040.
-- Localidad 2282: Georef 42126060; departamento 42126; localidad censal 42126060.
-- Localidad 2283: Georef 42126070; departamento 42126; localidad censal 42126070.
-- Localidad 2284: Georef 42133080; departamento 42133; localidad censal 42133080.
-- Localidad 2285: Georef 42119050; departamento 42119; localidad censal 42119050.
-- Localidad 2286: Georef 42007040; departamento 42007; localidad censal 42007040.
-- Localidad 2287: Georef 42035050; departamento 42035; localidad censal 42035050.
-- Localidad 2288: Georef 42049010; departamento 42049; localidad censal 42049010.
-- Localidad 2289: Georef 42021020; departamento 42021; localidad censal 42021020.
-- Localidad 2290: Georef 42070050; departamento 42070; localidad censal 42070050.
-- Localidad 2291: Georef 42056050; departamento 42056; localidad censal 42056050.
-- Localidad 2292: Georef 42105040; departamento 42105; localidad censal 42105040.
-- Localidad 2293: Georef 42098040; departamento 42098; localidad censal 42098040.
-- Localidad 2294: Georef 42140020; departamento 42140; localidad censal 42140020.
-- Localidad 2295: Georef 42007050; departamento 42007; localidad censal 42007050.
-- Localidad 2296: Georef 42105050; departamento 42105; localidad censal 42105050.
-- Localidad 2297: Georef 42147030; departamento 42147; localidad censal 42147030.
-- Localidad 2298: Georef 42154060; departamento 42154; localidad censal 42154060.
-- Localidad 2299: Georef 42028040; departamento 42028; localidad censal 42028040.
-- Localidad 2300: Georef 42056060; departamento 42056; localidad censal 42056060.
-- Localidad 2301: Georef 42098050; departamento 42098; localidad censal 42098050.
-- Localidad 2302: Georef 42119060; departamento 42119; localidad censal 42119060.
-- Localidad 2303: Georef 42035060; departamento 42035; localidad censal 42035060.
-- Localidad 2304: Georef 46028010; departamento 46028; localidad censal 46028010.
-- Localidad 2305: Georef 46007010; departamento 46007; localidad censal 46007010.
-- Localidad 2306: Georef 4611901001; departamento 46119; localidad censal 46119010.
-- Localidad 2307: Georef 46049010; departamento 46049; localidad censal 46049010.
-- Localidad 2308: Georef 46105010; departamento 46105; localidad censal 46105010.
-- Localidad 2309: Georef 46084010; departamento 46084; localidad censal 46084010.
-- Localidad 2310: Georef 46021010; departamento 46021; localidad censal 46021010.
-- Localidad 2311: Georef 4611901002; departamento 46119; localidad censal 46119010.
-- Localidad 2312: Georef 4611901003; departamento 46119; localidad censal 46119010.
-- Localidad 2313: Georef 4604201001; departamento 46042; localidad censal 46042010.
-- Localidad 2314: Georef 46049020; departamento 46049; localidad censal 46049020.
-- Localidad 2315: Georef 46021020; departamento 46021; localidad censal 46021020.
-- Localidad 2316: Georef 46021030; departamento 46021; localidad censal 46021030.
-- Localidad 2317: Georef 46049030; departamento 46049; localidad censal 46049030.
-- Localidad 2318: Georef 46049040; departamento 46049; localidad censal 46049040.
-- Localidad 2319: Georef 46007030; departamento 46007; localidad censal 46007030.
-- Localidad 2320: Georef 4602805001; departamento 46028; localidad censal 46028050.
-- Localidad 2321: Georef 46049050; departamento 46049; localidad censal 46049050.
-- Localidad 2322: Georef 46063010; departamento 46063; localidad censal 46063010.
-- Localidad 2323: Georef 46035010; departamento 46035; localidad censal 46035010.
-- Localidad 2324: Georef 46063020; departamento 46063; localidad censal 46063020.
-- Localidad 2325: Georef 46049060; departamento 46049; localidad censal 46049060.
-- Localidad 2326: Georef 4611901004; departamento 46119; localidad censal 46119010.
-- Localidad 2327: Georef 46112010; departamento 46112; localidad censal 46112010.
-- Localidad 2328: Georef 46042010; departamento 46042; localidad censal 46042010.
-- Localidad 2329: Georef 46021040; departamento 46021; localidad censal 46021040.
-- Localidad 2330: Georef 46042020; departamento 46042; localidad censal 46042020.
-- Localidad 2331: Georef 46042040; departamento 46042; localidad censal 46042040.
-- Localidad 2332: Georef 46084020; departamento 46084; localidad censal 46084020.
-- Localidad 2333: Georef 46042050; departamento 46042; localidad censal 46042050.
-- Localidad 2334: Georef 4611901005; departamento 46119; localidad censal 46119010.
-- Localidad 2335: Georef 46112020; departamento 46112; localidad censal 46112020.
-- Localidad 2336: Georef 46007040; departamento 46007; localidad censal 46007040.
-- Localidad 2337: Georef 46049070; departamento 46049; localidad censal 46049070.
-- Localidad 2338: Georef 46042060; departamento 46042; localidad censal 46042060.
-- Localidad 2339: Georef 46028020; departamento 46028; localidad censal 46028020.
-- Localidad 2340: Georef 46098010; departamento 46098; localidad censal 46098010.
-- Localidad 2341: Georef 46049080; departamento 46049; localidad censal 46049080.
-- Localidad 2342: Georef 4604201003; departamento 46042; localidad censal 46042010.
-- Localidad 2343: Georef 46014010; departamento 46014; localidad censal 46014010.
-- Localidad 2344: Georef 4611901006; departamento 46119; localidad censal 46119010.
-- Localidad 2345: Georef 46063030; departamento 46063; localidad censal 46063030.
-- Localidad 2346: Georef 46021050; departamento 46021; localidad censal 46021050.
-- Localidad 2347: Georef 46028030; departamento 46028; localidad censal 46028030.
-- Localidad 2348: Georef 4611901007; departamento 46119; localidad censal 46119010.
-- Localidad 2349: Georef 4604201004; departamento 46042; localidad censal 46042010.
-- Localidad 2350: Georef 4600701002; departamento 46007; localidad censal 46007010.
-- Localidad 2351: Georef 46070010; departamento 46070; localidad censal 46070010.
-- Localidad 2352: Georef 46042070; departamento 46042; localidad censal 46042070.
-- Localidad 2353: Georef 46084030; departamento 46084; localidad censal 46084030.
-- Localidad 2354: Georef 46042080; departamento 46042; localidad censal 46042080.
-- Localidad 2355: Georef 46070020; departamento 46070; localidad censal 46070020.
-- Localidad 2356: Georef 46042090; departamento 46042; localidad censal 46042090.
-- Localidad 2357: Georef 46084040; departamento 46084; localidad censal 46084040.
-- Localidad 2358: Georef 46063040; departamento 46063; localidad censal 46063040.
-- Localidad 2359: Georef 46028040; departamento 46028; localidad censal 46028040.
-- Localidad 2360: Georef 46105020; departamento 46105; localidad censal 46105020.
-- Localidad 2361: Georef 46021060; departamento 46021; localidad censal 46021060.
-- Localidad 2362: Georef 46049090; departamento 46049; localidad censal 46049090.
-- Localidad 2363: Georef 46049100; departamento 46049; localidad censal 46049100.
-- Localidad 2364: Georef 46035020; departamento 46035; localidad censal 46035020.
-- Localidad 2365: Georef 46070030; departamento 46070; localidad censal 46070030.
-- Localidad 2366: Georef 46056010; departamento 46056; localidad censal 46056010.
-- Localidad 2367: Georef 4611901008; departamento 46119; localidad censal 46119010.
-- Localidad 2368: Georef 46119010; departamento 46119; localidad censal 46119010.
-- Localidad 2369: Georef 4600701003; departamento 46007; localidad censal 46007010.
-- Localidad 2370: Georef 46070040; departamento 46070; localidad censal 46070040.
-- Localidad 2371: Georef 4611901009; departamento 46119; localidad censal 46119010.
-- Localidad 2372: Georef 4604201005; departamento 46042; localidad censal 46042010.
-- Localidad 2373: Georef 46042100; departamento 46042; localidad censal 46042100.
-- Localidad 2374: Georef 46021070; departamento 46021; localidad censal 46021070.
-- Localidad 2375: Georef 46042120; departamento 46042; localidad censal 46042120.
-- Localidad 2376: Georef 4602802002; departamento 46028; localidad censal 46028020.
-- Localidad 2377: Georef 46049110; departamento 46049; localidad censal 46049110.
-- Localidad 2378: Georef 46042110; departamento 46042; localidad censal 46042110.
-- Localidad 2379: Georef 46084050; departamento 46084; localidad censal 46084050.
-- Localidad 2380: Georef 46021080; departamento 46021; localidad censal 46021080.
-- Localidad 2381: Georef 46049120; departamento 46049; localidad censal 46049120.
-- Localidad 2382: Georef 4611901010; departamento 46119; localidad censal 46119010.
-- Localidad 2383: Georef 4611901011; departamento 46119; localidad censal 46119010.
-- Localidad 2384: Georef 46056020; departamento 46056; localidad censal 46056020.
-- Localidad 2385: Georef 46007045; departamento 46007; localidad censal 46007045.
-- Localidad 2386: Georef 46042130; departamento 46042; localidad censal 46042130.
-- Localidad 2387: Georef 4611901012; departamento 46119; localidad censal 46119010.
-- Localidad 2388: Georef 46091010; departamento 46091; localidad censal 46091010.
-- Localidad 2389: Georef 46042140; departamento 46042; localidad censal 46042140.
-- Localidad 2390: Georef 46077010; departamento 46077; localidad censal 46077010.
-- Localidad 2391: Georef 46007050; departamento 46007; localidad censal 46007050.
-- Localidad 2392: Georef 46098020; departamento 46098; localidad censal 46098020.
-- Localidad 2393: Georef 46126010; departamento 46126; localidad censal 46126010.
-- Localidad 2394: Georef 46028050; departamento 46028; localidad censal 46028050.
-- Localidad 2395: Georef 5000701010; departamento 50007; localidad censal 50007010.
-- Localidad 2396: Georef 5000701011; departamento 50007; localidad censal 50007010.
-- Localidad 2397: Georef 5000701001; departamento 50007; localidad censal 50007010.
-- Localidad 2398: Georef 50105220; departamento 50105; localidad censal 50105220.
-- Localidad 2399: Georef 5000701002; departamento 50007; localidad censal 50007010.
-- Localidad 2400: Georef 50056150; departamento 50056; localidad censal 50056150.
-- Localidad 2401: Georef 5000701003; departamento 50007; localidad censal 50007010.
-- Localidad 2402: Georef 5000701004; departamento 50007; localidad censal 50007010.
-- Localidad 2403: Georef 5000701005; departamento 50007; localidad censal 50007010.
-- Localidad 2404: Georef 5000701006; departamento 50007; localidad censal 50007010.
-- Localidad 2405: Georef 5000701007; departamento 50007; localidad censal 50007010.
-- Localidad 2406: Georef 5000701008; departamento 50007; localidad censal 50007010.
-- Localidad 2407: Georef 5000701009; departamento 50007; localidad censal 50007010.
-- Localidad 2408: Georef 50063010; departamento 50063; localidad censal 50063010.
-- Localidad 2409: Georef 50077010; departamento 50077; localidad censal 50077010.
-- Localidad 2410: Georef 50098020; departamento 50098; localidad censal 50098020.
-- Localidad 2411: Georef 50084010; departamento 50084; localidad censal 50084010.
-- Localidad 2412: Georef 50070010; departamento 50070; localidad censal 50070010.
-- Localidad 2413: Georef 50112010; departamento 50112; localidad censal 50112010.
-- Localidad 2414: Georef 50056010; departamento 50056; localidad censal 50056010.
-- Localidad 2415: Georef 50126010; departamento 50126; localidad censal 50126010.
-- Localidad 2416: Georef 50091005; departamento 50091; localidad censal 50091005.
-- Localidad 2417: Georef 50098030; departamento 50098; localidad censal 50098030.
-- Localidad 2418: Georef 50084020; departamento 50084; localidad censal 50084020.
-- Localidad 2419: Georef 50091010; departamento 50091; localidad censal 50091010.
-- Localidad 2420: Georef 50105020; departamento 50105; localidad censal 50105020.
-- Localidad 2421: Georef 50098040; departamento 50098; localidad censal 50098040.
-- Localidad 2422: Georef 50105030; departamento 50105; localidad censal 50105030.
-- Localidad 2423: Georef 50105040; departamento 50105; localidad censal 50105040.
-- Localidad 2424: Georef 50070020; departamento 50070; localidad censal 50070020.
-- Localidad 2425: Georef 50056020; departamento 50056; localidad censal 50056020.
-- Localidad 2426: Georef 50098045; departamento 50098; localidad censal 50098045.
-- Localidad 2427: Georef 50056030; departamento 50056; localidad censal 50056030.
-- Localidad 2428: Georef 50056040; departamento 50056; localidad censal 50056040.
-- Localidad 2429: Georef 50056050; departamento 50056; localidad censal 50056050.
-- Localidad 2430: Georef 50098050; departamento 50098; localidad censal 50098050.
-- Localidad 2431: Georef 50056060; departamento 50056; localidad censal 50056060.
-- Localidad 2432: Georef 50056070; departamento 50056; localidad censal 50056070.
-- Localidad 2433: Georef 50112020; departamento 50112; localidad censal 50112020.
-- Localidad 2434: Georef 50112030; departamento 50112; localidad censal 50112030.
-- Localidad 2435: Georef 50098055; departamento 50098; localidad censal 50098055.
-- Localidad 2436: Georef 50063020; departamento 50063; localidad censal 50063020.
-- Localidad 2437: Georef 50084030; departamento 50084; localidad censal 50084030.
-- Localidad 2438: Georef 50119010; departamento 50119; localidad censal 50119010.
-- Localidad 2439: Georef 50056075; departamento 50056; localidad censal 50056075.
-- Localidad 2440: Georef 5002802001; departamento 50028; localidad censal 50028020.
-- Localidad 2441: Georef 50014010; departamento 50014; localidad censal 50014010.
-- Localidad 2442: Georef 5002802002; departamento 50028; localidad censal 50028020.
-- Localidad 2443: Georef 50063030; departamento 50063; localidad censal 50063030.
-- Localidad 2444: Georef 50119020; departamento 50119; localidad censal 50119020.
-- Localidad 2445: Georef 5004905001; departamento 50049; localidad censal 50049050.
-- Localidad 2446: Georef 5002802003; departamento 50028; localidad censal 50028020.
-- Localidad 2447: Georef 50105050; departamento 50105; localidad censal 50105050.
-- Localidad 2448: Georef 50014020; departamento 50014; localidad censal 50014020.
-- Localidad 2449: Georef 5006309001; departamento 50063; localidad censal 50063090.
-- Localidad 2450: Georef 5006309002; departamento 50063; localidad censal 50063090.
-- Localidad 2451: Georef 50098060; departamento 50098; localidad censal 50098060.
-- Localidad 2452: Georef 50091020; departamento 50091; localidad censal 50091020.
-- Localidad 2453: Georef 50098070; departamento 50098; localidad censal 50098070.
-- Localidad 2454: Georef 5002101001; departamento 50021; localidad censal 50021010.
-- Localidad 2455: Georef 5004905002; departamento 50049; localidad censal 50049050.
-- Localidad 2456: Georef 5007006001; departamento 50070; localidad censal 50070060.
-- Localidad 2457: Georef 5006309003; departamento 50063; localidad censal 50063090.
-- Localidad 2458: Georef 50119030; departamento 50119; localidad censal 50119030.
-- Localidad 2459: Georef 50028010; departamento 50028; localidad censal 50028010.
-- Localidad 2460: Georef 5007006002; departamento 50070; localidad censal 50070060.
-- Localidad 2461: Georef 50126020; departamento 50126; localidad censal 50126020.
-- Localidad 2462: Georef 50056080; departamento 50056; localidad censal 50056080.
-- Localidad 2463: Georef 50063040; departamento 50063; localidad censal 50063040.
-- Localidad 2464: Georef 50070030; departamento 50070; localidad censal 50070030.
-- Localidad 2465: Georef 50105060; departamento 50105; localidad censal 50105060.
-- Localidad 2466: Georef 5010521001; departamento 50105; localidad censal 50105210.
-- Localidad 2467: Georef 5008407001; departamento 50084; localidad censal 50084070.
-- Localidad 2468: Georef 50042010; departamento 50042; localidad censal 50042010.
-- Localidad 2469: Georef 5002802004; departamento 50028; localidad censal 50028020.
-- Localidad 2470: Georef 5004905003; departamento 50049; localidad censal 50049050.
-- Localidad 2471: Georef 5004905004; departamento 50049; localidad censal 50049050.
-- Localidad 2472: Georef 50063050; departamento 50063; localidad censal 50063050.
-- Localidad 2473: Georef 5004905005; departamento 50049; localidad censal 50049050.
-- Localidad 2474: Georef 50098073; departamento 50098; localidad censal 50098073.
-- Localidad 2475: Georef 50119040; departamento 50119; localidad censal 50119040.
-- Localidad 2476: Georef 50084040; departamento 50084; localidad censal 50084040.
-- Localidad 2477: Georef 50105070; departamento 50105; localidad censal 50105070.
-- Localidad 2478: Georef 50056090; departamento 50056; localidad censal 50056090.
-- Localidad 2479: Georef 50070040; departamento 50070; localidad censal 50070040.
-- Localidad 2480: Georef 50126030; departamento 50126; localidad censal 50126030.
-- Localidad 2481: Georef 5004905007; departamento 50049; localidad censal 50049050.
-- Localidad 2482: Georef 50098077; departamento 50098; localidad censal 50098077.
-- Localidad 2483: Georef 5004905008; departamento 50049; localidad censal 50049050.
-- Localidad 2484: Georef 50063060; departamento 50063; localidad censal 50063060.
-- Localidad 2485: Georef 5002802005; departamento 50028; localidad censal 50028020.
-- Localidad 2486: Georef 50105080; departamento 50105; localidad censal 50105080.
-- Localidad 2487: Georef 50105090; departamento 50105; localidad censal 50105090.
-- Localidad 2488: Georef 50056100; departamento 50056; localidad censal 50056100.
-- Localidad 2489: Georef 50126035; departamento 50126; localidad censal 50126035.
-- Localidad 2490: Georef 5004905009; departamento 50049; localidad censal 50049050.
-- Localidad 2491: Georef 50070050; departamento 50070; localidad censal 50070050.
-- Localidad 2492: Georef 50014030; departamento 50014; localidad censal 50014030.
-- Localidad 2493: Georef 5002802006; departamento 50028; localidad censal 50028020.
-- Localidad 2494: Georef 5007006003; departamento 50070; localidad censal 50070060.
-- Localidad 2495: Georef 5002101002; departamento 50021; localidad censal 50021010.
-- Localidad 2496: Georef 50021010; departamento 50021; localidad censal 50021010.
-- Localidad 2497: Georef 50105100; departamento 50105; localidad censal 50105100.
-- Localidad 2498: Georef 50028020; departamento 50028; localidad censal 50028020.
-- Localidad 2499: Georef 50035010; departamento 50035; localidad censal 50035010.
-- Localidad 2500: Georef 50056110; departamento 50056; localidad censal 50056110.
-- Localidad 2501: Georef 50105110; departamento 50105; localidad censal 50105110.
-- Localidad 2502: Georef 5002802007; departamento 50028; localidad censal 50028020.
-- Localidad 2503: Georef 50049030; departamento 50049; localidad censal 50049030.
-- Localidad 2504: Georef 50056120; departamento 50056; localidad censal 50056120.
-- Localidad 2505: Georef 50056130; departamento 50056; localidad censal 50056130.
-- Localidad 2506: Georef 50035020; departamento 50035; localidad censal 50035020.
-- Localidad 2507: Georef 50126040; departamento 50126; localidad censal 50126040.
-- Localidad 2508: Georef 50084050; departamento 50084; localidad censal 50084050.
-- Localidad 2509: Georef 5004905010; departamento 50049; localidad censal 50049050.
-- Localidad 2510: Georef 50035030; departamento 50035; localidad censal 50035030.
-- Localidad 2511: Georef 50091040; departamento 50091; localidad censal 50091040.
-- Localidad 2512: Georef 50112040; departamento 50112; localidad censal 50112040.
-- Localidad 2513: Georef 50084060; departamento 50084; localidad censal 50084060.
-- Localidad 2514: Georef 50084070; departamento 50084; localidad censal 50084070.
-- Localidad 2515: Georef 50084080; departamento 50084; localidad censal 50084080.
-- Localidad 2516: Georef 50105120; departamento 50105; localidad censal 50105120.
-- Localidad 2517: Georef 50042020; departamento 50042; localidad censal 50042020.
-- Localidad 2518: Georef 50028030; departamento 50028; localidad censal 50028030.
-- Localidad 2519: Georef 5006309004; departamento 50063; localidad censal 50063090.
-- Localidad 2520: Georef 5002802008; departamento 50028; localidad censal 50028020.
-- Localidad 2521: Georef 50112050; departamento 50112; localidad censal 50112050.
-- Localidad 2522: Georef 50063070; departamento 50063; localidad censal 50063070.
-- Localidad 2523: Georef 50049040; departamento 50049; localidad censal 50049040.
-- Localidad 2524: Georef 50049050; departamento 50049; localidad censal 50049050.
-- Localidad 2525: Georef 50077030; departamento 50077; localidad censal 50077030.
-- Localidad 2526: Georef 50105130; departamento 50105; localidad censal 50105130.
-- Localidad 2527: Georef 5002101003; departamento 50021; localidad censal 50021010.
-- Localidad 2528: Georef 50063080; departamento 50063; localidad censal 50063080.
-- Localidad 2529: Georef 50056140; departamento 50056; localidad censal 50056140.
-- Localidad 2530: Georef 50084090; departamento 50084; localidad censal 50084090.
-- Localidad 2531: Georef 50035040; departamento 50035; localidad censal 50035040.
-- Localidad 2532: Georef 50084100; departamento 50084; localidad censal 50084100.
-- Localidad 2533: Georef 50014040; departamento 50014; localidad censal 50014040.
-- Localidad 2534: Georef 50028040; departamento 50028; localidad censal 50028040.
-- Localidad 2535: Georef 50049060; departamento 50049; localidad censal 50049060.
-- Localidad 2536: Georef 50119050; departamento 50119; localidad censal 50119050.
-- Localidad 2537: Georef 50063090; departamento 50063; localidad censal 50063090.
-- Localidad 2538: Georef 5007006004; departamento 50070; localidad censal 50070060.
-- Localidad 2539: Georef 50070060; departamento 50070; localidad censal 50070060.
-- Localidad 2540: Georef 50077040; departamento 50077; localidad censal 50077040.
-- Localidad 2541: Georef 5006309005; departamento 50063; localidad censal 50063090.
-- Localidad 2542: Georef 50035050; departamento 50035; localidad censal 50035050.
-- Localidad 2543: Georef 50084110; departamento 50084; localidad censal 50084110.
-- Localidad 2544: Georef 50007010; departamento 50007; localidad censal 50007010.
-- Localidad 2545: Georef 50105150; departamento 50105; localidad censal 50105150.
-- Localidad 2546: Georef 50098080; departamento 50098; localidad censal 50098080.
-- Localidad 2547: Georef 50084120; departamento 50084; localidad censal 50084120.
-- Localidad 2548: Georef 50098090; departamento 50098; localidad censal 50098090.
-- Localidad 2549: Georef 5002802009; departamento 50028; localidad censal 50028020.
-- Localidad 2550: Georef 5009810001; departamento 50098; localidad censal 50098100.
-- Localidad 2551: Georef 5004905011; departamento 50049; localidad censal 50049050.
-- Localidad 2552: Georef 50091050; departamento 50091; localidad censal 50091050.
-- Localidad 2553: Georef 5002802010; departamento 50028; localidad censal 50028020.
-- Localidad 2554: Georef 50063100; departamento 50063; localidad censal 50063100.
-- Localidad 2555: Georef 50035060; departamento 50035; localidad censal 50035060.
-- Localidad 2556: Georef 50105160; departamento 50105; localidad censal 50105160.
-- Localidad 2557: Georef 50049080; departamento 50049; localidad censal 50049080.
-- Localidad 2558: Georef 50063110; departamento 50063; localidad censal 50063110.
-- Localidad 2559: Georef 5002101004; departamento 50021; localidad censal 50021010.
-- Localidad 2560: Georef 50028050; departamento 50028; localidad censal 50028050.
-- Localidad 2561: Georef 50049090; departamento 50049; localidad censal 50049090.
-- Localidad 2562: Georef 50049100; departamento 50049; localidad censal 50049100.
-- Localidad 2563: Georef 50105170; departamento 50105; localidad censal 50105170.
-- Localidad 2564: Georef 50105180; departamento 50105; localidad censal 50105180.
-- Localidad 2565: Georef 50105190; departamento 50105; localidad censal 50105190.
-- Localidad 2566: Georef 50084130; departamento 50084; localidad censal 50084130.
-- Localidad 2567: Georef 50084140; departamento 50084; localidad censal 50084140.
-- Localidad 2568: Georef 5002802011; departamento 50028; localidad censal 50028020.
-- Localidad 2569: Georef 50070070; departamento 50070; localidad censal 50070070.
-- Localidad 2570: Georef 50035070; departamento 50035; localidad censal 50035070.
-- Localidad 2571: Georef 50070080; departamento 50070; localidad censal 50070080.
-- Localidad 2572: Georef 50105200; departamento 50105; localidad censal 50105200.
-- Localidad 2573: Georef 50091060; departamento 50091; localidad censal 50091060.
-- Localidad 2574: Georef 5002101005; departamento 50021; localidad censal 50021010.
-- Localidad 2575: Georef 5002802012; departamento 50028; localidad censal 50028020.
-- Localidad 2576: Georef 5002802013; departamento 50028; localidad censal 50028020.
-- Localidad 2577: Georef 50126050; departamento 50126; localidad censal 50126050.
-- Localidad 2578: Georef 50098100; departamento 50098; localidad censal 50098100.
-- Localidad 2579: Georef 50105210; departamento 50105; localidad censal 50105210.
-- Localidad 2580: Georef 50070090; departamento 50070; localidad censal 50070090.
-- Localidad 2581: Georef 50084150; departamento 50084; localidad censal 50084150.
-- Localidad 2582: Georef 50112060; departamento 50112; localidad censal 50112060.
-- Localidad 2583: Georef 5004905012; departamento 50049; localidad censal 50049050.
-- Localidad 2584: Georef 50098110; departamento 50098; localidad censal 50098110.
-- Localidad 2585: Georef 50119060; departamento 50119; localidad censal 50119060.
-- Localidad 2586: Georef 50126060; departamento 50126; localidad censal 50126060.
-- Localidad 2587: Georef 50063120; departamento 50063; localidad censal 50063120.
-- Localidad 2588: Georef 50049110; departamento 50049; localidad censal 50049110.
-- Localidad 2589: Georef 50042030; departamento 50042; localidad censal 50042030.
-- Localidad 2590: Georef 50105230; departamento 50105; localidad censal 50105230.
-- Localidad 2591: Georef 50105240; departamento 50105; localidad censal 50105240.
-- Localidad 2592: Georef 5012606002; departamento 50126; localidad censal 50126060.
-- Localidad 2593: Georef 5002802014; departamento 50028; localidad censal 50028020.
-- Localidad 2594: Georef 50056160; departamento 50056; localidad censal 50056160.
-- Localidad 2595: Georef 50119070; departamento 50119; localidad censal 50119070.
-- Localidad 2596: Georef 5006309006; departamento 50063; localidad censal 50063090.
-- Localidad 2597: Georef 54014060; departamento 54014; localidad censal 54014060.
-- Localidad 2598: Georef 54119060; departamento 54119; localidad censal 54119060.
-- Localidad 2599: Georef 54042050; departamento 54042; localidad censal 54042050.
-- Localidad 2600: Georef 54042040; departamento 54042; localidad censal 54042040.
-- Localidad 2601: Georef 54119010; departamento 54119; localidad censal 54119010.
-- Localidad 2602: Georef 54119020; departamento 54119; localidad censal 54119020.
-- Localidad 2603: Georef 54119025; departamento 54119; localidad censal 54119025.
-- Localidad 2604: Georef 54070010; departamento 54070; localidad censal 54070010.
-- Localidad 2605: Georef 54007010; departamento 54007; localidad censal 54007010.
-- Localidad 2606: Georef 54014010; departamento 54014; localidad censal 54014010.
-- Localidad 2607: Georef 54070020; departamento 54070; localidad censal 54070020.
-- Localidad 2608: Georef 54007020; departamento 54007; localidad censal 54007020.
-- Localidad 2609: Georef 54084007; departamento 54084; localidad censal 54084007.
-- Localidad 2610: Georef 54035010; departamento 54035; localidad censal 54035010.
-- Localidad 2611: Georef 54084003; departamento 54084; localidad censal 54084003.
-- Localidad 2612: Georef 54021005; departamento 54021; localidad censal 54021005.
-- Localidad 2613: Georef 54091013; departamento 54091; localidad censal 54091013.
-- Localidad 2614: Georef 54091017; departamento 54091; localidad censal 54091017.
-- Localidad 2615: Georef 54084005; departamento 54084; localidad censal 54084005.
-- Localidad 2616: Georef 54007025; departamento 54007; localidad censal 54007025.
-- Localidad 2617: Georef 54098005; departamento 54098; localidad censal 54098005.
-- Localidad 2618: Georef 54049020; departamento 54049; localidad censal 54049020.
-- Localidad 2619: Georef 54021010; departamento 54021; localidad censal 54021010.
-- Localidad 2620: Georef 54070030; departamento 54070; localidad censal 54070030.
-- Localidad 2621: Georef 54049025; departamento 54049; localidad censal 54049025.
-- Localidad 2622: Georef 54014020; departamento 54014; localidad censal 54014020.
-- Localidad 2623: Georef 54091020; departamento 54091; localidad censal 54091020.
-- Localidad 2624: Georef 54091030; departamento 54091; localidad censal 54091030.
-- Localidad 2625: Georef 54021020; departamento 54021; localidad censal 54021020.
-- Localidad 2626: Georef 54077010; departamento 54077; localidad censal 54077010.
-- Localidad 2627: Georef 54077015; departamento 54077; localidad censal 54077015.
-- Localidad 2628: Georef 54084010; departamento 54084; localidad censal 54084010.
-- Localidad 2629: Georef 54070040; departamento 54070; localidad censal 54070040.
-- Localidad 2630: Georef 54021030; departamento 54021; localidad censal 54021030.
-- Localidad 2631: Georef 54091010; departamento 54091; localidad censal 54091010.
-- Localidad 2632: Georef 54119030; departamento 54119; localidad censal 54119030.
-- Localidad 2633: Georef 54098010; departamento 54098; localidad censal 54098010.
-- Localidad 2634: Georef 54042010; departamento 54042; localidad censal 54042010.
-- Localidad 2635: Georef 54063040; departamento 54063; localidad censal 54063040.
-- Localidad 2636: Georef 54049010; departamento 54049; localidad censal 54049010.
-- Localidad 2637: Georef 54035020; departamento 54035; localidad censal 54035020.
-- Localidad 2638: Georef 54098020; departamento 54098; localidad censal 54098020.
-- Localidad 2639: Georef 54112010; departamento 54112; localidad censal 54112010.
-- Localidad 2640: Georef 54098030; departamento 54098; localidad censal 54098030.
-- Localidad 2641: Georef 54070050; departamento 54070; localidad censal 54070050.
-- Localidad 2642: Georef 54014030; departamento 54014; localidad censal 54014030.
-- Localidad 2643: Georef 5401403001; departamento 54014; localidad censal 54014030.
-- Localidad 2644: Georef 5401403002; departamento 54014; localidad censal 54014030.
-- Localidad 2645: Georef 54014050; departamento 54014; localidad censal 54014050.
-- Localidad 2646: Georef 54049030; departamento 54049; localidad censal 54049030.
-- Localidad 2647: Georef 54077020; departamento 54077; localidad censal 54077020.
-- Localidad 2648: Georef 54091040; departamento 54091; localidad censal 54091040.
-- Localidad 2649: Georef 54056010; departamento 54056; localidad censal 54056010.
-- Localidad 2650: Georef 54042020; departamento 54042; localidad censal 54042020.
-- Localidad 2651: Georef 54007030; departamento 54007; localidad censal 54007030.
-- Localidad 2652: Georef 54105010; departamento 54105; localidad censal 54105010.
-- Localidad 2653: Georef 54056020; departamento 54056; localidad censal 54056020.
-- Localidad 2654: Georef 54077030; departamento 54077; localidad censal 54077030.
-- Localidad 2655: Georef 54028010; departamento 54028; localidad censal 54028010.
-- Localidad 2656: Georef 54091050; departamento 54091; localidad censal 54091050.
-- Localidad 2657: Georef 54098040; departamento 54098; localidad censal 54098040.
-- Localidad 2658: Georef 54070060; departamento 54070; localidad censal 54070060.
-- Localidad 2659: Georef 54098050; departamento 54098; localidad censal 54098050.
-- Localidad 2660: Georef 54091060; departamento 54091; localidad censal 54091060.
-- Localidad 2661: Georef 54098060; departamento 54098; localidad censal 54098060.
-- Localidad 2662: Georef 54098070; departamento 54098; localidad censal 54098070.
-- Localidad 2663: Georef 54049040; departamento 54049; localidad censal 54049040.
-- Localidad 2664: Georef 54105020; departamento 54105; localidad censal 54105020.
-- Localidad 2665: Georef 54098080; departamento 54098; localidad censal 54098080.
-- Localidad 2666: Georef 54014055; departamento 54014; localidad censal 54014055.
-- Localidad 2667: Georef 54035030; departamento 54035; localidad censal 54035030.
-- Localidad 2668: Georef 54084020; departamento 54084; localidad censal 54084020.
-- Localidad 2669: Georef 54070070; departamento 54070; localidad censal 54070070.
-- Localidad 2670: Georef 54021040; departamento 54021; localidad censal 54021040.
-- Localidad 2671: Georef 54091070; departamento 54091; localidad censal 54091070.
-- Localidad 2672: Georef 54042030; departamento 54042; localidad censal 54042030.
-- Localidad 2673: Georef 54021050; departamento 54021; localidad censal 54021050.
-- Localidad 2674: Georef 54077040; departamento 54077; localidad censal 54077040.
-- Localidad 2675: Georef 54105030; departamento 54105; localidad censal 54105030.
-- Localidad 2676: Georef 54084030; departamento 54084; localidad censal 54084030.
-- Localidad 2677: Georef 54028020; departamento 54028; localidad censal 54028020.
-- Localidad 2678: Georef 54042035; departamento 54042; localidad censal 54042035.
-- Localidad 2679: Georef 54098090; departamento 54098; localidad censal 54098090.
-- Localidad 2680: Georef 54091080; departamento 54091; localidad censal 54091080.
-- Localidad 2681: Georef 54070080; departamento 54070; localidad censal 54070080.
-- Localidad 2682: Georef 54091090; departamento 54091; localidad censal 54091090.
-- Localidad 2683: Georef 54091105; departamento 54091; localidad censal 54091105.
-- Localidad 2684: Georef 54091100; departamento 54091; localidad censal 54091100.
-- Localidad 2685: Georef 54112020; departamento 54112; localidad censal 54112020.
-- Localidad 2686: Georef 54049043; departamento 54049; localidad censal 54049043.
-- Localidad 2687: Georef 54112030; departamento 54112; localidad censal 54112030.
-- Localidad 2688: Georef 54007040; departamento 54007; localidad censal 54007040.
-- Localidad 2689: Georef 54084040; departamento 54084; localidad censal 54084040.
-- Localidad 2690: Georef 54028030; departamento 54028; localidad censal 54028030.
-- Localidad 2691: Georef 54028040; departamento 54028; localidad censal 54028040.
-- Localidad 2692: Georef 54021060; departamento 54021; localidad censal 54021060.
-- Localidad 2693: Georef 54014070; departamento 54014; localidad censal 54014070.
-- Localidad 2694: Georef 54042055; departamento 54042; localidad censal 54042055.
-- Localidad 2695: Georef 54049045; departamento 54049; localidad censal 54049045.
-- Localidad 2696: Georef 54049047; departamento 54049; localidad censal 54049047.
-- Localidad 2697: Georef 54063010; departamento 54063; localidad censal 54063010.
-- Localidad 2698: Georef 54063030; departamento 54063; localidad censal 54063030.
-- Localidad 2699: Georef 54077050; departamento 54077; localidad censal 54077050.
-- Localidad 2700: Georef 54063020; departamento 54063; localidad censal 54063020.
-- Localidad 2701: Georef 54042060; departamento 54042; localidad censal 54042060.
-- Localidad 2702: Georef 54042070; departamento 54042; localidad censal 54042070.
-- Localidad 2703: Georef 54084050; departamento 54084; localidad censal 54084050.
-- Localidad 2704: Georef 54077060; departamento 54077; localidad censal 54077060.
-- Localidad 2705: Georef 54021070; departamento 54021; localidad censal 54021070.
-- Localidad 2706: Georef 54007050; departamento 54007; localidad censal 54007050.
-- Localidad 2707: Georef 54098100; departamento 54098; localidad censal 54098100.
-- Localidad 2708: Georef 54077070; departamento 54077; localidad censal 54077070.
-- Localidad 2709: Georef 54014080; departamento 54014; localidad censal 54014080.
-- Localidad 2710: Georef 54077080; departamento 54077; localidad censal 54077080.
-- Localidad 2711: Georef 54049050; departamento 54049; localidad censal 54049050.
-- Localidad 2712: Georef 54119040; departamento 54119; localidad censal 54119040.
-- Localidad 2713: Georef 54077090; departamento 54077; localidad censal 54077090.
-- Localidad 2714: Georef 54098110; departamento 54098; localidad censal 54098110.
-- Localidad 2715: Georef 54105040; departamento 54105; localidad censal 54105040.
-- Localidad 2716: Georef 54007060; departamento 54007; localidad censal 54007060.
-- Localidad 2717: Georef 54091110; departamento 54091; localidad censal 54091110.
-- Localidad 2718: Georef 54077100; departamento 54077; localidad censal 54077100.
-- Localidad 2719: Georef 54112040; departamento 54112; localidad censal 54112040.
-- Localidad 2720: Georef 54056030; departamento 54056; localidad censal 54056030.
-- Localidad 2721: Georef 54021080; departamento 54021; localidad censal 54021080.
-- Localidad 2722: Georef 54035040; departamento 54035; localidad censal 54035040.
-- Localidad 2723: Georef 54119050; departamento 54119; localidad censal 54119050.
-- Localidad 2724: Georef 54042080; departamento 54042; localidad censal 54042080.
-- Localidad 2725: Georef 54098120; departamento 54098; localidad censal 54098120.
-- Localidad 2726: Georef 54084060; departamento 54084; localidad censal 54084060.
-- Localidad 2727: Georef 54112050; departamento 54112; localidad censal 54112050.
-- Localidad 2728: Georef 54007070; departamento 54007; localidad censal 54007070.
-- Localidad 2729: Georef 54042090; departamento 54042; localidad censal 54042090.
-- Localidad 2730: Georef 54077110; departamento 54077; localidad censal 54077110.
-- Localidad 2731: Georef 54091120; departamento 54091; localidad censal 54091120.
-- Localidad 2732: Georef 54063035; departamento 54063; localidad censal 54063035.
-- Localidad 2733: Georef 54070090; departamento 54070; localidad censal 54070090.
-- Localidad 2734: Georef 54084070; departamento 54084; localidad censal 54084070.
-- Localidad 2735: Georef 54042100; departamento 54042; localidad censal 54042100.
-- Localidad 2736: Georef 54077120; departamento 54077; localidad censal 54077120.
-- Localidad 2737: Georef 58014005; departamento 58014; localidad censal 58014005.
-- Localidad 2738: Georef 58007010; departamento 58007; localidad censal 58007010.
-- Localidad 2739: Georef 58077010; departamento 58077; localidad censal 58077010.
-- Localidad 2740: Georef 58014010; departamento 58014; localidad censal 58014010.
-- Localidad 2741: Georef 58035010; departamento 58035; localidad censal 58035010.
-- Localidad 2742: Georef 58105010; departamento 58105; localidad censal 58105010.
-- Localidad 2743: Georef 58091010; departamento 58091; localidad censal 58091010.
-- Localidad 2744: Georef 58091020; departamento 58091; localidad censal 58091020.
-- Localidad 2745: Georef 58084010; departamento 58084; localidad censal 58084010.
-- Localidad 2746: Georef 58035030; departamento 58035; localidad censal 58035030.
-- Localidad 2747: Georef 58063010; departamento 58063; localidad censal 58063010.
-- Localidad 2748: Georef 58042010; departamento 58042; localidad censal 58042010.
-- Localidad 2749: Georef 58084020; departamento 58084; localidad censal 58084020.
-- Localidad 2750: Georef 5811202001; departamento 58112; localidad censal 58112020.
-- Localidad 2751: Georef 58035040; departamento 58035; localidad censal 58035040.
-- Localidad 2752: Georef 58084030; departamento 58084; localidad censal 58084030.
-- Localidad 2753: Georef 58084040; departamento 58084; localidad censal 58084040.
-- Localidad 2754: Georef 58098005; departamento 58098; localidad censal 58098005.
-- Localidad 2755: Georef 58077020; departamento 58077; localidad censal 58077020.
-- Localidad 2756: Georef 58049010; departamento 58049; localidad censal 58049010.
-- Localidad 2757: Georef 58105020; departamento 58105; localidad censal 58105020.
-- Localidad 2758: Georef 58021010; departamento 58021; localidad censal 58021010.
-- Localidad 2759: Georef 58105030; departamento 58105; localidad censal 58105030.
-- Localidad 2760: Georef 58077030; departamento 58077; localidad censal 58077030.
-- Localidad 2761: Georef 58063020; departamento 58063; localidad censal 58063020.
-- Localidad 2762: Georef 58112010; departamento 58112; localidad censal 58112010.
-- Localidad 2763: Georef 58077040; departamento 58077; localidad censal 58077040.
-- Localidad 2764: Georef 58077050; departamento 58077; localidad censal 58077050.
-- Localidad 2765: Georef 58035060; departamento 58035; localidad censal 58035060.
-- Localidad 2766: Georef 58112020; departamento 58112; localidad censal 58112020.
-- Localidad 2767: Georef 58007015; departamento 58007; localidad censal 58007015.
-- Localidad 2768: Georef 58035070; departamento 58035; localidad censal 58035070.
-- Localidad 2769: Georef 58091030; departamento 58091; localidad censal 58091030.
-- Localidad 2770: Georef 58098010; departamento 58098; localidad censal 58098010.
-- Localidad 2771: Georef 58098020; departamento 58098; localidad censal 58098020.
-- Localidad 2772: Georef 58028010; departamento 58028; localidad censal 58028010.
-- Localidad 2773: Georef 58035090; departamento 58035; localidad censal 58035090.
-- Localidad 2774: Georef 58035100; departamento 58035; localidad censal 58035100.
-- Localidad 2775: Georef 58105040; departamento 58105; localidad censal 58105040.
-- Localidad 2776: Georef 58112030; departamento 58112; localidad censal 58112030.
-- Localidad 2777: Georef 58091040; departamento 58091; localidad censal 58091040.
-- Localidad 2778: Georef 58056010; departamento 58056; localidad censal 58056010.
-- Localidad 2779: Georef 58014020; departamento 58014; localidad censal 58014020.
-- Localidad 2780: Georef 58028020; departamento 58028; localidad censal 58028020.
-- Localidad 2781: Georef 58035110; departamento 58035; localidad censal 58035110.
-- Localidad 2782: Georef 58084050; departamento 58084; localidad censal 58084050.
-- Localidad 2783: Georef 58042020; departamento 58042; localidad censal 58042020.
-- Localidad 2784: Georef 58077060; departamento 58077; localidad censal 58077060.
-- Localidad 2785: Georef 58042030; departamento 58042; localidad censal 58042030.
-- Localidad 2786: Georef 58077070; departamento 58077; localidad censal 58077070.
-- Localidad 2787: Georef 58035120; departamento 58035; localidad censal 58035120.
-- Localidad 2788: Georef 58070010; departamento 58070; localidad censal 58070010.
-- Localidad 2789: Georef 58056020; departamento 58056; localidad censal 58056020.
-- Localidad 2790: Georef 58007020; departamento 58007; localidad censal 58007020.
-- Localidad 2791: Georef 58070020; departamento 58070; localidad censal 58070020.
-- Localidad 2792: Georef 58035130; departamento 58035; localidad censal 58035130.
-- Localidad 2793: Georef 58035140; departamento 58035; localidad censal 58035140.
-- Localidad 2794: Georef 58112040; departamento 58112; localidad censal 58112040.
-- Localidad 2795: Georef 62084010; departamento 62084; localidad censal 62084010.
-- Localidad 2796: Georef 62091010; departamento 62091; localidad censal 62091010.
-- Localidad 2797: Georef 62035010; departamento 62035; localidad censal 62035010.
-- Localidad 2798: Georef 62042010; departamento 62042; localidad censal 62042010.
-- Localidad 2799: Georef 62084020; departamento 62084; localidad censal 62084020.
-- Localidad 2800: Georef 62084030; departamento 62084; localidad censal 62084030.
-- Localidad 2801: Georef 62007010; departamento 62007; localidad censal 62007010.
-- Localidad 2802: Georef 62042030; departamento 62042; localidad censal 62042030.
-- Localidad 2803: Georef 62042040; departamento 62042; localidad censal 62042040.
-- Localidad 2804: Georef 62042050; departamento 62042; localidad censal 62042050.
-- Localidad 2805: Georef 62042060; departamento 62042; localidad censal 62042060.
-- Localidad 2806: Georef 62042070; departamento 62042; localidad censal 62042070.
-- Localidad 2807: Georef 62042080; departamento 62042; localidad censal 62042080.
-- Localidad 2808: Georef 62028010; departamento 62028; localidad censal 62028010.
-- Localidad 2809: Georef 62042090; departamento 62042; localidad censal 62042090.
-- Localidad 2810: Georef 62042110; departamento 62042; localidad censal 62042110.
-- Localidad 2811: Georef 62042115; departamento 62042; localidad censal 62042115.
-- Localidad 2812: Georef 62042120; departamento 62042; localidad censal 62042120.
-- Localidad 2813: Georef 62042130; departamento 62042; localidad censal 62042130.
-- Localidad 2814: Georef 62042140; departamento 62042; localidad censal 62042140.
-- Localidad 2815: Georef 62042143; departamento 62042; localidad censal 62042143.
-- Localidad 2816: Georef 62063005; departamento 62063; localidad censal 62063005.
-- Localidad 2817: Georef 62042147; departamento 62042; localidad censal 62042147.
-- Localidad 2818: Georef 62042150; departamento 62042; localidad censal 62042150.
-- Localidad 2819: Georef 62042160; departamento 62042; localidad censal 62042160.
-- Localidad 2820: Georef 62042170; departamento 62042; localidad censal 62042170.
-- Localidad 2821: Georef 62042180; departamento 62042; localidad censal 62042180.
-- Localidad 2822: Georef 62042200; departamento 62042; localidad censal 62042200.
-- Localidad 2823: Georef 62042210; departamento 62042; localidad censal 62042210.
-- Localidad 2824: Georef 62042215; departamento 62042; localidad censal 62042215.
-- Localidad 2825: Georef 6204240001; departamento 62042; localidad censal 62042400.
-- Localidad 2826: Georef 62042245; departamento 62042; localidad censal 62042245.
-- Localidad 2827: Georef 62042250; departamento 62042; localidad censal 62042250.
-- Localidad 2828: Georef 62042260; departamento 62042; localidad censal 62042260.
-- Localidad 2829: Georef 62042265; departamento 62042; localidad censal 62042265.
-- Localidad 2830: Georef 62042280; departamento 62042; localidad censal 62042280.
-- Localidad 2831: Georef 62042297; departamento 62042; localidad censal 62042297.
-- Localidad 2832: Georef 6204245001; departamento 62042; localidad censal 62042450.
-- Localidad 2833: Georef 62028030; departamento 62028; localidad censal 62028030.
-- Localidad 2834: Georef 62042310; departamento 62042; localidad censal 62042310.
-- Localidad 2835: Georef 6204239001; departamento 62042; localidad censal 62042390.
-- Localidad 2836: Georef 62042335; departamento 62042; localidad censal 62042335.
-- Localidad 2837: Georef 62042340; departamento 62042; localidad censal 62042340.
-- Localidad 2838: Georef 62014010; departamento 62014; localidad censal 62014010.
-- Localidad 2839: Georef 62042350; departamento 62042; localidad censal 62042350.
-- Localidad 2840: Georef 62070005; departamento 62070; localidad censal 62070005.
-- Localidad 2841: Georef 62042360; departamento 62042; localidad censal 62042360.
-- Localidad 2842: Georef 62035020; departamento 62035; localidad censal 62035020.
-- Localidad 2843: Georef 62042370; departamento 62042; localidad censal 62042370.
-- Localidad 2844: Georef 62014020; departamento 62014; localidad censal 62014020.
-- Localidad 2845: Georef 62042380; departamento 62042; localidad censal 62042380.
-- Localidad 2846: Georef 62014030; departamento 62014; localidad censal 62014030.
-- Localidad 2847: Georef 62014040; departamento 62014; localidad censal 62014040.
-- Localidad 2848: Georef 62042390; departamento 62042; localidad censal 62042390.
-- Localidad 2849: Georef 62042400; departamento 62042; localidad censal 62042400.
-- Localidad 2850: Georef 62091020; departamento 62091; localidad censal 62091020.
-- Localidad 2851: Georef 62091030; departamento 62091; localidad censal 62091030.
-- Localidad 2852: Georef 62063010; departamento 62063; localidad censal 62063010.
-- Localidad 2853: Georef 62021020; departamento 62021; localidad censal 62021020.
-- Localidad 2854: Georef 62070010; departamento 62070; localidad censal 62070010.
-- Localidad 2855: Georef 62049010; departamento 62049; localidad censal 62049010.
-- Localidad 2856: Georef 62049020; departamento 62049; localidad censal 62049020.
-- Localidad 2857: Georef 62042410; departamento 62042; localidad censal 62042410.
-- Localidad 2858: Georef 62014050; departamento 62014; localidad censal 62014050.
-- Localidad 2859: Georef 62014060; departamento 62014; localidad censal 62014060.
-- Localidad 2860: Georef 62070020; departamento 62070; localidad censal 62070020.
-- Localidad 2861: Georef 62021030; departamento 62021; localidad censal 62021030.
-- Localidad 2862: Georef 62091040; departamento 62091; localidad censal 62091040.
-- Localidad 2863: Georef 62007020; departamento 62007; localidad censal 62007020.
-- Localidad 2864: Georef 62035030; departamento 62035; localidad censal 62035030.
-- Localidad 2865: Georef 62077005; departamento 62077; localidad censal 62077005.
-- Localidad 2866: Georef 62021040; departamento 62021; localidad censal 62021040.
-- Localidad 2867: Georef 62007030; departamento 62007; localidad censal 62007030.
-- Localidad 2868: Georef 62042420; departamento 62042; localidad censal 62042420.
-- Localidad 2869: Georef 62028020; departamento 62028; localidad censal 62028020.
-- Localidad 2870: Georef 62042430; departamento 62042; localidad censal 62042430.
-- Localidad 2871: Georef 62042440; departamento 62042; localidad censal 62042440.
-- Localidad 2872: Georef 62042450; departamento 62042; localidad censal 62042450.
-- Localidad 2873: Georef 62007040; departamento 62007; localidad censal 62007040.
-- Localidad 2874: Georef 62091050; departamento 62091; localidad censal 62091050.
-- Localidad 2875: Georef 62042460; departamento 62042; localidad censal 62042460.
-- Localidad 2876: Georef 62042470; departamento 62042; localidad censal 62042470.
-- Localidad 2877: Georef 62063013; departamento 62063; localidad censal 62063013.
-- Localidad 2878: Georef 62007050; departamento 62007; localidad censal 62007050.
-- Localidad 2879: Georef 62070030; departamento 62070; localidad censal 62070030.
-- Localidad 2880: Georef 62014070; departamento 62014; localidad censal 62014070.
-- Localidad 2881: Georef 62056010; departamento 62056; localidad censal 62056010.
-- Localidad 2882: Georef 62077010; departamento 62077; localidad censal 62077010.
-- Localidad 2883: Georef 62035040; departamento 62035; localidad censal 62035040.
-- Localidad 2884: Georef 62091060; departamento 62091; localidad censal 62091060.
-- Localidad 2885: Georef 62007060; departamento 62007; localidad censal 62007060.
-- Localidad 2886: Georef 62014080; departamento 62014; localidad censal 62014080.
-- Localidad 2887: Georef 62042480; departamento 62042; localidad censal 62042480.
-- Localidad 2888: Georef 62021047; departamento 62021; localidad censal 62021047.
-- Localidad 2889: Georef 62056020; departamento 62056; localidad censal 62056020.
-- Localidad 2890: Georef 62091070; departamento 62091; localidad censal 62091070.
-- Localidad 2891: Georef 62035050; departamento 62035; localidad censal 62035050.
-- Localidad 2892: Georef 62049030; departamento 62049; localidad censal 62049030.
-- Localidad 2893: Georef 62084040; departamento 62084; localidad censal 62084040.
-- Localidad 2894: Georef 62035060; departamento 62035; localidad censal 62035060.
-- Localidad 2895: Georef 62070040; departamento 62070; localidad censal 62070040.
-- Localidad 2896: Georef 62056030; departamento 62056; localidad censal 62056030.
-- Localidad 2897: Georef 62056040; departamento 62056; localidad censal 62056040.
-- Localidad 2898: Georef 62042020; departamento 62042; localidad censal 62042020.
-- Localidad 2899: Georef 62035070; departamento 62035; localidad censal 62035070.
-- Localidad 2900: Georef 62042490; departamento 62042; localidad censal 62042490.
-- Localidad 2901: Georef 62042500; departamento 62042; localidad censal 62042500.
-- Localidad 2902: Georef 62063017; departamento 62063; localidad censal 62063017.
-- Localidad 2903: Georef 62070060; departamento 62070; localidad censal 62070060.
-- Localidad 2904: Georef 62091090; departamento 62091; localidad censal 62091090.
-- Localidad 2905: Georef 62070070; departamento 62070; localidad censal 62070070.
-- Localidad 2906: Georef 62077020; departamento 62077; localidad censal 62077020.
-- Localidad 2907: Georef 62014090; departamento 62014; localidad censal 62014090.
-- Localidad 2908: Georef 62007070; departamento 62007; localidad censal 62007070.
-- Localidad 2909: Georef 62049040; departamento 62049; localidad censal 62049040.
-- Localidad 2910: Georef 62042240; departamento 62042; localidad censal 62042240.
-- Localidad 2911: Georef 62077030; departamento 62077; localidad censal 62077030.
-- Localidad 2912: Georef 62077040; departamento 62077; localidad censal 62077040.
-- Localidad 2913: Georef 62056050; departamento 62056; localidad censal 62056050.
-- Localidad 2914: Georef 62063020; departamento 62063; localidad censal 62063020.
-- Localidad 2915: Georef 62021050; departamento 62021; localidad censal 62021050.
-- Localidad 2916: Georef 62077045; departamento 62077; localidad censal 62077045.
-- Localidad 2917: Georef 62063060; departamento 62063; localidad censal 62063060.
-- Localidad 2918: Georef 62077050; departamento 62077; localidad censal 62077050.
-- Localidad 2919: Georef 62021060; departamento 62021; localidad censal 62021060.
-- Localidad 2920: Georef 62007080; departamento 62007; localidad censal 62007080.
-- Localidad 2921: Georef 62042520; departamento 62042; localidad censal 62042520.
-- Localidad 2922: Georef 62049050; departamento 62049; localidad censal 62049050.
-- Localidad 2923: Georef 62077060; departamento 62077; localidad censal 62077060.
-- Localidad 2924: Georef 62084050; departamento 62084; localidad censal 62084050.
-- Localidad 2925: Georef 62049060; departamento 62049; localidad censal 62049060.
-- Localidad 2926: Georef 62084060; departamento 62084; localidad censal 62084060.
-- Localidad 2927: Georef 62035080; departamento 62035; localidad censal 62035080.
-- Localidad 2928: Georef 62007090; departamento 62007; localidad censal 62007090.
-- Localidad 2929: Georef 62042530; departamento 62042; localidad censal 62042530.
-- Localidad 2930: Georef 6202106002; departamento 62021; localidad censal 62021060.
-- Localidad 2931: Georef 62021080; departamento 62021; localidad censal 62021080.
-- Localidad 2932: Georef 62042540; departamento 62042; localidad censal 62042540.
-- Localidad 2933: Georef 62070080; departamento 62070; localidad censal 62070080.
-- Localidad 2934: Georef 6202106003; departamento 62021; localidad censal 62021060.
-- Localidad 2935: Georef 62042550; departamento 62042; localidad censal 62042550.
-- Localidad 2936: Georef 62021110; departamento 62021; localidad censal 62021110.
-- Localidad 2937: Georef 62042560; departamento 62042; localidad censal 62042560.
-- Localidad 2938: Georef 62042570; departamento 62042; localidad censal 62042570.
-- Localidad 2939: Georef 62049070; departamento 62049; localidad censal 62049070.
-- Localidad 2940: Georef 66161010; departamento 66161; localidad censal 66161010.
-- Localidad 2941: Georef 66056010; departamento 66056; localidad censal 66056010.
-- Localidad 2942: Georef 66126010; departamento 66126; localidad censal 66126010.
-- Localidad 2943: Georef 66133010; departamento 66133; localidad censal 66133010.
-- Localidad 2944: Georef 66098010; departamento 66098; localidad censal 66098010.
-- Localidad 2945: Georef 66154010; departamento 66154; localidad censal 66154010.
-- Localidad 2946: Georef 66154020; departamento 66154; localidad censal 66154020.
-- Localidad 2947: Georef 66140010; departamento 66140; localidad censal 66140010.
-- Localidad 2948: Georef 66007010; departamento 66007; localidad censal 66007010.
-- Localidad 2949: Georef 66042003; departamento 66042; localidad censal 66042003.
-- Localidad 2950: Georef 66042005; departamento 66042; localidad censal 66042005.
-- Localidad 2951: Georef 66042007; departamento 66042; localidad censal 66042007.
-- Localidad 2952: Georef 66098020; departamento 66098; localidad censal 66098020.
-- Localidad 2953: Georef 66014010; departamento 66014; localidad censal 66014010.
-- Localidad 2954: Georef 66021010; departamento 66021; localidad censal 66021010.
-- Localidad 2955: Georef 66056030; departamento 66056; localidad censal 66056030.
-- Localidad 2956: Georef 66056040; departamento 66056; localidad censal 66056040.
-- Localidad 2957: Georef 66161020; departamento 66161; localidad censal 66161020.
-- Localidad 2958: Georef 66147010; departamento 66147; localidad censal 66147010.
-- Localidad 2959: Georef 66049010; departamento 66049; localidad censal 66049010.
-- Localidad 2960: Georef 66056050; departamento 66056; localidad censal 66056050.
-- Localidad 2961: Georef 66133020; departamento 66133; localidad censal 66133020.
-- Localidad 2962: Georef 66056060; departamento 66056; localidad censal 66056060.
-- Localidad 2963: Georef 66007020; departamento 66007; localidad censal 66007020.
-- Localidad 2964: Georef 66007030; departamento 66007; localidad censal 66007030.
-- Localidad 2965: Georef 66035010; departamento 66035; localidad censal 66035010.
-- Localidad 2966: Georef 66042010; departamento 66042; localidad censal 66042010.
-- Localidad 2967: Georef 66049020; departamento 66049; localidad censal 66049020.
-- Localidad 2968: Georef 66091010; departamento 66091; localidad censal 66091010.
-- Localidad 2969: Georef 66126020; departamento 66126; localidad censal 66126020.
-- Localidad 2970: Georef 66140020; departamento 66140; localidad censal 66140020.
-- Localidad 2971: Georef 66056070; departamento 66056; localidad censal 66056070.
-- Localidad 2972: Georef 66133030; departamento 66133; localidad censal 66133030.
-- Localidad 2973: Georef 66098030; departamento 66098; localidad censal 66098030.
-- Localidad 2974: Georef 66007040; departamento 66007; localidad censal 66007040.
-- Localidad 2975: Georef 66007050; departamento 66007; localidad censal 66007050.
-- Localidad 2976: Georef 6602805001; departamento 66028; localidad censal 66028050.
-- Localidad 2977: Georef 6602805002; departamento 66028; localidad censal 66028050.
-- Localidad 2978: Georef 66056080; departamento 66056; localidad censal 66056080.
-- Localidad 2979: Georef 66049030; departamento 66049; localidad censal 66049030.
-- Localidad 2980: Georef 66042020; departamento 66042; localidad censal 66042020.
-- Localidad 2981: Georef 66112010; departamento 66112; localidad censal 66112010.
-- Localidad 2982: Georef 66084010; departamento 66084; localidad censal 66084010.
-- Localidad 2983: Georef 66140030; departamento 66140; localidad censal 66140030.
-- Localidad 2984: Georef 66140040; departamento 66140; localidad censal 66140040.
-- Localidad 2985: Georef 66007060; departamento 66007; localidad censal 66007060.
-- Localidad 2986: Georef 66126030; departamento 66126; localidad censal 66126030.
-- Localidad 2987: Georef 66084020; departamento 66084; localidad censal 66084020.
-- Localidad 2988: Georef 66112020; departamento 66112; localidad censal 66112020.
-- Localidad 2989: Georef 66056090; departamento 66056; localidad censal 66056090.
-- Localidad 2990: Georef 66007070; departamento 66007; localidad censal 66007070.
-- Localidad 2991: Georef 66056100; departamento 66056; localidad censal 66056100.
-- Localidad 2992: Georef 66049040; departamento 66049; localidad censal 66049040.
-- Localidad 2993: Georef 66056110; departamento 66056; localidad censal 66056110.
-- Localidad 2994: Georef 66007080; departamento 66007; localidad censal 66007080.
-- Localidad 2995: Georef 66063010; departamento 66063; localidad censal 66063010.
-- Localidad 2996: Georef 66056120; departamento 66056; localidad censal 66056120.
-- Localidad 2997: Georef 66126040; departamento 66126; localidad censal 66126040.
-- Localidad 2998: Georef 66133035; departamento 66133; localidad censal 66133035.
-- Localidad 2999: Georef 66070010; departamento 66070; localidad censal 66070010.
-- Localidad 3000: Georef 66070020; departamento 66070; localidad censal 66070020.
-- Localidad 3001: Georef 66007090; departamento 66007; localidad censal 66007090.
-- Localidad 3002: Georef 66077010; departamento 66077; localidad censal 66077010.
-- Localidad 3003: Georef 66084030; departamento 66084; localidad censal 66084030.
-- Localidad 3004: Georef 66035020; departamento 66035; localidad censal 66035020.
-- Localidad 3005: Georef 66147015; departamento 66147; localidad censal 66147015.
-- Localidad 3006: Georef 6612602002; departamento 66126; localidad censal 66126020.
-- Localidad 3007: Georef 66091020; departamento 66091; localidad censal 66091020.
-- Localidad 3008: Georef 66119010; departamento 66119; localidad censal 66119010.
-- Localidad 3009: Georef 66147020; departamento 66147; localidad censal 66147020.
-- Localidad 3010: Georef 66133040; departamento 66133; localidad censal 66133040.
-- Localidad 3011: Georef 66098040; departamento 66098; localidad censal 66098040.
-- Localidad 3012: Georef 66007100; departamento 66007; localidad censal 66007100.
-- Localidad 3013: Georef 66133050; departamento 66133; localidad censal 66133050.
-- Localidad 3014: Georef 66161030; departamento 66161; localidad censal 66161030.
-- Localidad 3015: Georef 66007110; departamento 66007; localidad censal 66007110.
-- Localidad 3016: Georef 66112030; departamento 66112; localidad censal 66112030.
-- Localidad 3017: Georef 66007120; departamento 66007; localidad censal 66007120.
-- Localidad 3018: Georef 66056130; departamento 66056; localidad censal 66056130.
-- Localidad 3019: Georef 66056150; departamento 66056; localidad censal 66056150.
-- Localidad 3020: Georef 6605609002; departamento 66056; localidad censal 66056090.
-- Localidad 3021: Georef 66119020; departamento 66119; localidad censal 66119020.
-- Localidad 3022: Georef 66161040; departamento 66161; localidad censal 66161040.
-- Localidad 3023: Georef 66007130; departamento 66007; localidad censal 66007130.
-- Localidad 3024: Georef 66105010; departamento 66105; localidad censal 66105010.
-- Localidad 3025: Georef 66056170; departamento 66056; localidad censal 66056170.
-- Localidad 3026: Georef 66056180; departamento 66056; localidad censal 66056180.
-- Localidad 3027: Georef 66014020; departamento 66014; localidad censal 66014020.
-- Localidad 3028: Georef 66126060; departamento 66126; localidad censal 66126060.
-- Localidad 3029: Georef 66007140; departamento 66007; localidad censal 66007140.
-- Localidad 3030: Georef 66056190; departamento 66056; localidad censal 66056190.
-- Localidad 3031: Georef 66133060; departamento 66133; localidad censal 66133060.
-- Localidad 3032: Georef 66161050; departamento 66161; localidad censal 66161050.
-- Localidad 3033: Georef 66056200; departamento 66056; localidad censal 66056200.
-- Localidad 3034: Georef 66070030; departamento 66070; localidad censal 66070030.
-- Localidad 3035: Georef 6605611002; departamento 66056; localidad censal 66056110.
-- Localidad 3036: Georef 66007150; departamento 66007; localidad censal 66007150.
-- Localidad 3037: Georef 66112070; departamento 66112; localidad censal 66112070.
-- Localidad 3038: Georef 66133070; departamento 66133; localidad censal 66133070.
-- Localidad 3039: Georef 66140050; departamento 66140; localidad censal 66140050.
-- Localidad 3040: Georef 66147030; departamento 66147; localidad censal 66147030.
-- Localidad 3041: Georef 66028050; departamento 66028; localidad censal 66028050.
-- Localidad 3042: Georef 66035030; departamento 66035; localidad censal 66035030.
-- Localidad 3043: Georef 66105020; departamento 66105; localidad censal 66105020.
-- Localidad 3044: Georef 66154040; departamento 66154; localidad censal 66154040.
-- Localidad 3045: Georef 66140060; departamento 66140; localidad censal 66140060.
-- Localidad 3046: Georef 66112040; departamento 66112; localidad censal 66112040.
-- Localidad 3047: Georef 66112080; departamento 66112; localidad censal 66112080.
-- Localidad 3048: Georef 66161060; departamento 66161; localidad censal 66161060.
-- Localidad 3049: Georef 66126070; departamento 66126; localidad censal 66126070.
-- Localidad 3050: Georef 66133080; departamento 66133; localidad censal 66133080.
-- Localidad 3051: Georef 66133090; departamento 66133; localidad censal 66133090.
-- Localidad 3052: Georef 66105030; departamento 66105; localidad censal 66105030.
-- Localidad 3053: Georef 66161070; departamento 66161; localidad censal 66161070.
-- Localidad 3054: Georef 66133100; departamento 66133; localidad censal 66133100.
-- Localidad 3055: Georef 66119030; departamento 66119; localidad censal 66119030.
-- Localidad 3056: Georef 66098050; departamento 66098; localidad censal 66098050.
-- Localidad 3057: Georef 66056220; departamento 66056; localidad censal 66056220.
-- Localidad 3058: Georef 66056230; departamento 66056; localidad censal 66056230.
-- Localidad 3059: Georef 66105040; departamento 66105; localidad censal 66105040.
-- Localidad 3060: Georef 66007160; departamento 66007; localidad censal 66007160.
-- Localidad 3061: Georef 66021020; departamento 66021; localidad censal 66021020.
-- Localidad 3062: Georef 66056240; departamento 66056; localidad censal 66056240.
-- Localidad 3063: Georef 66126080; departamento 66126; localidad censal 66126080.
-- Localidad 3064: Georef 66077020; departamento 66077; localidad censal 66077020.
-- Localidad 3065: Georef 66028060; departamento 66028; localidad censal 66028060.
-- Localidad 3066: Georef 66056250; departamento 66056; localidad censal 66056250.
-- Localidad 3067: Georef 70063040; departamento 70063; localidad censal 70063040.
-- Localidad 3068: Georef 70063010; departamento 70063; localidad censal 70063010.
-- Localidad 3069: Georef 70049010; departamento 70049; localidad censal 70049010.
-- Localidad 3070: Georef 70119010; departamento 70119; localidad censal 70119010.
-- Localidad 3071: Georef 70119020; departamento 70119; localidad censal 70119020.
-- Localidad 3072: Georef 70021010; departamento 70021; localidad censal 70021010.
-- Localidad 3073: Georef 70070005; departamento 70070; localidad censal 70070005.
-- Localidad 3074: Georef 70070010; departamento 70070; localidad censal 70070010.
-- Localidad 3075: Georef 70091010; departamento 70091; localidad censal 70091010.
-- Localidad 3076: Georef 70035010; departamento 70035; localidad censal 70035010.
-- Localidad 3077: Georef 70021020; departamento 70021; localidad censal 70021020.
-- Localidad 3078: Georef 70105010; departamento 70105; localidad censal 70105010.
-- Localidad 3079: Georef 70070020; departamento 70070; localidad censal 70070020.
-- Localidad 3080: Georef 70035020; departamento 70035; localidad censal 70035020.
-- Localidad 3081: Georef 70042010; departamento 70042; localidad censal 70042010.
-- Localidad 3082: Georef 70119030; departamento 70119; localidad censal 70119030.
-- Localidad 3083: Georef 70105020; departamento 70105; localidad censal 70105020.
-- Localidad 3084: Georef 70105030; departamento 70105; localidad censal 70105030.
-- Localidad 3085: Georef 7009801001; departamento 70098; localidad censal 70098010.
-- Localidad 3086: Georef 70105040; departamento 70105; localidad censal 70105040.
-- Localidad 3087: Georef 70091020; departamento 70091; localidad censal 70091020.
-- Localidad 3088: Georef 70126010; departamento 70126; localidad censal 70126010.
-- Localidad 3089: Georef 7007701001; departamento 70077; localidad censal 70077010.
-- Localidad 3090: Georef 70056010; departamento 70056; localidad censal 70056010.
-- Localidad 3091: Georef 70007010; departamento 70007; localidad censal 70007010.
-- Localidad 3092: Georef 70035030; departamento 70035; localidad censal 70035030.
-- Localidad 3093: Georef 70056020; departamento 70056; localidad censal 70056020.
-- Localidad 3094: Georef 70056030; departamento 70056; localidad censal 70056030.
-- Localidad 3095: Georef 70049030; departamento 70049; localidad censal 70049030.
-- Localidad 3096: Georef 7012603001; departamento 70126; localidad censal 70126030.
-- Localidad 3097: Georef 7005606002; departamento 70056; localidad censal 70056060.
-- Localidad 3098: Georef 7007004001; departamento 70070; localidad censal 70070040.
-- Localidad 3099: Georef 70063030; departamento 70063; localidad censal 70063030.
-- Localidad 3100: Georef 70049040; departamento 70049; localidad censal 70049040.
-- Localidad 3101: Georef 70105060; departamento 70105; localidad censal 70105060.
-- Localidad 3102: Georef 7003504001; departamento 70035; localidad censal 70035040.
-- Localidad 3103: Georef 70035040; departamento 70035; localidad censal 70035040.
-- Localidad 3104: Georef 70014010; departamento 70014; localidad censal 70014010.
-- Localidad 3105: Georef 70119040; departamento 70119; localidad censal 70119040.
-- Localidad 3106: Georef 70105070; departamento 70105; localidad censal 70105070.
-- Localidad 3107: Georef 7003504002; departamento 70035; localidad censal 70035040.
-- Localidad 3108: Georef 70035050; departamento 70035; localidad censal 70035050.
-- Localidad 3109: Georef 70056040; departamento 70056; localidad censal 70056040.
-- Localidad 3110: Georef 70056050; departamento 70056; localidad censal 70056050.
-- Localidad 3111: Georef 70056060; departamento 70056; localidad censal 70056060.
-- Localidad 3112: Georef 70105080; departamento 70105; localidad censal 70105080.
-- Localidad 3113: Georef 70035060; departamento 70035; localidad censal 70035060.
-- Localidad 3114: Georef 70049050; departamento 70049; localidad censal 70049050.
-- Localidad 3115: Georef 70105090; departamento 70105; localidad censal 70105090.
-- Localidad 3116: Georef 70070030; departamento 70070; localidad censal 70070030.
-- Localidad 3117: Georef 70077010; departamento 70077; localidad censal 70077010.
-- Localidad 3118: Georef 70084010; departamento 70084; localidad censal 70084010.
-- Localidad 3119: Georef 70049060; departamento 70049; localidad censal 70049060.
-- Localidad 3120: Georef 70056070; departamento 70056; localidad censal 70056070.
-- Localidad 3121: Georef 70091030; departamento 70091; localidad censal 70091030.
-- Localidad 3122: Georef 70056080; departamento 70056; localidad censal 70056080.
-- Localidad 3123: Georef 70028010; departamento 70028; localidad censal 70028010.
-- Localidad 3124: Georef 70098010; departamento 70098; localidad censal 70098010.
-- Localidad 3125: Georef 70021030; departamento 70021; localidad censal 70021030.
-- Localidad 3126: Georef 70056090; departamento 70056; localidad censal 70056090.
-- Localidad 3127: Georef 70049070; departamento 70049; localidad censal 70049070.
-- Localidad 3128: Georef 70126020; departamento 70126; localidad censal 70126020.
-- Localidad 3129: Georef 70119050; departamento 70119; localidad censal 70119050.
-- Localidad 3130: Georef 70035070; departamento 70035; localidad censal 70035070.
-- Localidad 3131: Georef 7007004002; departamento 70070; localidad censal 70070040.
-- Localidad 3132: Georef 70070040; departamento 70070; localidad censal 70070040.
-- Localidad 3133: Georef 7007005001; departamento 70070; localidad censal 70070050.
-- Localidad 3134: Georef 70070050; departamento 70070; localidad censal 70070050.
-- Localidad 3135: Georef 70133010; departamento 70133; localidad censal 70133010.
-- Localidad 3136: Georef 70077020; departamento 70077; localidad censal 70077020.
-- Localidad 3137: Georef 7012603002; departamento 70126; localidad censal 70126030.
-- Localidad 3138: Georef 70126030; departamento 70126; localidad censal 70126030.
-- Localidad 3139: Georef 70070060; departamento 70070; localidad censal 70070060.
-- Localidad 3140: Georef 70091040; departamento 70091; localidad censal 70091040.
-- Localidad 3141: Georef 70091050; departamento 70091; localidad censal 70091050.
-- Localidad 3142: Georef 70091060; departamento 70091; localidad censal 70091060.
-- Localidad 3143: Georef 7001402001; departamento 70014; localidad censal 70014020.
-- Localidad 3144: Georef 70014020; departamento 70014; localidad censal 70014020.
-- Localidad 3145: Georef 70126040; departamento 70126; localidad censal 70126040.
-- Localidad 3146: Georef 70007020; departamento 70007; localidad censal 70007020.
-- Localidad 3147: Georef 70112010; departamento 70112; localidad censal 70112010.
-- Localidad 3148: Georef 70035080; departamento 70035; localidad censal 70035080.
-- Localidad 3149: Georef 70056100; departamento 70056; localidad censal 70056100.
-- Localidad 3150: Georef 70105100; departamento 70105; localidad censal 70105100.
-- Localidad 3151: Georef 70056110; departamento 70056; localidad censal 70056110.
-- Localidad 3152: Georef 7007005002; departamento 70070; localidad censal 70070050.
-- Localidad 3153: Georef 70119060; departamento 70119; localidad censal 70119060.
-- Localidad 3154: Georef 70091070; departamento 70091; localidad censal 70091070.
-- Localidad 3155: Georef 70126050; departamento 70126; localidad censal 70126050.
-- Localidad 3156: Georef 7001402002; departamento 70014; localidad censal 70014020.
-- Localidad 3157: Georef 7013301002; departamento 70133; localidad censal 70133010.
-- Localidad 3158: Georef 7403508002; departamento 74035; localidad censal 74035080.
-- Localidad 3159: Georef 74056010; departamento 74056; localidad censal 74056010.
-- Localidad 3160: Georef 74056020; departamento 74056; localidad censal 74056020.
-- Localidad 3161: Georef 74042010; departamento 74042; localidad censal 74042010.
-- Localidad 3162: Georef 74042020; departamento 74042; localidad censal 74042020.
-- Localidad 3163: Georef 74042030; departamento 74042; localidad censal 74042030.
-- Localidad 3164: Georef 74056030; departamento 74056; localidad censal 74056030.
-- Localidad 3165: Georef 74042040; departamento 74042; localidad censal 74042040.
-- Localidad 3166: Georef 74056040; departamento 74056; localidad censal 74056040.
-- Localidad 3167: Georef 74042050; departamento 74042; localidad censal 74042050.
-- Localidad 3168: Georef 74007010; departamento 74007; localidad censal 74007010.
-- Localidad 3169: Georef 74021010; departamento 74021; localidad censal 74021010.
-- Localidad 3170: Georef 74049010; departamento 74049; localidad censal 74049010.
-- Localidad 3171: Georef 74056050; departamento 74056; localidad censal 74056050.
-- Localidad 3172: Georef 7405610001; departamento 74056; localidad censal 74056100.
-- Localidad 3173: Georef 74056060; departamento 74056; localidad censal 74056060.
-- Localidad 3174: Georef 74028010; departamento 74028; localidad censal 74028010.
-- Localidad 3175: Georef 74028020; departamento 74028; localidad censal 74028020.
-- Localidad 3176: Georef 7403508001; departamento 74035; localidad censal 74035080.
-- Localidad 3177: Georef 7405610002; departamento 74056; localidad censal 74056100.
-- Localidad 3178: Georef 74056070; departamento 74056; localidad censal 74056070.
-- Localidad 3179: Georef 7405610003; departamento 74056; localidad censal 74056100.
-- Localidad 3180: Georef 74021020; departamento 74021; localidad censal 74021020.
-- Localidad 3181: Georef 74056080; departamento 74056; localidad censal 74056080.
-- Localidad 3182: Georef 74021025; departamento 74021; localidad censal 74021025.
-- Localidad 3183: Georef 74042060; departamento 74042; localidad censal 74042060.
-- Localidad 3184: Georef 74042070; departamento 74042; localidad censal 74042070.
-- Localidad 3185: Georef 74021030; departamento 74021; localidad censal 74021030.
-- Localidad 3186: Georef 74056090; departamento 74056; localidad censal 74056090.
-- Localidad 3187: Georef 74035010; departamento 74035; localidad censal 74035010.
-- Localidad 3188: Georef 74035020; departamento 74035; localidad censal 74035020.
-- Localidad 3189: Georef 74056100; departamento 74056; localidad censal 74056100.
-- Localidad 3190: Georef 74035030; departamento 74035; localidad censal 74035030.
-- Localidad 3191: Georef 74014010; departamento 74014; localidad censal 74014010.
-- Localidad 3192: Georef 74021050; departamento 74021; localidad censal 74021050.
-- Localidad 3193: Georef 74042080; departamento 74042; localidad censal 74042080.
-- Localidad 3194: Georef 74035040; departamento 74035; localidad censal 74035040.
-- Localidad 3195: Georef 74056105; departamento 74056; localidad censal 74056105.
-- Localidad 3196: Georef 7403507001; departamento 74035; localidad censal 74035070.
-- Localidad 3197: Georef 74021060; departamento 74021; localidad censal 74021060.
-- Localidad 3198: Georef 74063010; departamento 74063; localidad censal 74063010.
-- Localidad 3199: Georef 74049030; departamento 74049; localidad censal 74049030.
-- Localidad 3200: Georef 74063020; departamento 74063; localidad censal 74063020.
-- Localidad 3201: Georef 7405610004; departamento 74056; localidad censal 74056100.
-- Localidad 3202: Georef 74063030; departamento 74063; localidad censal 74063030.
-- Localidad 3203: Georef 74063040; departamento 74063; localidad censal 74063040.
-- Localidad 3204: Georef 74035050; departamento 74035; localidad censal 74035050.
-- Localidad 3205: Georef 74007030; departamento 74007; localidad censal 74007030.
-- Localidad 3206: Georef 74049040; departamento 74049; localidad censal 74049040.
-- Localidad 3207: Georef 74049050; departamento 74049; localidad censal 74049050.
-- Localidad 3208: Georef 74042090; departamento 74042; localidad censal 74042090.
-- Localidad 3209: Georef 74007040; departamento 74007; localidad censal 74007040.
-- Localidad 3210: Georef 74042100; departamento 74042; localidad censal 74042100.
-- Localidad 3211: Georef 74049060; departamento 74049; localidad censal 74049060.
-- Localidad 3212: Georef 74056110; departamento 74056; localidad censal 74056110.
-- Localidad 3213: Georef 74035055; departamento 74035; localidad censal 74035055.
-- Localidad 3214: Georef 74042110; departamento 74042; localidad censal 74042110.
-- Localidad 3215: Georef 74028030; departamento 74028; localidad censal 74028030.
-- Localidad 3216: Georef 74042120; departamento 74042; localidad censal 74042120.
-- Localidad 3217: Georef 74014020; departamento 74014; localidad censal 74014020.
-- Localidad 3218: Georef 74042130; departamento 74042; localidad censal 74042130.
-- Localidad 3219: Georef 74028040; departamento 74028; localidad censal 74028040.
-- Localidad 3220: Georef 74063050; departamento 74063; localidad censal 74063050.
-- Localidad 3221: Georef 74063060; departamento 74063; localidad censal 74063060.
-- Localidad 3222: Georef 74056120; departamento 74056; localidad censal 74056120.
-- Localidad 3223: Georef 74007050; departamento 74007; localidad censal 74007050.
-- Localidad 3224: Georef 74028050; departamento 74028; localidad censal 74028050.
-- Localidad 3225: Georef 74021070; departamento 74021; localidad censal 74021070.
-- Localidad 3226: Georef 74021090; departamento 74021; localidad censal 74021090.
-- Localidad 3227: Georef 74056130; departamento 74056; localidad censal 74056130.
-- Localidad 3228: Georef 74007070; departamento 74007; localidad censal 74007070.
-- Localidad 3229: Georef 74056140; departamento 74056; localidad censal 74056140.
-- Localidad 3230: Georef 74035060; departamento 74035; localidad censal 74035060.
-- Localidad 3231: Georef 74056150; departamento 74056; localidad censal 74056150.
-- Localidad 3232: Georef 74063070; departamento 74063; localidad censal 74063070.
-- Localidad 3233: Georef 74028060; departamento 74028; localidad censal 74028060.
-- Localidad 3234: Georef 7405610005; departamento 74056; localidad censal 74056100.
-- Localidad 3235: Georef 74049070; departamento 74049; localidad censal 74049070.
-- Localidad 3236: Georef 74049080; departamento 74049; localidad censal 74049080.
-- Localidad 3237: Georef 74028070; departamento 74028; localidad censal 74028070.
-- Localidad 3238: Georef 74042140; departamento 74042; localidad censal 74042140.
-- Localidad 3239: Georef 74014030; departamento 74014; localidad censal 74014030.
-- Localidad 3240: Georef 74063080; departamento 74063; localidad censal 74063080.
-- Localidad 3241: Georef 74028080; departamento 74028; localidad censal 74028080.
-- Localidad 3242: Georef 74014040; departamento 74014; localidad censal 74014040.
-- Localidad 3243: Georef 74028090; departamento 74028; localidad censal 74028090.
-- Localidad 3244: Georef 74035070; departamento 74035; localidad censal 74035070.
-- Localidad 3245: Georef 74035080; departamento 74035; localidad censal 74035080.
-- Localidad 3246: Georef 74035090; departamento 74035; localidad censal 74035090.
-- Localidad 3247: Georef 74056160; departamento 74056; localidad censal 74056160.
-- Localidad 3248: Georef 78021060; departamento 78021; localidad censal 78021060.
-- Localidad 3249: Georef 78049010; departamento 78049; localidad censal 78049010.
-- Localidad 3250: Georef 78014010; departamento 78014; localidad censal 78014010.
-- Localidad 3251: Georef 78014020; departamento 78014; localidad censal 78014020.
-- Localidad 3252: Georef 78007010; departamento 78007; localidad censal 78007010.
-- Localidad 3253: Georef 78028010; departamento 78028; localidad censal 78028010.
-- Localidad 3254: Georef 78028020; departamento 78028; localidad censal 78028020.
-- Localidad 3255: Georef 78021010; departamento 78021; localidad censal 78021010.
-- Localidad 3256: Georef 78014030; departamento 78014; localidad censal 78014030.
-- Localidad 3257: Georef 78049020; departamento 78049; localidad censal 78049020.
-- Localidad 3258: Georef 78049030; departamento 78049; localidad censal 78049030.
-- Localidad 3259: Georef 78014040; departamento 78014; localidad censal 78014040.
-- Localidad 3260: Georef 78021020; departamento 78021; localidad censal 78021020.
-- Localidad 3261: Georef 78014050; departamento 78014; localidad censal 78014050.
-- Localidad 3262: Georef 78014060; departamento 78014; localidad censal 78014060.
-- Localidad 3263: Georef 78035010; departamento 78035; localidad censal 78035010.
-- Localidad 3264: Georef 78035020; departamento 78035; localidad censal 78035020.
-- Localidad 3265: Georef 78014070; departamento 78014; localidad censal 78014070.
-- Localidad 3266: Georef 78014080; departamento 78014; localidad censal 78014080.
-- Localidad 3267: Georef 78042010; departamento 78042; localidad censal 78042010.
-- Localidad 3268: Georef 78007020; departamento 78007; localidad censal 78007020.
-- Localidad 3269: Georef 78021040; departamento 78021; localidad censal 78021040.
-- Localidad 3270: Georef 78021050; departamento 78021; localidad censal 78021050.
-- Localidad 3271: Georef 78014090; departamento 78014; localidad censal 78014090.
-- Localidad 3272: Georef 78028030; departamento 78028; localidad censal 78028030.
-- Localidad 3273: Georef 78021070; departamento 78021; localidad censal 78021070.
-- Localidad 3274: Georef 82042010; departamento 82042; localidad censal 82042010.
-- Localidad 3275: Georef 82084010; departamento 82084; localidad censal 82084010.
-- Localidad 3276: Georef 82091010; departamento 82091; localidad censal 82091010.
-- Localidad 3277: Georef 82084020; departamento 82084; localidad censal 82084020.
-- Localidad 3278: Georef 82028010; departamento 82028; localidad censal 82028010.
-- Localidad 3279: Georef 82021010; departamento 82021; localidad censal 82021010.
-- Localidad 3280: Georef 82119010; departamento 82119; localidad censal 82119010.
-- Localidad 3281: Georef 82098010; departamento 82098; localidad censal 82098010.
-- Localidad 3282: Georef 82084030; departamento 82084; localidad censal 82084030.
-- Localidad 3283: Georef 82084040; departamento 82084; localidad censal 82084040.
-- Localidad 3284: Georef 82091020; departamento 82091; localidad censal 82091020.
-- Localidad 3285: Georef 82042020; departamento 82042; localidad censal 82042020.
-- Localidad 3286: Georef 82063010; departamento 82063; localidad censal 82063010.
-- Localidad 3287: Georef 82021020; departamento 82021; localidad censal 82021020.
-- Localidad 3288: Georef 82112010; departamento 82112; localidad censal 82112010.
-- Localidad 3289: Georef 82084050; departamento 82084; localidad censal 82084050.
-- Localidad 3290: Georef 82014010; departamento 82014; localidad censal 82014010.
-- Localidad 3291: Georef 82084060; departamento 82084; localidad censal 82084060.
-- Localidad 3292: Georef 82007010; departamento 82007; localidad censal 82007010.
-- Localidad 3293: Georef 82105010; departamento 82105; localidad censal 82105010.
-- Localidad 3294: Georef 82063020; departamento 82063; localidad censal 82063020.
-- Localidad 3295: Georef 82049010; departamento 82049; localidad censal 82049010.
-- Localidad 3296: Georef 82063030; departamento 82063; localidad censal 82063030.
-- Localidad 3297: Georef 82084070; departamento 82084; localidad censal 82084070.
-- Localidad 3298: Georef 82091030; departamento 82091; localidad censal 82091030.
-- Localidad 3299: Georef 82014020; departamento 82014; localidad censal 82014020.
-- Localidad 3300: Georef 82021030; departamento 82021; localidad censal 82021030.
-- Localidad 3301: Georef 82021040; departamento 82021; localidad censal 82021040.
-- Localidad 3302: Georef 82049020; departamento 82049; localidad censal 82049020.
-- Localidad 3303: Georef 82091040; departamento 82091; localidad censal 82091040.
-- Localidad 3304: Georef 82105020; departamento 82105; localidad censal 82105020.
-- Localidad 3305: Georef 82105030; departamento 82105; localidad censal 82105030.
-- Localidad 3306: Georef 82028020; departamento 82028; localidad censal 82028020.
-- Localidad 3307: Georef 82105040; departamento 82105; localidad censal 82105040.
-- Localidad 3308: Georef 82056010; departamento 82056; localidad censal 82056010.
-- Localidad 3309: Georef 82105050; departamento 82105; localidad censal 82105050.
-- Localidad 3310: Georef 82028030; departamento 82028; localidad censal 82028030.
-- Localidad 3311: Georef 82021050; departamento 82021; localidad censal 82021050.
-- Localidad 3312: Georef 82021060; departamento 82021; localidad censal 82021060.
-- Localidad 3313: Georef 82021070; departamento 82021; localidad censal 82021070.
-- Localidad 3314: Georef 82014030; departamento 82014; localidad censal 82014030.
-- Localidad 3315: Georef 82049030; departamento 82049; localidad censal 82049030.
-- Localidad 3316: Georef 82105060; departamento 82105; localidad censal 82105060.
-- Localidad 3317: Georef 82014040; departamento 82014; localidad censal 82014040.
-- Localidad 3318: Georef 82028040; departamento 82028; localidad censal 82028040.
-- Localidad 3319: Georef 82007020; departamento 82007; localidad censal 82007020.
-- Localidad 3320: Georef 82056020; departamento 82056; localidad censal 82056020.
-- Localidad 3321: Georef 82063040; departamento 82063; localidad censal 82063040.
-- Localidad 3322: Georef 82098020; departamento 82098; localidad censal 82098020.
-- Localidad 3323: Georef 82042030; departamento 82042; localidad censal 82042030.
-- Localidad 3324: Georef 82133010; departamento 82133; localidad censal 82133010.
-- Localidad 3325: Georef 82063050; departamento 82063; localidad censal 82063050.
-- Localidad 3326: Georef 82056030; departamento 82056; localidad censal 82056030.
-- Localidad 3327: Georef 82042040; departamento 82042; localidad censal 82042040.
-- Localidad 3328: Georef 82133020; departamento 82133; localidad censal 82133020.
-- Localidad 3329: Georef 82028050; departamento 82028; localidad censal 82028050.
-- Localidad 3330: Georef 82126010; departamento 82126; localidad censal 82126010.
-- Localidad 3331: Georef 82063060; departamento 82063; localidad censal 82063060.
-- Localidad 3332: Georef 82119020; departamento 82119; localidad censal 82119020.
-- Localidad 3333: Georef 82091050; departamento 82091; localidad censal 82091050.
-- Localidad 3334: Georef 82119030; departamento 82119; localidad censal 82119030.
-- Localidad 3335: Georef 82126020; departamento 82126; localidad censal 82126020.
-- Localidad 3336: Georef 82042050; departamento 82042; localidad censal 82042050.
-- Localidad 3337: Georef 82084080; departamento 82084; localidad censal 82084080.
-- Localidad 3338: Georef 82042060; departamento 82042; localidad censal 82042060.
-- Localidad 3339: Georef 82056040; departamento 82056; localidad censal 82056040.
-- Localidad 3340: Georef 82105070; departamento 82105; localidad censal 82105070.
-- Localidad 3341: Georef 82126030; departamento 82126; localidad censal 82126030.
-- Localidad 3342: Georef 82014050; departamento 82014; localidad censal 82014050.
-- Localidad 3343: Georef 82126040; departamento 82126; localidad censal 82126040.
-- Localidad 3344: Georef 82021080; departamento 82021; localidad censal 82021080.
-- Localidad 3345: Georef 82070010; departamento 82070; localidad censal 82070010.
-- Localidad 3346: Georef 82035010; departamento 82035; localidad censal 82035010.
-- Localidad 3347: Georef 82112020; departamento 82112; localidad censal 82112020.
-- Localidad 3348: Georef 82105080; departamento 82105; localidad censal 82105080.
-- Localidad 3349: Georef 82028060; departamento 82028; localidad censal 82028060.
-- Localidad 3350: Georef 82091060; departamento 82091; localidad censal 82091060.
-- Localidad 3351: Georef 82014060; departamento 82014; localidad censal 82014060.
-- Localidad 3352: Georef 82014070; departamento 82014; localidad censal 82014070.
-- Localidad 3353: Georef 82042070; departamento 82042; localidad censal 82042070.
-- Localidad 3354: Georef 82042080; departamento 82042; localidad censal 82042080.
-- Localidad 3355: Georef 82042090; departamento 82042; localidad censal 82042090.
-- Localidad 3356: Georef 82056050; departamento 82056; localidad censal 82056050.
-- Localidad 3357: Georef 82133030; departamento 82133; localidad censal 82133030.
-- Localidad 3358: Georef 82091070; departamento 82091; localidad censal 82091070.
-- Localidad 3359: Georef 82126050; departamento 82126; localidad censal 82126050.
-- Localidad 3360: Georef 82021090; departamento 82021; localidad censal 82021090.
-- Localidad 3361: Georef 82091080; departamento 82091; localidad censal 82091080.
-- Localidad 3362: Georef 82021100; departamento 82021; localidad censal 82021100.
-- Localidad 3363: Georef 82112030; departamento 82112; localidad censal 82112030.
-- Localidad 3364: Georef 82098030; departamento 82098; localidad censal 82098030.
-- Localidad 3365: Georef 82021110; departamento 82021; localidad censal 82021110.
-- Localidad 3366: Georef 82056060; departamento 82056; localidad censal 82056060.
-- Localidad 3367: Georef 82021120; departamento 82021; localidad censal 82021120.
-- Localidad 3368: Georef 82091090; departamento 82091; localidad censal 82091090.
-- Localidad 3369: Georef 82091100; departamento 82091; localidad censal 82091100.
-- Localidad 3370: Georef 82105090; departamento 82105; localidad censal 82105090.
-- Localidad 3371: Georef 82119040; departamento 82119; localidad censal 82119040.
-- Localidad 3372: Georef 82084090; departamento 82084; localidad censal 82084090.
-- Localidad 3373: Georef 82021130; departamento 82021; localidad censal 82021130.
-- Localidad 3374: Georef 82084100; departamento 82084; localidad censal 82084100.
-- Localidad 3375: Georef 82056070; departamento 82056; localidad censal 82056070.
-- Localidad 3376: Georef 82126060; departamento 82126; localidad censal 82126060.
-- Localidad 3377: Georef 82084110; departamento 82084; localidad censal 82084110.
-- Localidad 3378: Georef 82070020; departamento 82070; localidad censal 82070020.
-- Localidad 3379: Georef 82091110; departamento 82091; localidad censal 82091110.
-- Localidad 3380: Georef 82105100; departamento 82105; localidad censal 82105100.
-- Localidad 3381: Georef 82105110; departamento 82105; localidad censal 82105110.
-- Localidad 3382: Georef 82042100; departamento 82042; localidad censal 82042100.
-- Localidad 3383: Georef 82021140; departamento 82021; localidad censal 82021140.
-- Localidad 3384: Georef 82049040; departamento 82049; localidad censal 82049040.
-- Localidad 3385: Georef 82084120; departamento 82084; localidad censal 82084120.
-- Localidad 3386: Georef 82049050; departamento 82049; localidad censal 82049050.
-- Localidad 3387: Georef 82126070; departamento 82126; localidad censal 82126070.
-- Localidad 3388: Georef 82070030; departamento 82070; localidad censal 82070030.
-- Localidad 3389: Georef 82042110; departamento 82042; localidad censal 82042110.
-- Localidad 3390: Georef 82063070; departamento 82063; localidad censal 82063070.
-- Localidad 3391: Georef 82070040; departamento 82070; localidad censal 82070040.
-- Localidad 3392: Georef 82028070; departamento 82028; localidad censal 82028070.
-- Localidad 3393: Georef 82021150; departamento 82021; localidad censal 82021150.
-- Localidad 3394: Georef 82070050; departamento 82070; localidad censal 82070050.
-- Localidad 3395: Georef 82021160; departamento 82021; localidad censal 82021160.
-- Localidad 3396: Georef 8202129001; departamento 82021; localidad censal 82021290.
-- Localidad 3397: Georef 82021170; departamento 82021; localidad censal 82021170.
-- Localidad 3398: Georef 82077010; departamento 82077; localidad censal 82077010.
-- Localidad 3399: Georef 82112040; departamento 82112; localidad censal 82112040.
-- Localidad 3400: Georef 82021180; departamento 82021; localidad censal 82021180.
-- Localidad 3401: Georef 82021190; departamento 82021; localidad censal 82021190.
-- Localidad 3402: Georef 82070060; departamento 82070; localidad censal 82070060.
-- Localidad 3403: Georef 82084130; departamento 82084; localidad censal 82084130.
-- Localidad 3404: Georef 82042120; departamento 82042; localidad censal 82042120.
-- Localidad 3405: Georef 82049060; departamento 82049; localidad censal 82049060.
-- Localidad 3406: Georef 82133040; departamento 82133; localidad censal 82133040.
-- Localidad 3407: Georef 82070070; departamento 82070; localidad censal 82070070.
-- Localidad 3408: Georef 82119050; departamento 82119; localidad censal 82119050.
-- Localidad 3409: Georef 82021200; departamento 82021; localidad censal 82021200.
-- Localidad 3410: Georef 82119060; departamento 82119; localidad censal 82119060.
-- Localidad 3411: Georef 82084140; departamento 82084; localidad censal 82084140.
-- Localidad 3412: Georef 82105120; departamento 82105; localidad censal 82105120.
-- Localidad 3413: Georef 82105130; departamento 82105; localidad censal 82105130.
-- Localidad 3414: Georef 82133050; departamento 82133; localidad censal 82133050.
-- Localidad 3415: Georef 82021210; departamento 82021; localidad censal 82021210.
-- Localidad 3416: Georef 82077020; departamento 82077; localidad censal 82077020.
-- Localidad 3417: Georef 82028090; departamento 82028; localidad censal 82028090.
-- Localidad 3418: Georef 82084150; departamento 82084; localidad censal 82084150.
-- Localidad 3419: Georef 82105140; departamento 82105; localidad censal 82105140.
-- Localidad 3420: Georef 82112050; departamento 82112; localidad censal 82112050.
-- Localidad 3421: Georef 82014080; departamento 82014; localidad censal 82014080.
-- Localidad 3422: Georef 82028100; departamento 82028; localidad censal 82028100.
-- Localidad 3423: Georef 82133060; departamento 82133; localidad censal 82133060.
-- Localidad 3424: Georef 82084160; departamento 82084; localidad censal 82084160.
-- Localidad 3425: Georef 82077030; departamento 82077; localidad censal 82077030.
-- Localidad 3426: Georef 82070080; departamento 82070; localidad censal 82070080.
-- Localidad 3427: Georef 82049070; departamento 82049; localidad censal 82049070.
-- Localidad 3428: Georef 82035020; departamento 82035; localidad censal 82035020.
-- Localidad 3429: Georef 82091120; departamento 82091; localidad censal 82091120.
-- Localidad 3430: Georef 82070090; departamento 82070; localidad censal 82070090.
-- Localidad 3431: Georef 82091130; departamento 82091; localidad censal 82091130.
-- Localidad 3432: Georef 82042130; departamento 82042; localidad censal 82042130.
-- Localidad 3433: Georef 82021220; departamento 82021; localidad censal 82021220.
-- Localidad 3434: Georef 82070100; departamento 82070; localidad censal 82070100.
-- Localidad 3435: Georef 82084170; departamento 82084; localidad censal 82084170.
-- Localidad 3436: Georef 82049080; departamento 82049; localidad censal 82049080.
-- Localidad 3437: Georef 82133070; departamento 82133; localidad censal 82133070.
-- Localidad 3438: Georef 82105150; departamento 82105; localidad censal 82105150.
-- Localidad 3439: Georef 82070110; departamento 82070; localidad censal 82070110.
-- Localidad 3440: Georef 82021230; departamento 82021; localidad censal 82021230.
-- Localidad 3441: Georef 82028110; departamento 82028; localidad censal 82028110.
-- Localidad 3442: Georef 82028120; departamento 82028; localidad censal 82028120.
-- Localidad 3443: Georef 82084180; departamento 82084; localidad censal 82084180.
-- Localidad 3444: Georef 82133080; departamento 82133; localidad censal 82133080.
-- Localidad 3445: Georef 82098040; departamento 82098; localidad censal 82098040.
-- Localidad 3446: Georef 82091140; departamento 82091; localidad censal 82091140.
-- Localidad 3447: Georef 82042140; departamento 82042; localidad censal 82042140.
-- Localidad 3448: Georef 82112060; departamento 82112; localidad censal 82112060.
-- Localidad 3449: Georef 82133090; departamento 82133; localidad censal 82133090.
-- Localidad 3450: Georef 82049090; departamento 82049; localidad censal 82049090.
-- Localidad 3451: Georef 82070120; departamento 82070; localidad censal 82070120.
-- Localidad 3452: Georef 82112070; departamento 82112; localidad censal 82112070.
-- Localidad 3453: Georef 82091150; departamento 82091; localidad censal 82091150.
-- Localidad 3454: Georef 82049100; departamento 82049; localidad censal 82049100.
-- Localidad 3455: Georef 82028130; departamento 82028; localidad censal 82028130.
-- Localidad 3456: Georef 82042150; departamento 82042; localidad censal 82042150.
-- Localidad 3457: Georef 82063080; departamento 82063; localidad censal 82063080.
-- Localidad 3458: Georef 82126080; departamento 82126; localidad censal 82126080.
-- Localidad 3459: Georef 82049110; departamento 82049; localidad censal 82049110.
-- Localidad 3460: Georef 82056080; departamento 82056; localidad censal 82056080.
-- Localidad 3461: Georef 82105160; departamento 82105; localidad censal 82105160.
-- Localidad 3462: Georef 82091160; departamento 82091; localidad censal 82091160.
-- Localidad 3463: Georef 82126090; departamento 82126; localidad censal 82126090.
-- Localidad 3464: Georef 82049120; departamento 82049; localidad censal 82049120.
-- Localidad 3465: Georef 82091170; departamento 82091; localidad censal 82091170.
-- Localidad 3466: Georef 82007030; departamento 82007; localidad censal 82007030.
-- Localidad 3467: Georef 82126100; departamento 82126; localidad censal 82126100.
-- Localidad 3468: Georef 82007040; departamento 82007; localidad censal 82007040.
-- Localidad 3469: Georef 82049130; departamento 82049; localidad censal 82049130.
-- Localidad 3470: Georef 82070130; departamento 82070; localidad censal 82070130.
-- Localidad 3471: Georef 82042160; departamento 82042; localidad censal 82042160.
-- Localidad 3472: Georef 82021240; departamento 82021; localidad censal 82021240.
-- Localidad 3473: Georef 82063090; departamento 82063; localidad censal 82063090.
-- Localidad 3474: Georef 82077040; departamento 82077; localidad censal 82077040.
-- Localidad 3475: Georef 82105170; departamento 82105; localidad censal 82105170.
-- Localidad 3476: Georef 82105180; departamento 82105; localidad censal 82105180.
-- Localidad 3477: Georef 82133100; departamento 82133; localidad censal 82133100.
-- Localidad 3478: Georef 82126110; departamento 82126; localidad censal 82126110.
-- Localidad 3479: Georef 82049140; departamento 82049; localidad censal 82049140.
-- Localidad 3480: Georef 82014090; departamento 82014; localidad censal 82014090.
-- Localidad 3481: Georef 82084190; departamento 82084; localidad censal 82084190.
-- Localidad 3482: Georef 82014100; departamento 82014; localidad censal 82014100.
-- Localidad 3483: Georef 82014110; departamento 82014; localidad censal 82014110.
-- Localidad 3484: Georef 82035030; departamento 82035; localidad censal 82035030.
-- Localidad 3485: Georef 82056090; departamento 82056; localidad censal 82056090.
-- Localidad 3486: Georef 82119070; departamento 82119; localidad censal 82119070.
-- Localidad 3487: Georef 82105190; departamento 82105; localidad censal 82105190.
-- Localidad 3488: Georef 82042170; departamento 82042; localidad censal 82042170.
-- Localidad 3489: Georef 82049150; departamento 82049; localidad censal 82049150.
-- Localidad 3490: Georef 82112080; departamento 82112; localidad censal 82112080.
-- Localidad 3491: Georef 82133110; departamento 82133; localidad censal 82133110.
-- Localidad 3492: Georef 82021250; departamento 82021; localidad censal 82021250.
-- Localidad 3493: Georef 82070140; departamento 82070; localidad censal 82070140.
-- Localidad 3494: Georef 82126120; departamento 82126; localidad censal 82126120.
-- Localidad 3495: Georef 82042180; departamento 82042; localidad censal 82042180.
-- Localidad 3496: Georef 82070150; departamento 82070; localidad censal 82070150.
-- Localidad 3497: Georef 82028140; departamento 82028; localidad censal 82028140.
-- Localidad 3498: Georef 82042190; departamento 82042; localidad censal 82042190.
-- Localidad 3499: Georef 82042200; departamento 82042; localidad censal 82042200.
-- Localidad 3500: Georef 82091180; departamento 82091; localidad censal 82091180.
-- Localidad 3501: Georef 82091190; departamento 82091; localidad censal 82091190.
-- Localidad 3502: Georef 82105200; departamento 82105; localidad censal 82105200.
-- Localidad 3503: Georef 82084200; departamento 82084; localidad censal 82084200.
-- Localidad 3504: Georef 82063100; departamento 82063; localidad censal 82063100.
-- Localidad 3505: Georef 82077050; departamento 82077; localidad censal 82077050.
-- Localidad 3506: Georef 82007050; departamento 82007; localidad censal 82007050.
-- Localidad 3507: Georef 82042210; departamento 82042; localidad censal 82042210.
-- Localidad 3508: Georef 82091200; departamento 82091; localidad censal 82091200.
-- Localidad 3509: Georef 82112090; departamento 82112; localidad censal 82112090.
-- Localidad 3510: Georef 82063110; departamento 82063; localidad censal 82063110.
-- Localidad 3511: Georef 82021260; departamento 82021; localidad censal 82021260.
-- Localidad 3512: Georef 82070160; departamento 82070; localidad censal 82070160.
-- Localidad 3513: Georef 82056100; departamento 82056; localidad censal 82056100.
-- Localidad 3514: Georef 82091210; departamento 82091; localidad censal 82091210.
-- Localidad 3515: Georef 82133120; departamento 82133; localidad censal 82133120.
-- Localidad 3516: Georef 82063120; departamento 82063; localidad censal 82063120.
-- Localidad 3517: Georef 82063130; departamento 82063; localidad censal 82063130.
-- Localidad 3518: Georef 82049160; departamento 82049; localidad censal 82049160.
-- Localidad 3519: Georef 82028150; departamento 82028; localidad censal 82028150.
-- Localidad 3520: Georef 82028160; departamento 82028; localidad censal 82028160.
-- Localidad 3521: Georef 82112100; departamento 82112; localidad censal 82112100.
-- Localidad 3522: Georef 82084210; departamento 82084; localidad censal 82084210.
-- Localidad 3523: Georef 82028170; departamento 82028; localidad censal 82028170.
-- Localidad 3524: Georef 82126130; departamento 82126; localidad censal 82126130.
-- Localidad 3525: Georef 82070170; departamento 82070; localidad censal 82070170.
-- Localidad 3526: Georef 82084220; departamento 82084; localidad censal 82084220.
-- Localidad 3527: Georef 82021270; departamento 82021; localidad censal 82021270.
-- Localidad 3528: Georef 82070180; departamento 82070; localidad censal 82070180.
-- Localidad 3529: Georef 82021280; departamento 82021; localidad censal 82021280.
-- Localidad 3530: Georef 82077060; departamento 82077; localidad censal 82077060.
-- Localidad 3531: Georef 82133130; departamento 82133; localidad censal 82133130.
-- Localidad 3532: Georef 82021290; departamento 82021; localidad censal 82021290.
-- Localidad 3533: Georef 82070190; departamento 82070; localidad censal 82070190.
-- Localidad 3534: Georef 82070200; departamento 82070; localidad censal 82070200.
-- Localidad 3535: Georef 82056110; departamento 82056; localidad censal 82056110.
-- Localidad 3536: Georef 82084230; departamento 82084; localidad censal 82084230.
-- Localidad 3537: Georef 82021300; departamento 82021; localidad censal 82021300.
-- Localidad 3538: Georef 82084240; departamento 82084; localidad censal 82084240.
-- Localidad 3539: Georef 82133140; departamento 82133; localidad censal 82133140.
-- Localidad 3540: Georef 82084250; departamento 82084; localidad censal 82084250.
-- Localidad 3541: Georef 82105210; departamento 82105; localidad censal 82105210.
-- Localidad 3542: Georef 82084260; departamento 82084; localidad censal 82084260.
-- Localidad 3543: Georef 82119080; departamento 82119; localidad censal 82119080.
-- Localidad 3544: Georef 82049170; departamento 82049; localidad censal 82049170.
-- Localidad 3545: Georef 82119090; departamento 82119; localidad censal 82119090.
-- Localidad 3546: Georef 82021310; departamento 82021; localidad censal 82021310.
-- Localidad 3547: Georef 82112110; departamento 82112; localidad censal 82112110.
-- Localidad 3548: Georef 82021320; departamento 82021; localidad censal 82021320.
-- Localidad 3549: Georef 82049180; departamento 82049; localidad censal 82049180.
-- Localidad 3550: Georef 82063140; departamento 82063; localidad censal 82063140.
-- Localidad 3551: Georef 82119100; departamento 82119; localidad censal 82119100.
-- Localidad 3552: Georef 8206303002; departamento 82063; localidad censal 82063030.
-- Localidad 3553: Georef 82063150; departamento 82063; localidad censal 82063150.
-- Localidad 3554: Georef 82119110; departamento 82119; localidad censal 82119110.
-- Localidad 3555: Georef 82098050; departamento 82098; localidad censal 82098050.
-- Localidad 3556: Georef 82084270; departamento 82084; localidad censal 82084270.
-- Localidad 3557: Georef 82028180; departamento 82028; localidad censal 82028180.
-- Localidad 3558: Georef 82042220; departamento 82042; localidad censal 82042220.
-- Localidad 3559: Georef 82070210; departamento 82070; localidad censal 82070210.
-- Localidad 3560: Georef 82035040; departamento 82035; localidad censal 82035040.
-- Localidad 3561: Georef 82056120; departamento 82056; localidad censal 82056120.
-- Localidad 3562: Georef 82070220; departamento 82070; localidad censal 82070220.
-- Localidad 3563: Georef 82021330; departamento 82021; localidad censal 82021330.
-- Localidad 3564: Georef 82049190; departamento 82049; localidad censal 82049190.
-- Localidad 3565: Georef 82077065; departamento 82077; localidad censal 82077065.
-- Localidad 3566: Georef 82112120; departamento 82112; localidad censal 82112120.
-- Localidad 3567: Georef 82070230; departamento 82070; localidad censal 82070230.
-- Localidad 3568: Georef 82070240; departamento 82070; localidad censal 82070240.
-- Localidad 3569: Georef 82070250; departamento 82070; localidad censal 82070250.
-- Localidad 3570: Georef 82091220; departamento 82091; localidad censal 82091220.
-- Localidad 3571: Georef 82042230; departamento 82042; localidad censal 82042230.
-- Localidad 3572: Georef 82105220; departamento 82105; localidad censal 82105220.
-- Localidad 3573: Georef 82105230; departamento 82105; localidad censal 82105230.
-- Localidad 3574: Georef 82042240; departamento 82042; localidad censal 82042240.
-- Localidad 3575: Georef 82105240; departamento 82105; localidad censal 82105240.
-- Localidad 3576: Georef 82042250; departamento 82042; localidad censal 82042250.
-- Localidad 3577: Georef 82091230; departamento 82091; localidad censal 82091230.
-- Localidad 3578: Georef 82098060; departamento 82098; localidad censal 82098060.
-- Localidad 3579: Georef 82070260; departamento 82070; localidad censal 82070260.
-- Localidad 3580: Georef 82070270; departamento 82070; localidad censal 82070270.
-- Localidad 3581: Georef 82119120; departamento 82119; localidad censal 82119120.
-- Localidad 3582: Georef 82126140; departamento 82126; localidad censal 82126140.
-- Localidad 3583: Georef 82014120; departamento 82014; localidad censal 82014120.
-- Localidad 3584: Georef 82063160; departamento 82063; localidad censal 82063160.
-- Localidad 3585: Georef 82112130; departamento 82112; localidad censal 82112130.
-- Localidad 3586: Georef 82119130; departamento 82119; localidad censal 82119130.
-- Localidad 3587: Georef 82070280; departamento 82070; localidad censal 82070280.
-- Localidad 3588: Georef 82126150; departamento 82126; localidad censal 82126150.
-- Localidad 3589: Georef 82112140; departamento 82112; localidad censal 82112140.
-- Localidad 3590: Georef 82021340; departamento 82021; localidad censal 82021340.
-- Localidad 3591: Georef 82042260; departamento 82042; localidad censal 82042260.
-- Localidad 3592: Georef 82014130; departamento 82014; localidad censal 82014130.
-- Localidad 3593: Georef 82070290; departamento 82070; localidad censal 82070290.
-- Localidad 3594: Georef 82021350; departamento 82021; localidad censal 82021350.
-- Localidad 3595: Georef 82063170; departamento 82063; localidad censal 82063170.
-- Localidad 3596: Georef 82042270; departamento 82042; localidad censal 82042270.
-- Localidad 3597: Georef 82077070; departamento 82077; localidad censal 82077070.
-- Localidad 3598: Georef 82035050; departamento 82035; localidad censal 82035050.
-- Localidad 3599: Georef 82028190; departamento 82028; localidad censal 82028190.
-- Localidad 3600: Georef 82070300; departamento 82070; localidad censal 82070300.
-- Localidad 3601: Georef 82063180; departamento 82063; localidad censal 82063180.
-- Localidad 3602: Georef 82091240; departamento 82091; localidad censal 82091240.
-- Localidad 3603: Georef 82028200; departamento 82028; localidad censal 82028200.
-- Localidad 3604: Georef 82070310; departamento 82070; localidad censal 82070310.
-- Localidad 3605: Georef 82126160; departamento 82126; localidad censal 82126160.
-- Localidad 3606: Georef 82063190; departamento 82063; localidad censal 82063190.
-- Localidad 3607: Georef 82056130; departamento 82056; localidad censal 82056130.
-- Localidad 3608: Georef 82112150; departamento 82112; localidad censal 82112150.
-- Localidad 3609: Georef 82084280; departamento 82084; localidad censal 82084280.
-- Localidad 3610: Georef 82091250; departamento 82091; localidad censal 82091250.
-- Localidad 3611: Georef 82028210; departamento 82028; localidad censal 82028210.
-- Localidad 3612: Georef 82091260; departamento 82091; localidad censal 82091260.
-- Localidad 3613: Georef 82021360; departamento 82021; localidad censal 82021360.
-- Localidad 3614: Georef 82021370; departamento 82021; localidad censal 82021370.
-- Localidad 3615: Georef 82049200; departamento 82049; localidad censal 82049200.
-- Localidad 3616: Georef 82021380; departamento 82021; localidad censal 82021380.
-- Localidad 3617: Georef 82133150; departamento 82133; localidad censal 82133150.
-- Localidad 3618: Georef 82042280; departamento 82042; localidad censal 82042280.
-- Localidad 3619: Georef 82028220; departamento 82028; localidad censal 82028220.
-- Localidad 3620: Georef 82119140; departamento 82119; localidad censal 82119140.
-- Localidad 3621: Georef 82133160; departamento 82133; localidad censal 82133160.
-- Localidad 3622: Georef 82007060; departamento 82007; localidad censal 82007060.
-- Localidad 3623: Georef 82077080; departamento 82077; localidad censal 82077080.
-- Localidad 3624: Georef 82056140; departamento 82056; localidad censal 82056140.
-- Localidad 3625: Georef 82126170; departamento 82126; localidad censal 82126170.
-- Localidad 3626: Georef 82042290; departamento 82042; localidad censal 82042290.
-- Localidad 3627: Georef 82133170; departamento 82133; localidad censal 82133170.
-- Localidad 3628: Georef 82112160; departamento 82112; localidad censal 82112160.
-- Localidad 3629: Georef 82112170; departamento 82112; localidad censal 82112170.
-- Localidad 3630: Georef 82021390; departamento 82021; localidad censal 82021390.
-- Localidad 3631: Georef 8206319002; departamento 82063; localidad censal 82063190.
-- Localidad 3632: Georef 82084290; departamento 82084; localidad censal 82084290.
-- Localidad 3633: Georef 82049210; departamento 82049; localidad censal 82049210.
-- Localidad 3634: Georef 82042300; departamento 82042; localidad censal 82042300.
-- Localidad 3635: Georef 82028230; departamento 82028; localidad censal 82028230.
-- Localidad 3636: Georef 82084300; departamento 82084; localidad censal 82084300.
-- Localidad 3637: Georef 82056150; departamento 82056; localidad censal 82056150.
-- Localidad 3638: Georef 82119150; departamento 82119; localidad censal 82119150.
-- Localidad 3639: Georef 82084310; departamento 82084; localidad censal 82084310.
-- Localidad 3640: Georef 82049220; departamento 82049; localidad censal 82049220.
-- Localidad 3641: Georef 82021400; departamento 82021; localidad censal 82021400.
-- Localidad 3642: Georef 82056160; departamento 82056; localidad censal 82056160.
-- Localidad 3643: Georef 82056170; departamento 82056; localidad censal 82056170.
-- Localidad 3644: Georef 82063200; departamento 82063; localidad censal 82063200.
-- Localidad 3645: Georef 82077090; departamento 82077; localidad censal 82077090.
-- Localidad 3646: Georef 82119160; departamento 82119; localidad censal 82119160.
-- Localidad 3647: Georef 82049230; departamento 82049; localidad censal 82049230.
-- Localidad 3648: Georef 82021410; departamento 82021; localidad censal 82021410.
-- Localidad 3649: Georef 82091270; departamento 82091; localidad censal 82091270.
-- Localidad 3650: Georef 82091280; departamento 82091; localidad censal 82091280.
-- Localidad 3651: Georef 82014140; departamento 82014; localidad censal 82014140.
-- Localidad 3652: Georef 82021420; departamento 82021; localidad censal 82021420.
-- Localidad 3653: Georef 82042310; departamento 82042; localidad censal 82042310.
-- Localidad 3654: Georef 82126180; departamento 82126; localidad censal 82126180.
-- Localidad 3655: Georef 82084320; departamento 82084; localidad censal 82084320.
-- Localidad 3656: Georef 82021430; departamento 82021; localidad censal 82021430.
-- Localidad 3657: Georef 86035010; departamento 86035; localidad censal 86035010.
-- Localidad 3658: Georef 86119010; departamento 86119; localidad censal 86119010.
-- Localidad 3659: Georef 86119020; departamento 86119; localidad censal 86119020.
-- Localidad 3660: Georef 86077010; departamento 86077; localidad censal 86077010.
-- Localidad 3661: Georef 86063010; departamento 86063; localidad censal 86063010.
-- Localidad 3662: Georef 86035020; departamento 86035; localidad censal 86035020.
-- Localidad 3663: Georef 86035030; departamento 86035; localidad censal 86035030.
-- Localidad 3664: Georef 86007010; departamento 86007; localidad censal 86007010.
-- Localidad 3665: Georef 86189010; departamento 86189; localidad censal 86189010.
-- Localidad 3666: Georef 86077020; departamento 86077; localidad censal 86077020.
-- Localidad 3667: Georef 86042010; departamento 86042; localidad censal 86042010.
-- Localidad 3668: Georef 86070010; departamento 86070; localidad censal 86070010.
-- Localidad 3669: Georef 86161010; departamento 86161; localidad censal 86161010.
-- Localidad 3670: Georef 86175010; departamento 86175; localidad censal 86175010.
-- Localidad 3671: Georef 86014010; departamento 86014; localidad censal 86014010.
-- Localidad 3672: Georef 86035040; departamento 86035; localidad censal 86035040.
-- Localidad 3673: Georef 86007020; departamento 86007; localidad censal 86007020.
-- Localidad 3674: Georef 86070020; departamento 86070; localidad censal 86070020.
-- Localidad 3675: Georef 86147020; departamento 86147; localidad censal 86147020.
-- Localidad 3676: Georef 86035050; departamento 86035; localidad censal 86035050.
-- Localidad 3677: Georef 86168010; departamento 86168; localidad censal 86168010.
-- Localidad 3678: Georef 86063020; departamento 86063; localidad censal 86063020.
-- Localidad 3679: Georef 86035060; departamento 86035; localidad censal 86035060.
-- Localidad 3680: Georef 86154010; departamento 86154; localidad censal 86154010.
-- Localidad 3681: Georef 86028010; departamento 86028; localidad censal 86028010.
-- Localidad 3682: Georef 86161020; departamento 86161; localidad censal 86161020.
-- Localidad 3683: Georef 86070030; departamento 86070; localidad censal 86070030.
-- Localidad 3684: Georef 86147030; departamento 86147; localidad censal 86147030.
-- Localidad 3685: Georef 86014020; departamento 86014; localidad censal 86014020.
-- Localidad 3686: Georef 86042020; departamento 86042; localidad censal 86042020.
-- Localidad 3687: Georef 86014030; departamento 86014; localidad censal 86014030.
-- Localidad 3688: Georef 86126010; departamento 86126; localidad censal 86126010.
-- Localidad 3689: Georef 86091005; departamento 86091; localidad censal 86091005.
-- Localidad 3690: Georef 86091010; departamento 86091; localidad censal 86091010.
-- Localidad 3691: Georef 86056010; departamento 86056; localidad censal 86056010.
-- Localidad 3692: Georef 86091020; departamento 86091; localidad censal 86091020.
-- Localidad 3693: Georef 86147040; departamento 86147; localidad censal 86147040.
-- Localidad 3694: Georef 86098010; departamento 86098; localidad censal 86098010.
-- Localidad 3695: Georef 86070040; departamento 86070; localidad censal 86070040.
-- Localidad 3696: Georef 86098020; departamento 86098; localidad censal 86098020.
-- Localidad 3697: Georef 86049010; departamento 86049; localidad censal 86049010.
-- Localidad 3698: Georef 86049020; departamento 86049; localidad censal 86049020.
-- Localidad 3699: Georef 86133010; departamento 86133; localidad censal 86133010.
-- Localidad 3700: Georef 86091030; departamento 86091; localidad censal 86091030.
-- Localidad 3701: Georef 86147120; departamento 86147; localidad censal 86147120.
-- Localidad 3702: Georef 86049030; departamento 86049; localidad censal 86049030.
-- Localidad 3703: Georef 86021010; departamento 86021; localidad censal 86021010.
-- Localidad 3704: Georef 86063030; departamento 86063; localidad censal 86063030.
-- Localidad 3705: Georef 86175020; departamento 86175; localidad censal 86175020.
-- Localidad 3706: Georef 86175030; departamento 86175; localidad censal 86175030.
-- Localidad 3707: Georef 86077030; departamento 86077; localidad censal 86077030.
-- Localidad 3708: Georef 86161030; departamento 86161; localidad censal 86161030.
-- Localidad 3709: Georef 86042030; departamento 86042; localidad censal 86042030.
-- Localidad 3710: Georef 86063040; departamento 86063; localidad censal 86063040.
-- Localidad 3711: Georef 86182010; departamento 86182; localidad censal 86182010.
-- Localidad 3712: Georef 86091040; departamento 86091; localidad censal 86091040.
-- Localidad 3713: Georef 86147050; departamento 86147; localidad censal 86147050.
-- Localidad 3714: Georef 86042040; departamento 86042; localidad censal 86042040.
-- Localidad 3715: Georef 86119030; departamento 86119; localidad censal 86119030.
-- Localidad 3716: Georef 86119040; departamento 86119; localidad censal 86119040.
-- Localidad 3717: Georef 86028020; departamento 86028; localidad censal 86028020.
-- Localidad 3718: Georef 86035070; departamento 86035; localidad censal 86035070.
-- Localidad 3719: Georef 86028030; departamento 86028; localidad censal 86028030.
-- Localidad 3720: Georef 86161040; departamento 86161; localidad censal 86161040.
-- Localidad 3721: Georef 86091050; departamento 86091; localidad censal 86091050.
-- Localidad 3722: Georef 86091060; departamento 86091; localidad censal 86091060.
-- Localidad 3723: Georef 86035080; departamento 86035; localidad censal 86035080.
-- Localidad 3724: Georef 86035090; departamento 86035; localidad censal 86035090.
-- Localidad 3725: Georef 86070060; departamento 86070; localidad censal 86070060.
-- Localidad 3726: Georef 86035100; departamento 86035; localidad censal 86035100.
-- Localidad 3727: Georef 86077040; departamento 86077; localidad censal 86077040.
-- Localidad 3728: Georef 86147060; departamento 86147; localidad censal 86147060.
-- Localidad 3729: Georef 86063050; departamento 86063; localidad censal 86063050.
-- Localidad 3730: Georef 86133020; departamento 86133; localidad censal 86133020.
-- Localidad 3731: Georef 86119050; departamento 86119; localidad censal 86119050.
-- Localidad 3732: Georef 86084010; departamento 86084; localidad censal 86084010.
-- Localidad 3733: Georef 86119060; departamento 86119; localidad censal 86119060.
-- Localidad 3734: Georef 86119070; departamento 86119; localidad censal 86119070.
-- Localidad 3735: Georef 86049040; departamento 86049; localidad censal 86049040.
-- Localidad 3736: Georef 86077050; departamento 86077; localidad censal 86077050.
-- Localidad 3737: Georef 86147070; departamento 86147; localidad censal 86147070.
-- Localidad 3738: Georef 86147080; departamento 86147; localidad censal 86147080.
-- Localidad 3739: Georef 86056030; departamento 86056; localidad censal 86056030.
-- Localidad 3740: Georef 86035110; departamento 86035; localidad censal 86035110.
-- Localidad 3741: Georef 86035120; departamento 86035; localidad censal 86035120.
-- Localidad 3742: Georef 86168020; departamento 86168; localidad censal 86168020.
-- Localidad 3743: Georef 86056040; departamento 86056; localidad censal 86056040.
-- Localidad 3744: Georef 86028040; departamento 86028; localidad censal 86028040.
-- Localidad 3745: Georef 86049050; departamento 86049; localidad censal 86049050.
-- Localidad 3746: Georef 86007030; departamento 86007; localidad censal 86007030.
-- Localidad 3747: Georef 86147090; departamento 86147; localidad censal 86147090.
-- Localidad 3748: Georef 86049060; departamento 86049; localidad censal 86049060.
-- Localidad 3749: Georef 86098030; departamento 86098; localidad censal 86098030.
-- Localidad 3750: Georef 86021020; departamento 86021; localidad censal 86021020.
-- Localidad 3751: Georef 86070080; departamento 86070; localidad censal 86070080.
-- Localidad 3752: Georef 86056050; departamento 86056; localidad censal 86056050.
-- Localidad 3753: Georef 86049070; departamento 86049; localidad censal 86049070.
-- Localidad 3754: Georef 86133030; departamento 86133; localidad censal 86133030.
-- Localidad 3755: Georef 86189020; departamento 86189; localidad censal 86189020.
-- Localidad 3756: Georef 86154020; departamento 86154; localidad censal 86154020.
-- Localidad 3757: Georef 86056070; departamento 86056; localidad censal 86056070.
-- Localidad 3758: Georef 86119080; departamento 86119; localidad censal 86119080.
-- Localidad 3759: Georef 86133040; departamento 86133; localidad censal 86133040.
-- Localidad 3760: Georef 86091070; departamento 86091; localidad censal 86091070.
-- Localidad 3761: Georef 86147100; departamento 86147; localidad censal 86147100.
-- Localidad 3762: Georef 86119090; departamento 86119; localidad censal 86119090.
-- Localidad 3763: Georef 86049080; departamento 86049; localidad censal 86049080.
-- Localidad 3764: Georef 86119100; departamento 86119; localidad censal 86119100.
-- Localidad 3765: Georef 86140010; departamento 86140; localidad censal 86140010.
-- Localidad 3766: Georef 86133050; departamento 86133; localidad censal 86133050.
-- Localidad 3767: Georef 86028050; departamento 86028; localidad censal 86028050.
-- Localidad 3768: Georef 86147110; departamento 86147; localidad censal 86147110.
-- Localidad 3769: Georef 86119110; departamento 86119; localidad censal 86119110.
-- Localidad 3770: Georef 86014040; departamento 86014; localidad censal 86014040.
-- Localidad 3771: Georef 86056080; departamento 86056; localidad censal 86056080.
-- Localidad 3772: Georef 86049090; departamento 86049; localidad censal 86049090.
-- Localidad 3773: Georef 86063070; departamento 86063; localidad censal 86063070.
-- Localidad 3774: Georef 86084020; departamento 86084; localidad censal 86084020.
-- Localidad 3775: Georef 86091080; departamento 86091; localidad censal 86091080.
-- Localidad 3776: Georef 86049100; departamento 86049; localidad censal 86049100.
-- Localidad 3777: Georef 86049110; departamento 86049; localidad censal 86049110.
-- Localidad 3778: Georef 86133060; departamento 86133; localidad censal 86133060.
-- Localidad 3779: Georef 86014050; departamento 86014; localidad censal 86014050.
-- Localidad 3780: Georef 86154030; departamento 86154; localidad censal 86154030.
-- Localidad 3781: Georef 86189030; departamento 86189; localidad censal 86189030.
-- Localidad 3782: Georef 86035130; departamento 86035; localidad censal 86035130.
-- Localidad 3783: Georef 86126020; departamento 86126; localidad censal 86126020.
-- Localidad 3784: Georef 86189040; departamento 86189; localidad censal 86189040.
-- Localidad 3785: Georef 86140020; departamento 86140; localidad censal 86140020.
-- Localidad 3786: Georef 86140030; departamento 86140; localidad censal 86140030.
-- Localidad 3787: Georef 86098040; departamento 86098; localidad censal 86098040.
-- Localidad 3788: Georef 86063080; departamento 86063; localidad censal 86063080.
-- Localidad 3789: Georef 86147130; departamento 86147; localidad censal 86147130.
-- Localidad 3790: Georef 86119120; departamento 86119; localidad censal 86119120.
-- Localidad 3791: Georef 86077060; departamento 86077; localidad censal 86077060.
-- Localidad 3792: Georef 86035140; departamento 86035; localidad censal 86035140.
-- Localidad 3793: Georef 86035150; departamento 86035; localidad censal 86035150.
-- Localidad 3794: Georef 86056090; departamento 86056; localidad censal 86056090.
-- Localidad 3795: Georef 86070090; departamento 86070; localidad censal 86070090.
-- Localidad 3796: Georef 86098050; departamento 86098; localidad censal 86098050.
-- Localidad 3797: Georef 86021030; departamento 86021; localidad censal 86021030.
-- Localidad 3798: Georef 86070100; departamento 86070; localidad censal 86070100.
-- Localidad 3799: Georef 86007040; departamento 86007; localidad censal 86007040.
-- Localidad 3800: Georef 86147140; departamento 86147; localidad censal 86147140.
-- Localidad 3801: Georef 86063090; departamento 86063; localidad censal 86063090.
-- Localidad 3802: Georef 86028060; departamento 86028; localidad censal 86028060.
-- Localidad 3803: Georef 86175040; departamento 86175; localidad censal 86175040.
-- Localidad 3804: Georef 86126030; departamento 86126; localidad censal 86126030.
-- Localidad 3805: Georef 86147150; departamento 86147; localidad censal 86147150.
-- Localidad 3806: Georef 86168030; departamento 86168; localidad censal 86168030.
-- Localidad 3807: Georef 86105010; departamento 86105; localidad censal 86105010.
-- Localidad 3808: Georef 86189050; departamento 86189; localidad censal 86189050.
-- Localidad 3809: Georef 86147160; departamento 86147; localidad censal 86147160.
-- Localidad 3810: Georef 86112010; departamento 86112; localidad censal 86112010.
-- Localidad 3811: Georef 86161050; departamento 86161; localidad censal 86161050.
-- Localidad 3812: Georef 86147170; departamento 86147; localidad censal 86147170.
-- Localidad 3813: Georef 86049120; departamento 86049; localidad censal 86049120.
-- Localidad 3814: Georef 86119130; departamento 86119; localidad censal 86119130.
-- Localidad 3815: Georef 86049130; departamento 86049; localidad censal 86049130.
-- Localidad 3816: Georef 86098060; departamento 86098; localidad censal 86098060.
-- Localidad 3817: Georef 90007080; departamento 90007; localidad censal 90007080.
-- Localidad 3818: Georef 90070010; departamento 90070; localidad censal 90070010.
-- Localidad 3819: Georef 90077010; departamento 90077; localidad censal 90077010.
-- Localidad 3820: Georef 90014010; departamento 90014; localidad censal 90014010.
-- Localidad 3821: Georef 90021010; departamento 90021; localidad censal 90021010.
-- Localidad 3822: Georef 90021020; departamento 90021; localidad censal 90021020.
-- Localidad 3823: Georef 90098010; departamento 90098; localidad censal 90098010.
-- Localidad 3824: Georef 90021030; departamento 90021; localidad censal 90021030.
-- Localidad 3825: Georef 90091010; departamento 90091; localidad censal 90091010.
-- Localidad 3826: Georef 90014020; departamento 90014; localidad censal 90014020.
-- Localidad 3827: Georef 9001402002; departamento 90014; localidad censal 90014020.
-- Localidad 3828: Georef 9006302001; departamento 90063; localidad censal 90063020.
-- Localidad 3829: Georef 90105030; departamento 90105; localidad censal 90105030.
-- Localidad 3830: Georef 90063010; departamento 90063; localidad censal 90063010.
-- Localidad 3831: Georef 90007010; departamento 90007; localidad censal 90007010.
-- Localidad 3832: Georef 90056010; departamento 90056; localidad censal 90056010.
-- Localidad 3833: Georef 90028020; departamento 90028; localidad censal 90028020.
-- Localidad 3834: Georef 90070020; departamento 90070; localidad censal 90070020.
-- Localidad 3835: Georef 90112010; departamento 90112; localidad censal 90112010.
-- Localidad 3836: Georef 90098020; departamento 90098; localidad censal 90098020.
-- Localidad 3837: Georef 90014040; departamento 90014; localidad censal 90014040.
-- Localidad 3838: Georef 90014050; departamento 90014; localidad censal 90014050.
-- Localidad 3839: Georef 90021050; departamento 90021; localidad censal 90021050.
-- Localidad 3840: Georef 90014060; departamento 90014; localidad censal 90014060.
-- Localidad 3841: Georef 90014070; departamento 90014; localidad censal 90014070.
-- Localidad 3842: Georef 90105070; departamento 90105; localidad censal 90105070.
-- Localidad 3843: Georef 90007020; departamento 90007; localidad censal 90007020.
-- Localidad 3844: Georef 9001401002; departamento 90014; localidad censal 90014010.
-- Localidad 3845: Georef 90063020; departamento 90063; localidad censal 90063020.
-- Localidad 3846: Georef 90098030; departamento 90098; localidad censal 90098030.
-- Localidad 3847: Georef 90007030; departamento 90007; localidad censal 90007030.
-- Localidad 3848: Georef 9001406001; departamento 90014; localidad censal 90014060.
-- Localidad 3849: Georef 90056020; departamento 90056; localidad censal 90056020.
-- Localidad 3850: Georef 9001406002; departamento 90014; localidad censal 90014060.
-- Localidad 3851: Georef 9001410001; departamento 90014; localidad censal 90014100.
-- Localidad 3852: Georef 9001406003; departamento 90014; localidad censal 90014060.
-- Localidad 3853: Georef 9002803001; departamento 90028; localidad censal 90028030.
-- Localidad 3854: Georef 9011903001; departamento 90119; localidad censal 90119030.
-- Localidad 3855: Georef 90028030; departamento 90028; localidad censal 90028030.
-- Localidad 3856: Georef 90007040; departamento 90007; localidad censal 90007040.
-- Localidad 3857: Georef 90035010; departamento 90035; localidad censal 90035010.
-- Localidad 3858: Georef 90021060; departamento 90021; localidad censal 90021060.
-- Localidad 3859: Georef 90028040; departamento 90028; localidad censal 90028040.
-- Localidad 3860: Georef 9001408001; departamento 90014; localidad censal 90014080.
-- Localidad 3861: Georef 90063030; departamento 90063; localidad censal 90063030.
-- Localidad 3862: Georef 9007701002; departamento 90077; localidad censal 90077010.
-- Localidad 3863: Georef 90042010; departamento 90042; localidad censal 90042010.
-- Localidad 3864: Georef 90049010; departamento 90049; localidad censal 90049010.
-- Localidad 3865: Georef 90014080; departamento 90014; localidad censal 90014080.
-- Localidad 3866: Georef 90007050; departamento 90007; localidad censal 90007050.
-- Localidad 3867: Georef 90063040; departamento 90063; localidad censal 90063040.
-- Localidad 3868: Georef 90021070; departamento 90021; localidad censal 90021070.
-- Localidad 3869: Georef 90035020; departamento 90035; localidad censal 90035020.
-- Localidad 3870: Georef 90014090; departamento 90014; localidad censal 90014090.
-- Localidad 3871: Georef 9001402003; departamento 90014; localidad censal 90014020.
-- Localidad 3872: Georef 9001401003; departamento 90014; localidad censal 90014010.
-- Localidad 3873: Georef 90056030; departamento 90056; localidad censal 90056030.
-- Localidad 3874: Georef 90014100; departamento 90014; localidad censal 90014100.
-- Localidad 3875: Georef 90077020; departamento 90077; localidad censal 90077020.
-- Localidad 3876: Georef 90063050; departamento 90063; localidad censal 90063050.
-- Localidad 3877: Georef 90007060; departamento 90007; localidad censal 90007060.
-- Localidad 3878: Georef 90056040; departamento 90056; localidad censal 90056040.
-- Localidad 3879: Georef 90021080; departamento 90021; localidad censal 90021080.
-- Localidad 3880: Georef 90091020; departamento 90091; localidad censal 90091020.
-- Localidad 3881: Georef 90070030; departamento 90070; localidad censal 90070030.
-- Localidad 3882: Georef 90091030; departamento 90091; localidad censal 90091030.
-- Localidad 3883: Georef 90014110; departamento 90014; localidad censal 90014110.
-- Localidad 3884: Georef 90007070; departamento 90007; localidad censal 90007070.
-- Localidad 3885: Georef 90070040; departamento 90070; localidad censal 90070040.
-- Localidad 3886: Georef 90014120; departamento 90014; localidad censal 90014120.
-- Localidad 3887: Georef 90077030; departamento 90077; localidad censal 90077030.
-- Localidad 3888: Georef 90056060; departamento 90056; localidad censal 90056060.
-- Localidad 3889: Georef 90070050; departamento 90070; localidad censal 90070050.
-- Localidad 3890: Georef 90014130; departamento 90014; localidad censal 90014130.
-- Localidad 3891: Georef 90049020; departamento 90049; localidad censal 90049020.
-- Localidad 3892: Georef 90084010; departamento 90084; localidad censal 90084010.
-- Localidad 3893: Georef 90112020; departamento 90112; localidad censal 90112020.
-- Localidad 3894: Georef 90077040; departamento 90077; localidad censal 90077040.
-- Localidad 3895: Georef 90091040; departamento 90091; localidad censal 90091040.
-- Localidad 3896: Georef 90070060; departamento 90070; localidad censal 90070060.
-- Localidad 3897: Georef 90056070; departamento 90056; localidad censal 90056070.
-- Localidad 3898: Georef 90070070; departamento 90070; localidad censal 90070070.
-- Localidad 3899: Georef 90091050; departamento 90091; localidad censal 90091050.
-- Localidad 3900: Georef 90070080; departamento 90070; localidad censal 90070080.
-- Localidad 3901: Georef 90035030; departamento 90035; localidad censal 90035030.
-- Localidad 3902: Georef 90098040; departamento 90098; localidad censal 90098040.
-- Localidad 3903: Georef 90105080; departamento 90105; localidad censal 90105080.
-- Localidad 3904: Georef 90070090; departamento 90070; localidad censal 90070090.
-- Localidad 3905: Georef 90042020; departamento 90042; localidad censal 90042020.
-- Localidad 3906: Georef 90007090; departamento 90007; localidad censal 90007090.
-- Localidad 3907: Georef 90007100; departamento 90007; localidad censal 90007100.
-- Localidad 3908: Georef 90119020; departamento 90119; localidad censal 90119020.
-- Localidad 3909: Georef 90091060; departamento 90091; localidad censal 90091060.
-- Localidad 3910: Georef 90077050; departamento 90077; localidad censal 90077050.
-- Localidad 3911: Georef 90056090; departamento 90056; localidad censal 90056090.
-- Localidad 3912: Georef 90112030; departamento 90112; localidad censal 90112030.
-- Localidad 3913: Georef 90056080; departamento 90056; localidad censal 90056080.
-- Localidad 3914: Georef 90105100; departamento 90105; localidad censal 90105100.
-- Localidad 3915: Georef 90007110; departamento 90007; localidad censal 90007110.
-- Localidad 3916: Georef 90070100; departamento 90070; localidad censal 90070100.
-- Localidad 3917: Georef 9001410002; departamento 90014; localidad censal 90014100.
-- Localidad 3918: Georef 9001410003; departamento 90014; localidad censal 90014100.
-- Localidad 3919: Georef 90119030; departamento 90119; localidad censal 90119030.
-- Localidad 3920: Georef 94015010; departamento 94015; localidad censal 94015010.
-- Localidad 3921: Georef 94021010; departamento 94021; localidad censal 94021010.
-- Localidad 3922: Georef 94008010; departamento 94008; localidad censal 94008010.
-- Localidad 3923: Georef 94015020; departamento 94015; localidad censal 94015020.
