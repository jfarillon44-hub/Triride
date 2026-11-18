-- TriRide Database Structure
-- Target Database: triride_db

CREATE DATABASE IF NOT EXISTS `triride_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `triride_db`;

-- 1. ADMINS TABLE
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. USERS (PASSENGERS) TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `wallet_balance` DECIMAL(10,2) DEFAULT 150.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. DRIVERS TABLE
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `license_no` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Offline', -- 'Online', 'Offline', 'Suspended'
  `rating` DECIMAL(3,2) DEFAULT 5.00,
  `vehicle_info` VARCHAR(50) DEFAULT 'Green Tricycle',
  `latitude` DECIMAL(10,8) DEFAULT 8.6042,     -- Default location (Prosperidad, Agusan del Sur)
  `longitude` DECIMAL(11,8) DEFAULT 125.9142,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. RIDES TABLE
CREATE TABLE IF NOT EXISTS `rides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `pickup_location` VARCHAR(255) NOT NULL,
  `destination` VARCHAR(255) NOT NULL,
  `pickup_lat` DECIMAL(10,8) DEFAULT NULL,
  `pickup_lng` DECIMAL(11,8) DEFAULT NULL,
  `dest_lat` DECIMAL(10,8) DEFAULT NULL,
  `dest_lng` DECIMAL(11,8) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending', -- 'Pending', 'Accepted', 'Arrived', 'Picked Up', 'Completed', 'Cancelled'
  `fare` DECIMAL(10,2) NOT NULL,
  `distance` DECIMAL(5,2) DEFAULT NULL,            -- in km
  `duration` INT DEFAULT NULL,                     -- in minutes
  `request_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. PAYMENTS TABLE
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ride_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` VARCHAR(50) NOT NULL, -- 'Cash', 'GCash', 'Maya', 'PayPal'
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending', -- 'Pending', 'Paid'
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LOCATIONS TABLE (Historical GPS tracking for rides)
CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ride_id` INT NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. CHATS TABLE
CREATE TABLE IF NOT EXISTS `chats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ride_id` INT NOT NULL,
  `sender` ENUM('Passenger', 'Driver') NOT NULL,
  `message` TEXT NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ride_id`) REFERENCES `rides`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. REPORTS TABLE
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `report_type` VARCHAR(100) NOT NULL,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. SETTINGS TABLE
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `app_name` VARCHAR(100) DEFAULT 'TriRide',
  `base_fare` DECIMAL(10,2) DEFAULT 25.00,
  `per_km_rate` DECIMAL(10,2) DEFAULT 5.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- SEEDING SAMPLE DATA
-- Password for all seeded accounts is 'admin123', 'driver123', 'user123' respectively.
-- Hashed using password_hash('...', PASSWORD_DEFAULT)

-- Insert Settings
INSERT INTO `settings` (`id`, `app_name`, `base_fare`, `per_km_rate`) 
VALUES (1, 'TriRide', 25.00, 5.00)
ON DUPLICATE KEY UPDATE `app_name`='TriRide';

-- Insert Admin
-- admin@gmail.com / admin123
INSERT INTO `admins` (`fullname`, `email`, `password`) 
VALUES ('System Admin', 'admin@gmail.com', '$2y$10$Z.sy3Pve5NmsLKOf8MTsPeFZefzq6YcVd24kYTkir.aMqp52yiAo2')
ON DUPLICATE KEY UPDATE `fullname`='System Admin';

-- Insert Driver
-- driver01@gmail.com / driver123
INSERT INTO `drivers` (`fullname`, `phone`, `license_no`, `email`, `password`, `status`, `rating`, `vehicle_info`, `latitude`, `longitude`) 
VALUES ('Juan Dela Cruz', '09987654321', 'L-12345-67890', 'driver01@gmail.com', '$2y$10$zdsnSR3qut8h8P5HBvHlveqNQay4NIriGAsHzg6a7g7AxII1I/ZkC', 'Offline', 4.8, 'Green Tricycle', 8.6042, 125.9142)
ON DUPLICATE KEY UPDATE `fullname`='Juan Dela Cruz';

-- Insert User (Passenger)
-- user01@gmail.com / user123
INSERT INTO `users` (`fullname`, `phone`, `email`, `password`, `wallet_balance`) 
VALUES ('Juliana V. Farillon', '09123456789', 'user01@gmail.com', '$2y$10$1kQWmt97mWnzoswOx3kIT.P9M4yJJogAyHrSRN/mBLApN8w49WQs2', 150.00)
ON DUPLICATE KEY UPDATE `fullname`='Juliana V. Farillon';

-- Additional seed accounts to match the UI screenshot user lists
INSERT IGNORE INTO `users` (`fullname`, `phone`, `email`, `password`, `wallet_balance`)
VALUES 
('Maria Santos', '09111111111', 'maria@gmail.com', '$2y$10$1kQWmt97mWnzoswOx3kIT.P9M4yJJogAyHrSRN/mBLApN8w49WQs2', 200.00),
('Pedro Reyes', '09222222222', 'pedro@gmail.com', '$2y$10$1kQWmt97mWnzoswOx3kIT.P9M4yJJogAyHrSRN/mBLApN8w49WQs2', 50.00);

INSERT IGNORE INTO `drivers` (`fullname`, `phone`, `license_no`, `email`, `password`, `status`, `rating`, `vehicle_info`)
VALUES
('Ricardo Dalisay', '09333333333', 'L-33333-33333', 'ricardo@gmail.com', '$2y$10$zdsnSR3qut8h8P5HBvHlveqNQay4NIriGAsHzg6a7g7AxII1I/ZkC', 'Online', 4.9, 'Yellow Tricycle'),
('Jose Rizal', '09444444444', 'L-44444-44444', 'jose@gmail.com', '$2y$10$zdsnSR3qut8h8P5HBvHlveqNQay4NIriGAsHzg6a7g7AxII1I/ZkC', 'Offline', 4.5, 'Blue Tricycle');
