SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_invoices`
--

CREATE TABLE IF NOT EXISTS `api_invoices` (
  `id_invoice` int(11) NOT NULL AUTO_INCREMENT,
  `id_service` int(11) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount` double NOT NULL,
  `payment_name` varchar(32) NOT NULL,
  `payment_status` varchar(10) NOT NULL,
  `payment_reference` varchar(128) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_expire` datetime NOT NULL,
  `log_payment` text NOT NULL,
  PRIMARY KEY (`id_invoice`),
  UNIQUE KEY `payment_reference` (`payment_reference`),
  KEY `id_service` (`id_service`),
  KEY `payment_status` (`payment_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17  COMMENT='Facturas de Cobro por API';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_planes`
--

CREATE TABLE IF NOT EXISTS `api_planes` (
  `id_plan` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(128) NOT NULL,
  `periocidad` int(11) NOT NULL,
  `max_request_per_hour` int(11) DEFAULT NULL,
  `show_names` tinyint(1) NOT NULL,
  `show_rif` tinyint(1) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_plan`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 COMMENT='Planes que estan a la venta';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_precio_planes`
--

CREATE TABLE IF NOT EXISTS `api_precio_planes` (
  `id_precio_plan` int(11) NOT NULL AUTO_INCREMENT,
  `id_plan` int(11) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount` double NOT NULL,
  PRIMARY KEY (`id_precio_plan`),
  KEY `id_plan` (`id_plan`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11   COMMENT='Tabla de Precios por Hora';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_requests`
--

CREATE TABLE IF NOT EXISTS `api_requests` (
  `id_request` int(11) NOT NULL AUTO_INCREMENT,
  `id_service` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` tinyint(4) NOT NULL,
  `num_request` int(11) NOT NULL,
  PRIMARY KEY (`id_request`),
  KEY `id_service` (`id_service`),
  KEY `fecha` (`fecha`),
  KEY `hora` (`hora`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46  COMMENT='Contador de solicitudes por Hora';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_services`
--

CREATE TABLE IF NOT EXISTS `api_services` (
  `id_service` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_plan` int(11) NOT NULL,
  `token` varchar(32) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `proximo_corte` datetime NOT NULL,
  `last_remember` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_service`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_plan` (`id_plan`),
  KEY `proximo_corte` (`proximo_corte`),
  KEY `last_remember` (`last_remember`),
  KEY `activo` (`activo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 COMMENT='APPs Creados';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_usuarios`
--

CREATE TABLE IF NOT EXISTS `api_usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(32) NOT NULL,
  `pass` varchar(32) NOT NULL,
  `mail` varchar(128) NOT NULL,
  `rol` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `vat` varchar(32) DEFAULT NULL,
  `phone` varchar(32) NOT NULL,
  `address` text NOT NULL,
  `country` varchar(3) NOT NULL,
  PRIMARY KEY (`id_usuario`),
  KEY `user` (`user`),
  KEY `mail` (`mail`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 COMMENT='Registro de Usuarios';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE IF NOT EXISTS `estados` (
  `id_estado` int(11) NOT NULL,
  `estado` varchar(128) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipios`
--

CREATE TABLE IF NOT EXISTS `municipios` (
  `id_municipio` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `municipio` varchar(128) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id_municipio`),
  KEY `id_estado` (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin  COMMENT='Municipios del CNE';

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parroquias`
--

CREATE TABLE IF NOT EXISTS `parroquias` (
  `id_parroquia` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_municipio` int(11) NOT NULL,
  `parroquia` varchar(128) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id_parroquia`),
  KEY `id_estado` (`id_estado`),
  KEY `id_municipio` (`id_municipio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Parroquias del CNE';


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centros_cne`
--

CREATE TABLE IF NOT EXISTS `centros_cne` (
  `id_centro_cne` int(10) NOT NULL,
  `id_parroquia` int(10) DEFAULT NULL,
  `nombre_centro` varchar(128) COLLATE utf8_bin NOT NULL,
  `direccion_centro` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id_centro_cne`),
  KEY `id_parroquia` (`id_parroquia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Centros de Votacion';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_civil`
--

CREATE TABLE IF NOT EXISTS `registro_civil` (
  `rif` varchar(10) COLLATE utf8_bin NOT NULL DEFAULT '',
  `nacionalidad` varchar(1) COLLATE utf8_bin NOT NULL,
  `cedula` int(10) NOT NULL,
  `primer_apellido` varchar(128) COLLATE utf8_bin NOT NULL,
  `segundo_apellido` varchar(128) COLLATE utf8_bin DEFAULT NULL,
  `primer_nombre` varchar(128) COLLATE utf8_bin NOT NULL,
  `segundo_nombre` varchar(128) COLLATE utf8_bin DEFAULT NULL,
  `id_centro_cne` int(11) DEFAULT NULL,
  PRIMARY KEY (`rif`),
  KEY `nacionalidad` (`nacionalidad`),
  KEY `cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Registro Civil';

--
-- Estructura de tabla para la tabla `not_show`
--

CREATE TABLE IF NOT EXISTS `not_show` (
  `rif` varchar(32) NOT NULL,
  `created_date` datetime NOT NULL,
  `ip_submit` varchar(64) NOT NULL,
  PRIMARY KEY (`rif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabla de Cedulas que se han dado de Baja en el Sistema';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
