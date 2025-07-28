-- Create users table if not exists
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create payment_methods table if not exists
CREATE TABLE IF NOT EXISTS payment_methods (
    method_id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table if not exists
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL,
    type ENUM('expense', 'revenue') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create bills table
CREATE TABLE IF NOT EXISTS bills (
    bill_number INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    client_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    amount DECIMAL(10,2),
    issue_date DATE,
    due_date DATE,
    status ENUM('Unpaid', 'Paid', 'Overdue'),
    payment_date DATE,
    method_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (method_id) REFERENCES payment_methods(method_id)
);

-- Create revenue table
CREATE TABLE IF NOT EXISTS revenue (
    revenue_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    bill_number INT,  -- Linked to the bill that generated revenue
    total_revenue DECIMAL(10,2),
    revenue_date DATE,
    method_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (bill_number) REFERENCES bills(bill_number),
    FOREIGN KEY (method_id) REFERENCES payment_methods(method_id)
);

-- Create expenses table if not exists
CREATE TABLE IF NOT EXISTS expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    category_id INT,
    amount DECIMAL(10,2),
    expense_date DATE,
    description TEXT,
    receipt_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Create staff table if not exists
CREATE TABLE IF NOT EXISTS staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    position VARCHAR(100),
    salary DECIMAL(10,2),
    hire_date DATE,
    joining_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert default payment methods
INSERT INTO payment_methods (method_name) VALUES 
('Cash'),
('Bank Transfer'),
('UPI'),
('Credit Card'),
('Debit Card');

-- Insert default expense categories
INSERT INTO categories (category_name, type) VALUES 
('Rent', 'expense'),
('Utilities', 'expense'),
('Salaries', 'expense'),
('Supplies', 'expense'),
('Marketing', 'expense'),
('Other', 'expense'),
('Sales', 'revenue'),
('Tutorials', 'revenue'),
('Event Decorations', 'revenue'); 