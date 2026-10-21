CREATE DATABASE IF NOT EXISTS computer_lab_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE computer_lab_db;

CREATE TABLE IF NOT EXISTS equipment (
    Equipment_ID INT AUTO_INCREMENT PRIMARY KEY,
    Equipment_Name VARCHAR(150) NOT NULL,
    Status ENUM('Working', 'Under Maintenance', 'Damaged') NOT NULL,
    Purchase_Date DATE NOT NULL,
    Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    User_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Full_Name VARCHAR(120) NOT NULL,
    Email VARCHAR(190) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment_transactions (
    Transaction_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Equipment_ID INT NOT NULL,
    User_ID INT UNSIGNED NOT NULL,
    Borrowed_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Due_Date DATE NOT NULL,
    Returned_At TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_transactions_equipment FOREIGN KEY (Equipment_ID)
        REFERENCES equipment (Equipment_ID) ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_user FOREIGN KEY (User_ID)
        REFERENCES users (User_ID) ON DELETE RESTRICT,
    INDEX idx_transactions_open_equipment (Equipment_ID, Returned_At)
) ENGINE=InnoDB;