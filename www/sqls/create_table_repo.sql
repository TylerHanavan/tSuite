CREATE TABLE repo (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    url VARCHAR(300) NOT NULL,
    download_location VARCHAR(300) NOT NULL
);