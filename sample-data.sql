DROP DATABASE test;

CREATE DATABASE test;

use test;

CREATE TABLE table_alphabet (
	id INT(6) AUTO_INCREMENT PRIMARY KEY,
	created DATETIME ,
	title VARCHAR(20),
	tipe VARCHAR(20),
	qty INT(2)
)ENGINE = MYISAM;

INSERT INTO table_alphabet (id, created, title, tipe, qty) VALUES
('', now() + interval 1 month, 'alpha', '', 1),
('', now() + interval 1 month, 'bravo', '', 1),
('', now() + interval 1 month, 'charlie', '', 1),
('', now() + interval 1 month, 'delta', '', 1),
('', now() + interval 1 month, 'echo', '', 1),
('', now() + interval 1 month, 'fanta', '', 1),
('', now() + interval 1 month, 'golf', '', 1),
('', now() + interval 1 month, 'hotel', '', 1),
('', now() + interval 1 month, 'india', '', 1),
('', now() + interval 1 month, 'juliet', '', 1),
('', now() + interval 1 month, 'kilo', '', 1),
('', now() + interval 1 month, 'london', '', 1),
('', now() + interval 1 month, 'mama', '', 1),
('', now() + interval 1 month, 'november', '', 1),
('', now() + interval 1 month, 'oscar', '', 1),
('', now() + interval 1 month, 'papa', '', 1),
('', now() + interval 1 month, 'queen', '', 1),
('', now() + interval 1 month, 'romeo', '', 1),
('', now() + interval 1 month, 'sierra', '', 1),
('', now() + interval 1 month, 'tango', '', 1),
('', now() + interval 1 month, 'uniform', '', 1),
('', now() + interval 1 month, 'victor', '', 1),
('', now() + interval 1 month, 'whiskey', '', 1),
('', now() + interval 1 month, 'xray', '', 1),
('', now() + interval 1 month, 'yankee', '', 1),
('', now() + interval 1 month, 'zebra', '', 1);

UPDATE table_alphabet SET created = now() + interval 1 month - INTERVAL (2*26 - 3*id) DAY;
UPDATE table_alphabet SET tipe = 'coffee' WHERE MOD(id, 3) = 0;
UPDATE table_alphabet SET tipe = 'tea' WHERE MOD(id, 5) = 0;
UPDATE table_alphabet SET tipe = 'milk' WHERE tipe = '';
UPDATE table_alphabet SET qty = id * 2;

CREATE TABLE table_log LIKE table_alphabet;
CREATE TABLE table_log_a LIKE table_alphabet;
CREATE TABLE table_log_b LIKE table_alphabet;
CREATE TABLE table_log_c LIKE table_alphabet;

INSERT INTO table_log SELECT * FROM table_alphabet;
INSERT INTO table_log_a SELECT * FROM table_alphabet;
INSERT INTO table_log_b SELECT * FROM table_alphabet;
INSERT INTO table_log_c SELECT * FROM table_alphabet;

DELETE FROM table_log WHERE id < 20;
DELETE FROM table_log_a WHERE id > 8;
DELETE FROM table_log_b WHERE id < 6;
DELETE FROM table_log_b WHERE id > 18;
DELETE FROM table_log_c WHERE id < 15;
DELETE FROM table_log_c WHERE id > 20;

ALTER TABLE table_log_a DROP tipe;
ALTER TABLE table_log_b DROP qty;


CREATE TABLE table_ack LIKE table_alphabet;
CREATE TABLE table_ack_a LIKE table_alphabet;
CREATE TABLE table_ack_b LIKE table_alphabet;
CREATE TABLE table_ack_c LIKE table_alphabet;

INSERT INTO table_ack SELECT * FROM table_alphabet;
INSERT INTO table_ack_a SELECT * FROM table_alphabet;
INSERT INTO table_ack_b SELECT * FROM table_alphabet;
INSERT INTO table_ack_c SELECT * FROM table_alphabet;

DELETE FROM table_ack WHERE id < 20;
DELETE FROM table_ack_a WHERE id > 8;
DELETE FROM table_ack_b WHERE id < 6;
DELETE FROM table_ack_b WHERE id > 18;
DELETE FROM table_ack_c WHERE id < 15;
DELETE FROM table_ack_c WHERE id > 20;

ALTER TABLE table_ack_a DROP tipe;
ALTER TABLE table_ack_b DROP qty;
