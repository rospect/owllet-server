
CREATE DATABASE IF NOT EXISTS owllet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE owllet_db;

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

CREATE TABLE IF NOT EXISTS receipts (
  expense_id VARCHAR(36)  PRIMARY KEY,
  image_data MEDIUMTEXT   NOT NULL,
  created_at BIGINT       NOT NULL,
  CONSTRAINT fk_receipt_expense
    FOREIGN KEY (expense_id) REFERENCES expenses (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS budgets (
  month      CHAR(7)       PRIMARY KEY,
  amount     DECIMAL(12,2) NOT NULL,
  updated_at BIGINT        NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profiles (
  id              INT          PRIMARY KEY,
  display_name    VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NULL,
  avatar_initials VARCHAR(10)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

