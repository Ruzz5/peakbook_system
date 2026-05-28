CREATE DATABASE IF NOT EXISTS peakbook_db;
USE peakbook_db;
CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) NOT NULL UNIQUE,
    firstname   VARCHAR(50)  NOT NULL,
    lastname    VARCHAR(50)  NOT NULL,
    password    VARCHAR(255) NOT NULL,         
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id     INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name   VARCHAR(100) NOT NULL,
    contact_number  VARCHAR(20),
    address         VARCHAR(255),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    book_id         INT AUTO_INCREMENT PRIMARY KEY,
    book_title      VARCHAR(255) NOT NULL,
    quantity        INT          NOT NULL DEFAULT 0,
    price           DECIMAL(10,2) NOT NULL,
    book_condition  VARCHAR(50),               
    supplier_id     INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS customers (
    customer_id     INT AUTO_INCREMENT PRIMARY KEY,
    fullname        VARCHAR(100) NOT NULL,
    address         VARCHAR(255),
    contact_number  VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS couriers (
    courier_id      INT AUTO_INCREMENT PRIMARY KEY,
    courier_name    VARCHAR(100) NOT NULL,
    contact_number  VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS orders (
    order_id        INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    courier_id      INT,
    book_id         INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    order_date      DATE,
    total_amount    DECIMAL(10,2),
    order_status    VARCHAR(30) DEFAULT 'Pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id)  REFERENCES couriers(courier_id)  ON DELETE SET NULL,
    FOREIGN KEY (book_id)     REFERENCES books(book_id)        ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL,
    customer_id     INT NOT NULL,
    payment_date    DATE,
    payment_method  VARCHAR(50),
    amount_paid     DECIMAL(10,2),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id)    REFERENCES orders(order_id)       ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
);
INSERT INTO users (email, firstname, lastname, password) VALUES
('rizz@peakbook.com', 'Rizza', 'Paga', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

INSERT INTO suppliers (supplier_name, contact_number, address) VALUES
('National Book Store', '09171234567', 'Makati City'),
('Fully Booked', '09181234567', 'BGC, Taguig');

INSERT INTO books (book_title, quantity, price, book_condition, supplier_id) VALUES
('No Longer Human', 50, 350.00, 'New', 1),
('Kafka on the Shore', 30, 450.00, 'New', 2),
('The Great Gatsby', 25, 280.00, 'Good', 1),
('1984', 40, 320.00, 'New', 2),
('Dune', 20, 520.00, 'New', 1);

INSERT INTO customers (fullname, address, contact_number) VALUES
('Juan dela Cruz', 'Davao City', '09201234567'),
('Maria Santos', 'Cebu City', '09301234567'),
('Pedro Reyes', 'Manila', '09401234567');

INSERT INTO couriers (courier_name, contact_number) VALUES
('J&T Express', '09501234567'),
('Ninja Van', '09601234567'),
('LBC', '09701234567');
