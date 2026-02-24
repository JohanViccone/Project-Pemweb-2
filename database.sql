-- database.sql
-- Database: hotel_the_peak
-- Import file ini lewat phpMyAdmin atau MySQL CLI.

CREATE DATABASE IF NOT EXISTS hotel_the_peak
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE hotel_the_peak;

-- USERS
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  photo VARCHAR(255) NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ROOMS
DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_number VARCHAR(10) NOT NULL UNIQUE,
  type VARCHAR(50) NOT NULL,
  price_per_day INT NOT NULL DEFAULT 0,
  status ENUM('available','occupied','maintenance') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- GUESTS
DROP TABLE IF EXISTS guests;
CREATE TABLE guests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  address TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- STAYS (data menginap / checkout)
DROP TABLE IF EXISTS stays;
CREATE TABLE stays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guest_id INT NOT NULL,
  room_id INT NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  status ENUM('menginap','selesai') NOT NULL DEFAULT 'menginap',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stays_guest FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
  CONSTRAINT fk_stays_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
  INDEX idx_stays_status (status),
  INDEX idx_stays_dates (check_in, check_out)
) ENGINE=InnoDB;

-- TRANSACTIONS (pembayaran)
DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stay_id INT NOT NULL,
  booking_type ENUM('boking','langsung') NOT NULL DEFAULT 'langsung',
  total_days INT NOT NULL DEFAULT 1,
  price_per_day INT NOT NULL DEFAULT 0,
  total_amount INT NOT NULL DEFAULT 0,
  down_payment INT NOT NULL DEFAULT 0,
  paid_amount INT NOT NULL DEFAULT 0,
  remaining_amount INT NOT NULL DEFAULT 0,
  change_amount INT NOT NULL DEFAULT 0,
  status ENUM('menginap','checkout') NOT NULL DEFAULT 'menginap',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_stay FOREIGN KEY (stay_id) REFERENCES stays(id) ON DELETE CASCADE,
  INDEX idx_tx_status (status),
  INDEX idx_tx_created (created_at)
) ENGINE=InnoDB;

-- SETTINGS (1 row)
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id INT PRIMARY KEY,
  store_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  owner_name VARCHAR(120) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (id, store_name, phone, address, owner_name)
VALUES (1, 'Hotel The Peak', '08xxxxxxxxxx', 'Alamat hotel kamu di sini', 'Pemilik');

-- Default admin
INSERT INTO users (username, password_hash, name, email, phone, address, role)
VALUES ('admin', '$2b$10$2XelHUuEB.zieujxLgru0.QJE33nQE7H2gDKvxi6S4rrtxrTpwQ.m', 'Administrator', 'admin@example.com', '08xxxxxxxxxx', 'Alamat admin', 'admin');

-- Sample rooms (status disesuaikan dengan data transaksi contoh di bawah)
INSERT INTO rooms (room_number, type, price_per_day, status) VALUES
('101', 'Deluxe', 1200000, 'available'),
('103', 'Superior', 1300000, 'occupied'),
('105', 'Deluxe', 1200000, 'maintenance'),
('201', 'Superior', 1600000, 'available'),
('202', 'Deluxe', 1700000, 'available');

-- Sample guest + stay + tx (opsional)
INSERT INTO guests (name, phone, email, address) VALUES
('Alex Pratama', '081234567890', 'alex@example.com', 'Jakarta'),
('Lucy Wijaya', '081234567891', 'lucy@example.com', 'Bandung'),
('Budi Santoso', '081234567892', 'budi@example.com', 'Surabaya');

INSERT INTO stays (guest_id, room_id, check_in, check_out, status) VALUES
(1, (SELECT id FROM rooms WHERE room_number='101'), '2026-03-16', '2026-03-20', 'selesai'),
(2, (SELECT id FROM rooms WHERE room_number='103'), '2026-03-20', '2026-03-22', 'menginap'),
(3, (SELECT id FROM rooms WHERE room_number='201'), '2026-03-21', '2026-03-22', 'selesai');

INSERT INTO transactions (stay_id, booking_type, total_days, price_per_day, total_amount, down_payment, paid_amount, remaining_amount, change_amount, status)
VALUES
((SELECT id FROM stays WHERE guest_id=1 ORDER BY id DESC LIMIT 1), 'langsung', 4, 1150000, 4600000, 0, 4600000, 0, 0, 'checkout'),
((SELECT id FROM stays WHERE guest_id=2 ORDER BY id DESC LIMIT 1), 'boking', 2, 1300000, 2600000, 550000, 500000, 1550000, 0, 'menginap'),
((SELECT id FROM stays WHERE guest_id=3 ORDER BY id DESC LIMIT 1), 'langsung', 1, 1600000, 1600000, 0, 1600000, 0, 0, 'checkout');
