DROP DATABASE IF EXISTS test_db;
CREATE DATABASE test_db;
USE test_db;

DROP TABLE IF EXISTS counter;

CREATE TABLE counter (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP
);
