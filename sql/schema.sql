create database vm_portal;
use vm_portal;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('user','admin') DEFAULT 'user'
);

CREATE TABLE vm_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vm_name VARCHAR(50) NOT NULL,
    type ENUM('bronze','silver','gold') NOT NULL,
    template VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','creating','done','error','rejected') DEFAULT 'pending',
    vmid INT DEFAULT NULL,
    ip VARCHAR(50),
    hostname VARCHAR(100),
    credentials TEXT,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);