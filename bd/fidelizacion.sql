-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-06-2025 a las 22:06:30
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
-- Base de datos: `fidelizacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`id_admin`, `nombre`, `telefono`, `password_hash`, `creado_en`) VALUES
(1, 'juan', '1234567890', '123', '2025-05-29 17:41:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `beneficios`
--

CREATE TABLE `beneficios` (
  `id_beneficio` int(11) NOT NULL,
  `empresa` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `descuento` varchar(50) DEFAULT NULL,
  `vigente_desde` date DEFAULT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `beneficios`
--

INSERT INTO `beneficios` (`id_beneficio`, `empresa`, `descripcion`, `descuento`, `vigente_desde`, `vigente_hasta`, `activo`, `imagen`) VALUES
(1, 'Coca Cola', 'Tendras descuento en la compra de refrescos coca cola', '10%', '2025-05-10', '2027-05-10', 1, 'img/beneficios/ben_684998d0be2c8.jpeg'),
(2, 'Nike', '20% de descuento en compras superiores a los $1000', '20%', '2025-06-26', '2026-02-11', 1, 'img/beneficios/ben_684c7e5e4e8ea.jpg'),
(3, 'La sierra', '15% al comprar boletos para cualquier viaje', '15%', '2025-02-12', '2025-12-13', 1, 'img/beneficios/ben_684c7e983ecf4.webp'),
(4, 'Zara', '12% de descuento en compras mayores a $600', '12%', '2025-03-22', '2026-12-13', 1, 'img/beneficios/ben_684c7ecf9a578.jpg'),
(5, 'Dining Cart', '7% de descuento en todas las comidas', '7%', '2025-02-04', '2027-11-16', 1, 'img/beneficios/ben_684c7f159d210.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(80) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `ciudad` varchar(50) DEFAULT NULL,
  `puntos_actuales` int(11) NOT NULL DEFAULT 0,
  `tarjeta_digital` varchar(100) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre`, `apellidos`, `telefono`, `password_hash`, `direccion`, `correo`, `estado`, `ciudad`, `puntos_actuales`, `tarjeta_digital`, `creado_en`) VALUES
(1, 'pedro', 'contreras', '0987654321', '123', '12', 'pedro@gmail.com', 'yucatan', 'merida', 3510, 'si', '2025-05-29 18:44:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

CREATE TABLE `premios` (
  `id_premio` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `puntos_requeridos` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `premios`
--

INSERT INTO `premios` (`id_premio`, `nombre`, `descripcion`, `imagen`, `puntos_requeridos`, `stock`, `activo`) VALUES
(1, 'Pase Cena gratis', 'Gana una cena gratis', 'img/premio_684991b69dedf2.16802412.jpeg', 300, 99, 1),
(2, 'Tarjeta amazon de $300', 'Canjea esta tarjeta de 300', 'img/premio_684c7bcadde702.56121907.png', 500, 20, 1),
(3, 'Boletos de festival actual', 'Boletos de festival actual, dos boletos por canje', 'img/premio_684c7bf7590936.66964796.jpg', 900, 20, 1),
(4, 'Tarjeta spotify', 'tarjeta spotify de 129', 'img/premio_684c7c24906453.28188006.jpg', 400, 9, 1),
(5, 'Dona a personas de escasos recursos - $100', 'Se enviara a instituciones que donen a personas de escasos recursos', 'img/premio_684c7c607cd905.31092229.jpg', 100, 20, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `redenciones`
--

CREATE TABLE `redenciones` (
  `id_redencion` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_premio` int(11) NOT NULL,
  `puntos_usados` int(11) NOT NULL,
  `puntos_restantes` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `redenciones`
--

INSERT INTO `redenciones` (`id_redencion`, `id_cliente`, `id_premio`, `puntos_usados`, `puntos_restantes`, `fecha`) VALUES
(1, 1, 1, 300, 905, '2025-06-11 07:02:15'),
(2, 1, 1, 300, 0, '2025-06-11 08:17:59'),
(3, 1, 5, 100, 3910, '2025-06-13 13:34:32'),
(4, 1, 4, 400, 0, '2025-06-13 13:43:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarjetas`
--

CREATE TABLE `tarjetas` (
  `id_tarjeta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `numero` varchar(16) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `cvv` varchar(3) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tarjetas`
--

INSERT INTO `tarjetas` (`id_tarjeta`, `id_cliente`, `numero`, `fecha_vencimiento`, `cvv`, `creado_en`) VALUES
(1, 1, '8185727247552449', '2029-06-11', '671', '2025-06-11 07:47:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones_puntos`
--

CREATE TABLE `transacciones_puntos` (
  `id_transaccion` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `monto_compra` decimal(10,2) NOT NULL,
  `puntos_acreditados` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `transacciones_puntos`
--

INSERT INTO `transacciones_puntos` (`id_transaccion`, `id_cliente`, `monto_compra`, `puntos_acreditados`, `fecha`) VALUES
(1, 1, 100.00, 5, '2025-06-11 06:48:42'),
(2, 1, 200.00, 10, '2025-06-13 13:34:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `telefono` (`telefono`);

--
-- Indices de la tabla `beneficios`
--
ALTER TABLE `beneficios`
  ADD PRIMARY KEY (`id_beneficio`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `telefono` (`telefono`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`id_premio`);

--
-- Indices de la tabla `redenciones`
--
ALTER TABLE `redenciones`
  ADD PRIMARY KEY (`id_redencion`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_premio` (`id_premio`);

--
-- Indices de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD PRIMARY KEY (`id_tarjeta`),
  ADD UNIQUE KEY `id_cliente` (`id_cliente`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `cvv` (`cvv`);

--
-- Indices de la tabla `transacciones_puntos`
--
ALTER TABLE `transacciones_puntos`
  ADD PRIMARY KEY (`id_transaccion`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `beneficios`
--
ALTER TABLE `beneficios`
  MODIFY `id_beneficio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `id_premio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `redenciones`
--
ALTER TABLE `redenciones`
  MODIFY `id_redencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  MODIFY `id_tarjeta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `transacciones_puntos`
--
ALTER TABLE `transacciones_puntos`
  MODIFY `id_transaccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `redenciones`
--
ALTER TABLE `redenciones`
  ADD CONSTRAINT `redenciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `redenciones_ibfk_2` FOREIGN KEY (`id_premio`) REFERENCES `premios` (`id_premio`);

--
-- Filtros para la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD CONSTRAINT `tarjetas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `transacciones_puntos`
--
ALTER TABLE `transacciones_puntos`
  ADD CONSTRAINT `transacciones_puntos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
