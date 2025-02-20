CREATE TABLE repo_setting (
    id INT PRIMARY KEY AUTO_INCREMENT,
    repo_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    value TEXT NOT NULL
);