CREATE TABLE scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    ip VARCHAR(100),
    port INT
);

CREATE TABLE cameras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scale_id INT,
    name VARCHAR(100),
    url VARCHAR(255),
    FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE CASCADE
);

CREATE TABLE weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scale_id INT,
    weight FLOAT,
    timestamp DATETIME,
    stable BOOLEAN,
    FOREIGN KEY (scale_id) REFERENCES scales(id) ON DELETE CASCADE
);
