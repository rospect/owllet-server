-- Dito ginagawa ang mga table ng Owllet sa MySQL o MariaDB.
--
-- PAALALA:
-- * Sa XAMPP (lokal): i-paste ito buo sa SQL tab ng phpMyAdmin at pindutin ang Go.
-- * Sa online hosting (hal. InfinityFree): BAGO mag-import, tanggalin ang dalawang
--   linyang "CREATE DATABASE" at "USE" sa ibaba — iba ang pangalan ng database na
--   ibinigay ng host (hal. if0_XXXX_owllet_db). Piliin muna ang iyong database sa
--   phpMyAdmin, saka i-paste ang laman ng file na ito sa SQL tab.
--
-- Ligtas itong patakbuhin ulit — hindi ginagawa o pinapalitan ang table na meron na.
--
-- 4 tables lang — puro ginagamit ng application:
--   1. expenses  — mga detalye ng gastos
--   2. receipts  — mga larawan ng resibo (kusang nabubura kasama ng gastos)
--   3. budgets   — budget kada buwan (history)
--   4. profiles  — display name at avatar initials ng user
--
-- Ang mga category at paraan ng bayad ay naka-hardcode sa api.php
-- (CATEGORIES at PAYMENT_METHODS) — walang table na kailangan para sa mga ito.

-- === Tanggalin ang dalawang ito kapag online hosting ang gamit ===
CREATE DATABASE IF NOT EXISTS owllet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE owllet_db;
-- === Tanggalin ang dalawang ito kapag online hosting ang gamit ===

-- Dito ginagawa ang table para sa mga gastos.
-- Ang category ay string lang dito; sa api.php sinusuri kung kilala ito (isValidCategory).
CREATE TABLE IF NOT EXISTS expenses (
  id             VARCHAR(36)   PRIMARY KEY,
  title          VARCHAR(255)  NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  category       VARCHAR(50)   NOT NULL,
  date           DATE          NOT NULL,
  note           VARCHAR(500)  NULL,
  payment_method VARCHAR(50)   NULL,
  created_at     BIGINT        NOT NULL,
  KEY idx_expenses_date (date),
  KEY idx_expenses_category (category),
  KEY idx_expenses_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dito ginagawa ang table para sa mga photo ng resibo.
-- Kapag nabura ang gastos, kusang nabubura rin ang resibo nito (ON DELETE CASCADE).
CREATE TABLE IF NOT EXISTS receipts (
  expense_id VARCHAR(36)  PRIMARY KEY,
  image_data MEDIUMTEXT   NOT NULL,
  created_at BIGINT       NOT NULL,
  CONSTRAINT fk_receipt_expense
    FOREIGN KEY (expense_id) REFERENCES expenses (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dito ginagawa ang table para sa budget kada buwan (1 row bawat YYYY-MM).
CREATE TABLE IF NOT EXISTS budgets (
  month      CHAR(7)       PRIMARY KEY,
  amount     DECIMAL(12,2) NOT NULL,
  updated_at BIGINT        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Profile ng user. Walang seed na dito — kapag wala pang row sa table,
-- ang api.php ang magbabalik ng default ("Owllet User" / "OU") sa /profile.
CREATE TABLE IF NOT EXISTS profiles (
  id              INT          PRIMARY KEY,
  display_name    VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NULL,
  avatar_initials VARCHAR(10)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (Opcional) Kung galing ka sa lumang bersyon ng database at may mga table pa na
-- hindi na ginagamit, puwede itong patakbuhin isang beses para malinis:
-- DROP TABLE IF EXISTS categories, payment_methods, expense_tags, tags, recurring_expenses, settings;
