# calories
Calorie counter and weight tracker

# Create DB & load schema

    > CREATE DATABASE calories;

    mysql -u root -p calories < schema.sql

# Create DB User

    CREATE USER 'calories'@'localhost' IDENTIFIED BY 'password';
    GRANT EXECUTE on calories.* TO 'calories'@'localhost';
    FLUSH PRIVILEGES;
