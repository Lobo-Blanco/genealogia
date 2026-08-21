-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-08-2026 a las 13:29:28
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
-- Base de datos: `genealogia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arboles`
--

CREATE TABLE `arboles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `arboles`
--

INSERT INTO `arboles` (`id`, `nombre`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Mi familia', 'Árbol genealógico principal', '2026-08-18 15:14:55', '2026-08-18 15:14:55'),
(2, 'La familia de Carlos', 'Árbol genealógico secundario', '2026-08-18 15:14:55', '2026-08-18 15:14:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `divorcios`
--

CREATE TABLE `divorcios` (
  `id` int(10) UNSIGNED NOT NULL,
  `union_id` int(10) UNSIGNED NOT NULL,
  `fecha` date DEFAULT NULL,
  `lugar` varchar(200) DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `divorcios`
--

INSERT INTO `divorcios` (`id`, `union_id`, `fecha`, `lugar`, `notas`) VALUES
(1, 7, '2012-02-15', 'Alicante', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `filiaciones`
--

CREATE TABLE `filiaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `hijo_id` int(10) UNSIGNED NOT NULL,
  `progenitor_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('biologica','pre-adoptiva','adoptiva') NOT NULL DEFAULT 'biologica',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `filiaciones`
--

INSERT INTO `filiaciones` (`id`, `usuario_id`, `hijo_id`, `progenitor_id`, `tipo`, `fecha_inicio`, `fecha_fin`, `notas`) VALUES
(6, 4, 2, 1, 'biologica', NULL, NULL, NULL),
(8, 4, 4, 1, 'biologica', NULL, NULL, NULL),
(9, 4, 5, 3, 'biologica', NULL, NULL, NULL),
(10, 4, 5, 2, 'biologica', NULL, NULL, NULL),
(11, 4, 2, 6, 'biologica', NULL, NULL, NULL),
(13, 4, 4, 6, 'biologica', NULL, NULL, NULL),
(14, 4, 7, 4, 'biologica', NULL, NULL, NULL),
(15, NULL, 9, 8, 'biologica', NULL, NULL, NULL),
(16, 4, 9, 4, 'biologica', NULL, NULL, NULL),
(17, 4, 11, 1, 'biologica', NULL, NULL, NULL),
(18, 4, 12, 1, 'biologica', NULL, NULL, NULL),
(19, NULL, 12, 13, 'biologica', NULL, NULL, NULL),
(20, 4, 15, 14, 'biologica', NULL, NULL, NULL),
(21, 4, 15, 1, 'biologica', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(200) NOT NULL,
  `sexo` enum('H','M','O') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `lugar_nacimiento` varchar(200) DEFAULT NULL,
  `fecha_defuncion` date DEFAULT NULL,
  `lugar_defuncion` varchar(200) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `arbol_id` int(11) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `personas`
--

INSERT INTO `personas` (`id`, `nombre`, `apellidos`, `sexo`, `fecha_nacimiento`, `lugar_nacimiento`, `fecha_defuncion`, `lugar_defuncion`, `notas`, `arbol_id`, `created_at`, `updated_at`) VALUES
(1, 'Juan', 'Patriarca', 'H', '1975-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-19 22:04:59', '2026-08-19 22:44:29'),
(2, 'Maria', 'hija de Juan e Isabel', 'M', '1996-12-01', NULL, NULL, NULL, NULL, 2, '2026-08-19 22:05:33', '2026-08-19 22:05:33'),
(3, 'Alberto', 'marido de Maria', 'H', '1997-12-01', NULL, '2026-01-15', NULL, NULL, 2, '2026-08-19 23:43:34', '2026-08-21 11:28:49'),
(4, 'Pedro', 'hijo de Juan e Isabel', 'H', '1995-01-01', 'Madrid', '2025-06-15', 'Albacete', NULL, 2, '2026-08-19 23:45:53', '2026-08-21 11:22:04'),
(5, 'Luis', 'hijo de Maria y Alberto', 'H', '2009-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-19 23:46:54', '2026-08-19 23:46:54'),
(6, 'Isabel', 'compañera de Juan', 'M', '1973-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 08:12:02', '2026-08-20 08:12:02'),
(7, 'Joaquin', 'hijo de Pedro soltero', 'H', '2005-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 08:51:57', '2026-08-20 08:51:57'),
(8, 'Elisa', 'mujer de Pedro', 'M', '1987-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 08:53:02', '2026-08-20 08:53:02'),
(9, 'Ernesto', 'hijo de Pedro y Elisa', 'H', '2012-12-20', NULL, NULL, NULL, NULL, 2, '2026-08-20 08:54:32', '2026-08-20 08:54:51'),
(10, 'Juana', 'mujer de Luis', 'M', '2010-01-01', 'Madrid', '2026-01-30', 'Burgos', NULL, 2, '2026-08-20 11:21:00', '2026-08-21 11:04:42'),
(11, 'Ana', 'hija de Juan', 'M', '1994-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 11:24:26', '2026-08-20 11:24:26'),
(12, 'Luis', 'hijo de Juan y Carmen', 'H', '1995-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 11:26:06', '2026-08-20 11:26:06'),
(13, 'Carmen', 'Pareja anterior de Juan', 'M', '1978-01-01', NULL, '1997-10-01', NULL, NULL, 2, '2026-08-20 11:26:57', '2026-08-20 11:26:57'),
(14, 'Ana', 'otra pareja de Juan', 'M', '1979-01-01', 'Murcia', '2026-01-28', 'Albacete', NULL, 2, '2026-08-20 11:43:18', '2026-08-21 11:08:04'),
(15, 'Ana', 'Hija de Juan y Ana', 'M', '1994-01-01', NULL, NULL, NULL, NULL, 2, '2026-08-20 11:44:17', '2026-08-20 11:44:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'administrador', 'Acceso completo a toda la aplicación'),
(2, 'visualizador', 'Puede consultar toda la genealogía'),
(3, 'editor', 'Puede modificar la rama genealógica asignada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uniones`
--

