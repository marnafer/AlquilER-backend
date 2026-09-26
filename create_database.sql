-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-08-2026 a las 03:26:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
CREATE DATABASE IF NOT EXISTS sistema_alquiler_db_dev;
USE sistema_alquiler_db_dev;
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `deleted_at`) VALUES
(1, 'Casa', NULL),
(2, 'Departamento', NULL),
(3, 'Cabaña', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas`
--

CREATE TABLE IF NOT EXISTS `consultas` (
  `id` int(11) UNSIGNED NOT NULL,
  `propiedad_id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `fecha_consulta` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consultas`
--

INSERT INTO `consultas` (`id`, `propiedad_id`, `usuario_id`, `fecha_consulta`, `deleted_at`) VALUES
(1, 1, 2, '2026-06-12 18:10:55', '2026-06-12 18:10:55'),
(2, 1, 2, '2026-06-13 20:15:09', '2026-06-13 20:15:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE IF NOT EXISTS `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `propiedad_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localidades`
--

CREATE TABLE IF NOT EXISTS `localidades` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo_postal` varchar(15) NOT NULL,
  `provincia_id` int(11) UNSIGNED NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `localidades`
--

INSERT INTO `localidades` (`id`, `nombre`, `codigo_postal`, `provincia_id`, `deleted_at`) VALUES
(1, 'Crespo', '3116', 1, NULL),
(2, 'Parana', '3100', 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE IF NOT EXISTS `logs_actividad` (
  `id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `accion` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_consultas`
--

CREATE TABLE IF NOT EXISTS `mensajes_consultas` (
  `id` int(11) UNSIGNED NOT NULL,
  `consulta_id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_mensaje` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `referencia_id` int(11) UNSIGNED DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_notificacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expiracion` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Estructura de tabla para la tabla `propiedades`
--

CREATE TABLE IF NOT EXISTS `propiedades` (
  `id` int(11) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(12,2) NOT NULL,
  `expensas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `direccion` varchar(125) NOT NULL,
  `cantidad_ambientes` tinyint(2) UNSIGNED NOT NULL,
  `cantidad_dormitorios` tinyint(2) UNSIGNED NOT NULL,
  `cantidad_banos` tinyint(2) UNSIGNED NOT NULL,
  `capacidad` tinyint(3) UNSIGNED DEFAULT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `destacada` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `categoria_id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `localidad_id` int(11) UNSIGNED NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedades`
--

INSERT INTO `propiedades` (`id`, `titulo`, `descripcion`, `precio`, `expensas`, `direccion`, `cantidad_ambientes`, `cantidad_dormitorios`, `cantidad_banos`, `capacidad`, `disponible`, `destacada`, `categoria_id`, `usuario_id`, `localidad_id`, `deleted_at`) VALUES
(1, 'Casa prueba', 'Prueba', 150000.00, 0.00, 'Prueba', 2, 1, 1, 2, 0, 0, 1, 2, 1, NULL),
(4, 'Casa quinta actualizada', 'Descripción modificada', 200000.00, 7000.00, 'San Martin 456', 6, 4, 2, 8, 1, 1, 1, 6, 1, NULL),
(5, 'Casa', 'Casa grande', 150000.00, 0.00, 'Las Palmeras', 2, 1, 1, 2, 1, 1, 1, 8, 1, NULL),
(6, 'Departamento actualizado', 'Excelente estado, al frente con balcón. Cuenta con cocina integrada y piso flotante. Ideal para una pareja.', 350000.50, 45000.00, 'San Martín 1234, Piso 4 Depto A', 2, 1, 1, 2, 1, 0, 1, 12, 1, '2026-08-20 01:23:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedad_imagenes`
--

CREATE TABLE IF NOT EXISTS `propiedad_imagenes` (
  `id` int(11) UNSIGNED NOT NULL,
  `propiedad_id` int(11) UNSIGNED NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `descripcion` varchar(300) DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedad_servicio`
--

CREATE TABLE IF NOT EXISTS `propiedad_servicio` (
  `id` int(11) UNSIGNED NOT NULL,
  `propiedad_id` int(11) UNSIGNED NOT NULL,
  `servicio_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedad_servicio`
--

INSERT INTO `propiedad_servicio` (`id`, `propiedad_id`, `servicio_id`) VALUES
(6, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincias`
--

CREATE TABLE IF NOT EXISTS `provincias` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provincias`
--

INSERT INTO `provincias` (`id`, `nombre`, `deleted_at`) VALUES
(1, 'Entre Rios New', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

  CREATE TABLE `resenas` (
  `id` int(11) UNSIGNED NOT NULL,
  `reserva_id` int(11) UNSIGNED NOT NULL,
  `tipo` enum('propiedad','inquilino') NOT NULL,
  `calificador_id` int(11) UNSIGNED NOT NULL,
  `calificacion` tinyint(1) UNSIGNED NOT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resenas`
--

INSERT INTO `resenas`
(`id`, `reserva_id`, `tipo`, `calificador_id`, `calificacion`, `comentario`, `fecha_publicacion`, `deleted_at`)
VALUES
(1, 5, 'propiedad', 2, 5, 'Excelente lugar', '2026-06-15 03:53:00', '2026-06-15 03:58:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int(11) UNSIGNED NOT NULL,
  `propiedad_id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `fecha_inicio_alquiler` date NOT NULL,
  `fecha_fin_alquiler` date DEFAULT NULL,
  `estado` enum('pendiente','confirmada','rechazada','cancelada','finalizada') DEFAULT 'pendiente',
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `propiedad_id`, `usuario_id`, `fecha_inicio_alquiler`, `fecha_fin_alquiler`, `estado`, `fecha_reserva`, `deleted_at`) VALUES
(2, 1, 2, '2026-07-01', '2027-07-01', 'finalizada', '2026-06-13 21:55:23', '2026-06-13 22:36:05'),
(3, 1, 2, '2026-07-01', '2027-07-01', 'rechazada', '2026-06-13 22:19:33', NULL),
(4, 1, 2, '2026-07-01', '2027-07-01', 'cancelada', '2026-06-13 22:24:21', NULL),
(5, 1, 2, '2026-05-01', NULL, 'finalizada', '2026-06-15 03:51:20', NULL);

-- --------------------------------------------------------

DROP EVENT IF EXISTS `finalizar_reservas_vencidas`;

CREATE EVENT `finalizar_reservas_vencidas`
ON SCHEDULE EVERY 1 DAY
STARTS '2026-09-21 00:05:00'
DO
    UPDATE `reservas`
    SET `estado` = 'finalizada'
    WHERE `estado` IN ('pendiente', 'confirmada')
      AND `fecha_fin_alquiler` IS NOT NULL
      AND `fecha_fin_alquiler` < CURDATE()
      AND `deleted_at` IS NULL;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'usuario'),
(2, 'administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE IF NOT EXISTS `servicios` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `deleted_at`) VALUES
(1, 'Gas natural', NULL),
(3, 'Wifi', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(25) NOT NULL,
  `domicilio` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol_id` int(11) UNSIGNED NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `telefono`, `domicilio`, `contrasena`, `rol_id`, `deleted_at`) VALUES
(2, 'Propietario', 'Prop', 'prop@gmail.com', '3624123456', 'Prop', '', 2, NULL),
(6, 'Juan', 'Perez', 'propi@gmail.com', '3435123456', 'San Martin 123', '$2y$10$zIKPJrOQHTGCnpeGKiK1Ku1Tiwr1W98hLjm3.vP3uexdk/KQ1jzGO', 2, NULL),
(8, 'Propietario Demo', 'Prueba', 'propietario.demo@test.com', '3435147584', 'Las Palmeras', '$2y$10$rI2jinljp3nA2N5YVjGeO.SQFzwZ6WwmshLD/OgfXrmZGgfbVTQXO', 2, NULL),
(9, 'Mariano Register', 'Register Vista', 'mariano@gmail.com', '3435123879', 'Av Ramirez', '$2y$10$VyYq1WBamY05DnN1GFHNeOXFVnbvtbXPpyvOrYukj7AwJYixJ.A..', 2, NULL),
(10, 'Prueba', 'Sweet Alert', 'probando@gmail.com', '3435123879', 'Av Ramirez', '$2y$10$YIUpfqUstc3maRp9xOyUP.ZVbpFYnR1cjyOLo.JO3afpQiRCG7DDq', 1, NULL),
(12, 'Usuario', 'User', 'user@gmail.com', '3436789098', 'Jordania', '$2y$10$E/I3eeSTgugi33mPH4brNuvtmXIqNm9aSusB0kOBEC32dFhV3Lp2e', 1, NULL);

-- --------------------------------------------------------

--
-- Disparadores `propiedades`
--
DELIMITER $$
CREATE TRIGGER `tr_soft_delete_propiedad` AFTER UPDATE ON `propiedades` FOR EACH ROW BEGIN
    -- Verificamos si la propiedad acaba de ser marcada como borrada
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        -- Cancelamos las reservas que aún no hayan finalizado
        UPDATE `reservas`
        SET `estado` = 'cancelada'
        WHERE `propiedad_id` = NEW.id 
        AND `estado` IN ('pendiente', 'confirmada');
    END IF;
END
$$
DELIMITER ;

--
-- Estructura Stand-in para la vista `v_usuarios_activos`
-- (Véase abajo para la vista actual)
--
CREATE TABLE IF NOT EXISTS `v_usuarios_activos` (
`id` int(11) unsigned
,`nombre` varchar(50)
,`apellido` varchar(50)
,`email` varchar(100)
,`rol_id` int(11) unsigned
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuarios_activos`
--
DROP TABLE IF EXISTS `v_usuarios_activos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_usuarios_activos`  AS SELECT `usuarios`.`id` AS `id`, `usuarios`.`nombre` AS `nombre`, `usuarios`.`apellido` AS `apellido`, `usuarios`.`email` AS `email`, `usuarios`.`rol_id` AS `rol_id` FROM `usuarios` WHERE `usuarios`.`deleted_at` is null ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_consulta_propiedad` (`propiedad_id`),
  ADD KEY `fk_consulta_usuario` (`usuario_id`) USING BTREE;

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_propiedad` (`usuario_id`,`propiedad_id`),
  ADD KEY `fk_fav_propiedad` (`propiedad_id`);

--
-- Indices de la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_provincia_localidad` (`provincia_id`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `mensajes_consultas`
--
ALTER TABLE `mensajes_consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mensaje_consulta` (`consulta_id`),
  ADD KEY `fk_mensaje_usuario` (`usuario_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notificacion_usuario` (`usuario_id`),
  ADD KEY `idx_notificaciones_fecha` (`fecha_notificacion`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_resets_token` (`token`),
  ADD KEY `idx_password_resets_email` (`email`);

--
-- Indices de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_propiedad_localidad` (`localidad_id`),
  ADD KEY `fk_propiedad_categoria` (`categoria_id`),
  ADD KEY `fk_propiedad_administrador` (`usuario_id`);

--
-- Indices de la tabla `propiedad_imagenes`
--
ALTER TABLE `propiedad_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_imagen_propiedad` (`propiedad_id`);

--
-- Indices de la tabla `propiedad_servicio`
--
ALTER TABLE `propiedad_servicio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_propiedad_servicio` (`propiedad_id`,`servicio_id`),
  ADD KEY `fk_pivot_propiedad` (`propiedad_id`),
  ADD KEY `fk_pivot_servicio` (`servicio_id`);

--
-- Indices de la tabla `provincias`
--
ALTER TABLE `provincias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_resena_reserva_tipo` (`reserva_id`,`tipo`),
  ADD KEY `idx_resenas_reserva_id` (`reserva_id`),
  ADD KEY `idx_resenas_calificador_id` (`calificador_id`),
  ADD KEY `idx_resenas_tipo` (`tipo`),
  ADD KEY `idx_resenas_calificacion` (`calificacion`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reserva_propiedad` (`propiedad_id`),
  ADD KEY `fk_reserva_usuario` (`usuario_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuarios_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `localidades`
--
ALTER TABLE `localidades`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_consultas`
--
ALTER TABLE `mensajes_consultas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `propiedad_imagenes`
--
ALTER TABLE `propiedad_imagenes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propiedad_servicio`
--
ALTER TABLE `propiedad_servicio`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `provincias`
--
ALTER TABLE `provincias`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;


--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `fk_consultas_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_consultas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `localidades`
--
ALTER TABLE `localidades`
  ADD CONSTRAINT `fk_provincia_localidad` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`);

--
-- Filtros para la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensajes_consultas`
--
ALTER TABLE `mensajes_consultas`
  ADD CONSTRAINT `fk_mensaje_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensaje_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD CONSTRAINT `fk_propiedades_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_propiedades_localidad` FOREIGN KEY (`localidad_id`) REFERENCES `localidades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_propiedades_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `propiedad_imagenes`
--
ALTER TABLE `propiedad_imagenes`
  ADD CONSTRAINT `fk_imagenes_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `propiedad_servicio`
--
ALTER TABLE `propiedad_servicio`
  ADD CONSTRAINT `fk_propiedad_servicio_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_propiedad_servicio_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD CONSTRAINT `refresh_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `fk_resenas_calificador` FOREIGN KEY (`calificador_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_resenas_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
