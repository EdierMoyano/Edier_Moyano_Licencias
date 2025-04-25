-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2025 at 04:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `software_licencias`
--

-- --------------------------------------------------------

--
-- Table structure for table `asistencia`
--

CREATE TABLE `asistencia` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT NULL,
  `nombre_usu` varchar(100) DEFAULT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asistencia`
--

INSERT INTO `asistencia` (`id`, `curso_id`, `fecha_hora`, `nombre_usu`, `documento`, `observaciones`) VALUES
(1, 1, '2025-04-24 21:09:26', 'Julaina', '3133676', 'Llego temprano'),
(2, 1, '2025-04-24 21:43:24', 'edawrd', '123123123', 'asdasdasdasd'),
(3, 1, '2025-04-25 07:33:59', 'asd', 'asd', 'asd'),
(4, 1, '2025-04-25 08:35:01', 'qwer qwe', 'w6', ''),
(5, 1, '2025-04-25 08:38:54', 'qwer qwe', 'w6', 'fasdasd');

-- --------------------------------------------------------

--
-- Table structure for table `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `f_inicio` date DEFAULT NULL,
  `f_fin` date DEFAULT NULL,
  `lugar` varchar(100) DEFAULT NULL,
  `id_creador` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `descripcion`, `f_inicio`, `f_fin`, `lugar`, `id_creador`) VALUES
(1, 'Nose numero 1', 'nose nuermo 2', '2025-04-23', '2025-04-23', 'Sena nave 4', '1104940105'),
(2, 'poximo', 'a', '2025-05-06', '2025-05-14', 'a', '1104940105');

-- --------------------------------------------------------

--
-- Table structure for table `empresa`
--

CREATE TABLE `empresa` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `empresa`
--

INSERT INTO `empresa` (`id`, `nombre`) VALUES
(1, 'Desarrolladores'),
(2, 'TeamTalks'),
(3, 'AgroStock');

-- --------------------------------------------------------

--
-- Table structure for table `licencias`
--

