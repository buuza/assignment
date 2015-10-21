CREATE TABLE users (
id INT(10) AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(20) NOT NULL,
password VARCHAR(256) NOT NULL,
email VARCHAR(50),
name VARCHAR(20)
);

CREATE TABLE etf_data (
id INT(10) AUTO_INCREMENT PRIMARY KEY,
ETF TEXT NOT NULL,
html TEXT NOT NULL,
csv TEXT NOT NULL,
country_weight_html TEXT NOT NULL,
weght_csv TEXT NOT NULL,
sector_html TEXT NOT NULL,
sector_csv TEXT NOT NULL,
addedDateTime date
);

CREATE TABLE user_history(
search_id INT(50) AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(20),
ETF VARCHAR(10),
Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);