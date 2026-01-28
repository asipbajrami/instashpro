-- Use mysql_native_password for compatibility with MariaDB clients (mysqldump in PHP container)
ALTER USER 'instashpro'@'%' IDENTIFIED WITH mysql_native_password BY 'instashpro_password';
FLUSH PRIVILEGES;