CREATE TABLE `licencias` (
  `id` int(11) NOT NULL,
  `codigo_licencia` varchar(20) NOT NULL,
  `id_tipo_licencia` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Activa','Inactiva','Expirada') DEFAULT 'Activa',
  `id_empresa` int(11) NOT NULL,
  `fecha_compra` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `licencias`
--

INSERT INTO `licencias` (`id`, `codigo_licencia`, `id_tipo_licencia`, `fecha_inicio`, `fecha_fin`, `estado`, `id_empresa`, `fecha_compra`) VALUES
(1, '', 1, '2025-04-21', '2026-04-23', 'Activa', 2, '2025-04-24 19:57:13'),
(2, '34bbb92a0bc72c39083c', 2, '2025-04-25', '2026-04-25', 'Activa', 3, '2025-04-24 20:27:20'),
(3, '36f0335323c75716dced', 1, '2025-04-15', '2025-04-18', 'Activa', 3, '2025-04-24 21:21:10'),
(4, 'c522311f0a480aa94c71', 4, '2025-04-23', '2026-04-24', 'Activa', 3, '2025-04-25 09:47:14');

--
-- Triggers `licencias`
--
DELIMITER $$
CREATE TRIGGER `actualizar_estado_licencia` BEFORE UPDATE ON `licencias` FOR EACH ROW BEGIN
    -- Si la fecha de fin es anterior a la fecha actual, marcar como expirada
    IF NEW.fecha_fin < CURDATE() AND NEW.estado = 'Activa' THEN
        SET NEW.estado = 'Expirada';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calcular_fecha_fin` BEFORE INSERT ON `licencias` FOR EACH ROW BEGIN
    DECLARE dias INT;
    DECLARE codigo_aleatorio VARCHAR(20);
    
    -- Generar un código único aleatorio de 20 caracteres
    SET codigo_aleatorio = CONCAT(
        SUBSTRING(MD5(RAND()), 1, 10),
        SUBSTRING(MD5(UNIX_TIMESTAMP()), 1, 10)
    );
    
    
    SET NEW.codigo_licencia = codigo_aleatorio;
    
    
    SELECT duracion_dias INTO dias FROM tipo_licencia WHERE id = NEW.id_tipo_licencia;
    
    
    SET NEW.fecha_fin = DATE_ADD(NEW.fecha_inicio, INTERVAL dias DAY);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `rol`
--

CREATE TABLE `rol` (
  `id` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rol`
--

INSERT INTO `rol` (`id`, `nombre_rol`) VALUES
(1, 'S_Admin'),
(2, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_licencia`
--

CREATE TABLE `tipo_licencia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_dias` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipo_licencia`
--

INSERT INTO `tipo_licencia` (`id`, `nombre`, `descripcion`, `duracion_dias`) VALUES
(1, 'Demo', 'Versión de prueba limitada', 3),
(2, 'Freeware', 'Software gratuito con funcionalidades básicas', 365),
(3, 'Shareware', 'Software de prueba con funcionalidades completas', 30),
(4, 'Anual', 'Licencia completa por un año', 365),
(5, 'Semestral', 'Licencia completa por seis meses', 182);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `codigo_barras_ruta` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `contraseña` varchar(100) NOT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `rol` int(11) DEFAULT NULL,
  `codigo_barras` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombres`, `apellidos`, `codigo_barras_ruta`, `email`, `contraseña`, `id_empresa`, `rol`, `codigo_barras`) VALUES
('1104940105', 'Edier santiago', 'moyano', NULL, 'santiagopistacho2@gmail.com', '$2y$10$0wqiPzETKDicengAC5CIFetvZKf1VlqHGXLt.8g1bniu/sPkNBIaG', 2, 2, ''),
('1107978187', 'daniel', 'chiquei', NULL, 'daniel@gmail.com', '$2y$10$fHHmEolCDqI/a9J.kyA45Ob1URO.yzE5YPUUt5i.IJWUqCCmdTnze', 3, 2, ''),
('1212121212', 'pedroas', 'asas', 'barcode/barcode_1212121212.png', 'asdas@gmail.com', '$2y$10$QIDXsGWgkY6jNOV5sJvJmO/pPvs02qy/7IERS0Q0LeSKzwtD8YXNi', 2, 2, ''),
('1231234', 'das', 'adad', NULL, '123w@gmail.com', '$2y$10$z35FDC3y./BUOBapkk6MRuQ7KPB26BQGyztKS/7L9qHIOyAqV3B7y', 3, 2, ''),
('123123455', 'addas', 'asdasd', NULL, 'eweda@gmai.com', '$2y$10$i6/so1P/LSuy2.I3KAVHoex1racnPJ8PHjJopFaQ0F0aTnRiXy.U2', 3, 2, ''),
('12345678', 'asdasd', 'asdasd', 'barcode/barcode_12345678.png', 'sdasdw@gmail.com', '$2y$10$afxLI2Ty7Z9xbNnHH9ppOuRcOw6wLrDNG0SrKQJZIqAzTiTjp.BoO', 3, 2, 'RMMY71NXAD'),
('1234567890', 'Super', 'Admin', NULL, 'ediersmb@gmail.com', '$2y$10$.Es1eRG4ZWfYMdrPMp1cNeRQHUoNwD6/.IAa3.LdpCq78kO8Plbiu', 1, 1, ''),
('1598686', 'pedro', 'sanches', 'barcode/barcode_1598686.png', 'pedrorata@gmail.com', '$2y$10$i2tZAld15Zu2eL3z9TuaPeUEOjV.aoJ7F8wfXpa/EHODcdVwViRGm', 2, 2, ''),
('179225', 'codigo1', '2', 'iVBORw0KGgoAAAANSUhEUgAAAIgAAAAyAQMAAAC9JQECAAAABlBMVEX///8AAABVwtN+AAAAAXRSTlMAQObYZgAAAAlwSFlzAAAOxAAADsQBlSsOGwAAACVJREFUKJFj+Mx/mP/wmfMf7Hk+HD5gfODPeYZRkVGRURE6igAAXLAS+w5FQkUAAAAASUVORK5CYII=', 'code1@gmail.com', '$2y$10$RNzhwhUwOVXABOlpFb.oAOzElJGaSXrQxgPSlhXRte.GzQXEtPraO', 3, 2, ''),
('3157883730', 'mari', 'asd', NULL, 'dasdasd@gmail.com', '$2y$10$iu7gBi8TwSx2AhLzdI8uEuMWmcG29UU4EW6grY9gsPFWebuaEFeU.', 3, 2, ''),
('787878', '12ads', 'asdasd', 'barcode/787878.png', 'asdasd@gmail.com', '$2y$10$aXTGd0JMSypXlyqoPpOs4ulN5b1V/s0tvtUzTSsNYa8r.HaXaV/Iq', 3, 2, ''),
('w6', 'qwer', 'qwe', 'barcode/barcode_09876543.png', 'qwe@gmail.com', '$2y$10$SlRi0yoecqqXBaaYo1dkc.m3/Jlu7Vg9cotbO3MW8cSkkVL6K2mOK', 2, 2, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Indexes for table `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_creador` (`id_creador`);

--
-- Indexes for table `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `licencias`
--
ALTER TABLE `licencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_licencia` (`codigo_licencia`),
  ADD KEY `id_tipo_licencia` (`id_tipo_licencia`),
  ADD KEY `id_empresa` (`id_empresa`);

--
-- Indexes for table `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `rol` (`rol`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `licencias`
--
ALTER TABLE `licencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rol`
--
ALTER TABLE `rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`);

--
-- Constraints for table `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `licencias`
--
ALTER TABLE `licencias`
  ADD CONSTRAINT `licencias_ibfk_1` FOREIGN KEY (`id_tipo_licencia`) REFERENCES `tipo_licencia` (`id`),
  ADD CONSTRAINT `licencias_ibfk_2` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`rol`) REFERENCES `rol` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
