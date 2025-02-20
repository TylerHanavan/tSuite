CREATE TABLE commit (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    repo_id INT NOT NULL,
    hash VARCHAR(40) NOT NULL,
    date DATETIME NOT NULL,
    author VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    test_status TINYINT,
    success_tests INT,
    failed_tests INT,
    download_duration INT,
    install_duration INT,
    test_duration INT,
    do_retest_flag TINYINT DEFAULT 0
);