CREATE TABLE `uniones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `persona1_id` int(10) UNSIGNED NOT NULL,
  `persona2_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('pareja','matrimonio') NOT NULL DEFAULT 'pareja',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `fin_tipo` enum('ninguno','matrimonio','disolucion','divorcio','fallecimiento') NOT NULL DEFAULT 'ninguno',
  `lugar` varchar(200) DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `uniones`
--

INSERT INTO `uniones` (`id`, `usuario_id`, `persona1_id`, `persona2_id`, `tipo`, `fecha_inicio`, `fecha_fin`, `fin_tipo`, `lugar`, `notas`) VALUES
(6, 4, 2, 3, 'pareja', '2000-12-01', '2026-01-15', 'fallecimiento', NULL, NULL),
(7, 4, 8, 4, 'matrimonio', '2010-01-01', '2012-02-15', 'divorcio', NULL, NULL),
(8, 4, 5, 10, 'matrimonio', '2024-01-01', '2026-01-30', 'fallecimiento', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(200) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `persona_referencia_id` int(10) UNSIGNED DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `username`, `password`, `email`, `rol_id`, `persona_referencia_id`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'del sistema', 'admin', '$2y$10$7v05O.n3EzCtcoJe56gEKuIDt9nvKttzyFy6Y7Ojs2AppNyUQ0zLK', 'admin@genealogia.local', 1, NULL, 1, '2026-08-15 18:47:13', '2026-08-15 18:47:13'),
(2, 'Usuario', 'Visitante', 'visita', '$2y$10$HZX6EHPRfdz9UxdjvbKAl.YQ7UrWbSP9K45LmMKzTkkPM9juckVpO', 'visita@genealogia.local', 2, NULL, 1, '2026-08-15 18:47:13', '2026-08-15 18:47:13'),
(3, 'Carlos', 'Garc?a Mart?nez', 'carlos', '$2y$10$ferUmRYBtUrGIGvyqyhH9e1o.eo5WWm3VulBO8VOOm/aucYbanG9G', 'carlos@genealogia.local', 3, NULL, 1, '2026-08-15 18:47:13', '2026-08-15 18:47:13'),
(4, 'Marta', 'Garc?a Fern?ndez', 'marta', '$2y$10$9eQ6Syp.jloqPsK4HL9GRuo6ZwaPIRg8nkqMn1c29JHHAL58vLzzW', 'marta@genealogia.local', 3, NULL, 1, '2026-08-15 18:47:14', '2026-08-15 18:47:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_arboles`
--

CREATE TABLE `usuarios_arboles` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `arbol_id` int(10) UNSIGNED NOT NULL,
  `rol` enum('administrador','editor','visualizador') NOT NULL DEFAULT 'visualizador',
  `activo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios_arboles`
--

INSERT INTO `usuarios_arboles` (`id`, `usuario_id`, `arbol_id`, `rol`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'administrador', 1, '2026-08-18 15:24:00', '2026-08-18 15:24:00'),
(2, 2, 1, 'visualizador', 1, '2026-08-18 15:24:00', '2026-08-18 15:24:00'),
(3, 3, 2, 'editor', 1, '2026-08-18 15:24:00', '2026-08-18 15:24:00'),
(4, 4, 2, 'editor', 1, '2026-08-18 15:24:00', '2026-08-18 15:24:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_personas`
--

CREATE TABLE `usuarios_personas` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `persona_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios_personas`
--

INSERT INTO `usuarios_personas` (`usuario_id`, `persona_id`) VALUES
(3, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 11),
(4, 12),
(4, 13),
(4, 14),
(4, 15);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `arboles`
--
ALTER TABLE `arboles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `divorcios`
--
ALTER TABLE `divorcios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_divorcio_union` (`union_id`);

--
-- Indices de la tabla `filiaciones`
--
ALTER TABLE `filiaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_filiacion` (`hijo_id`,`progenitor_id`,`tipo`),
  ADD KEY `idx_filiaciones_hijo` (`hijo_id`),
  ADD KEY `idx_filiaciones_progenitor` (`progenitor_id`),
  ADD KEY `idx_filiaciones_usuario` (`usuario_id`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_personas_apellidos` (`apellidos`),
  ADD KEY `idx_personas_nombre` (`nombre`),
  ADD KEY `idx_personas_arbol` (`arbol_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_roles_nombre` (`nombre`);

--
-- Indices de la tabla `uniones`
--
ALTER TABLE `uniones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_union_persona1` (`persona1_id`),
  ADD KEY `idx_union_persona2` (`persona2_id`),
  ADD KEY `idx_uniones_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_username` (`username`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `fk_usuarios_rol` (`rol_id`) USING BTREE,
  ADD KEY `fk_usuarios_persona_referencia` (`persona_referencia_id`) USING BTREE;

--
-- Indices de la tabla `usuarios_arboles`
--
ALTER TABLE `usuarios_arboles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_arbol` (`usuario_id`,`arbol_id`),
  ADD KEY `idx_usuarios_arboles_usuario` (`usuario_id`),
  ADD KEY `idx_usuarios_arboles_arbol` (`arbol_id`);

--
-- Indices de la tabla `usuarios_personas`
--
ALTER TABLE `usuarios_personas`
  ADD PRIMARY KEY (`usuario_id`,`persona_id`),
  ADD KEY `idx_up_persona` (`persona_id`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `arboles`
--
ALTER TABLE `arboles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `divorcios`
--
ALTER TABLE `divorcios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `filiaciones`
--
ALTER TABLE `filiaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `uniones`
--
ALTER TABLE `uniones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios_arboles`
--
ALTER TABLE `usuarios_arboles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `divorcios`
--
ALTER TABLE `divorcios`
  ADD CONSTRAINT `fk_divorcio_matrimonio` FOREIGN KEY (`union_id`) REFERENCES `uniones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `filiaciones`
--
ALTER TABLE `filiaciones`
  ADD CONSTRAINT `fk_filiacion_hijo` FOREIGN KEY (`hijo_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_filiacion_progenitor` FOREIGN KEY (`progenitor_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_filiacion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `personas`
--
ALTER TABLE `personas`
  ADD CONSTRAINT `fk_persona_arbol` FOREIGN KEY (`arbol_id`) REFERENCES `arboles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `uniones`
--
ALTER TABLE `uniones`
  ADD CONSTRAINT `fk_union_persona1` FOREIGN KEY (`persona1_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_union_persona2` FOREIGN KEY (`persona2_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_union_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_persona_referencia` FOREIGN KEY (`persona_referencia_id`) REFERENCES `personas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios_arboles`
--
ALTER TABLE `usuarios_arboles`
  ADD CONSTRAINT `fk_usuario_arbol_arbol` FOREIGN KEY (`arbol_id`) REFERENCES `arboles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_arbol_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios_personas`
--
ALTER TABLE `usuarios_personas`
  ADD CONSTRAINT `fk_up_persona` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_up_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
