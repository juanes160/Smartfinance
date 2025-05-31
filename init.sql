-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS smartfinance CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE smartfinance;

-- Configuraciones iniciales
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Configuración de caracteres
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Tabla: categorias
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Aseo'),
(3, 'Comida'),
(2, 'Ropa');

-- Tabla: transacciones
CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `categoria_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transacciones` (`id`, `id_usuario`, `tipo`, `monto`, `descripcion`, `fecha`, `categoria_id`) VALUES
(1, 1, 'ingreso', 3000000.00, 'pago de este mes', '2025-05-25 18:55:05', NULL),
(7, 2, 'egreso', 100000.00, 'gasto carro', '2025-05-28 01:13:14', NULL),
(8, 2, 'ingreso', 2112.00, 'kje1', '2025-05-28 03:39:41', NULL),
(9, 2, 'ingreso', 2000000.00, 'qwd', '2025-05-28 03:53:02', NULL),
(10, 2, 'ingreso', 444423.00, 'FEWAF', '2025-05-28 04:13:03', NULL),
(11, 2, 'ingreso', 324.00, 'FWE', '2025-05-28 04:16:04', NULL),
(12, 2, 'ingreso', 7.00, 'HFDE', '2025-05-28 04:16:56', NULL),
(13, 2, 'ingreso', 3.00, 'fwe', '2025-05-28 04:18:23', 1);

-- Tabla: usuarios
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `usuarios` (`id`, `nombre_completo`, `correo`, `usuario`, `contrasena`) VALUES
(1, 'yesid', 'casdad@gmail.com', 'beo', '$2y$10$j0UTL7JZgPMRaX.b3QOgO.UudpuEKUFlCIvIiHKs2AGE4FErX4.gW'),
(2, 'Juan Esteban Cuaran Mena', 'jecm160@gmail.com', 'Juanes160', '$2y$10$KtgUXRg0vyZMFrK9Vefk0uSkP6p15iEF8FPYD5CZ65jGS5yiCUELa');

-- Índices
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `categoria_id` (`categoria_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `usuario` (`usuario`);

-- AUTO_INCREMENT
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Llaves foráneas
ALTER TABLE `transacciones`
  ADD CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `transacciones_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
