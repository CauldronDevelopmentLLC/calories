CREATE TABLE IF NOT EXISTS `calories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `cals` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `cals` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `goal` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_date` (`uid`, `date`)
) CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `weight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `weight` float NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_date` (`uid`, `date`)
) CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `height` int(11) NOT NULL,
  `sex` int(11) NOT NULL,
  `dob` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) CHARSET=utf8;


DELIMITER //
DROP FUNCTION IF EXISTS GetUserID;
CREATE FUNCTION GetUserID(_name VARCHAR(255))
  RETURNS INT
BEGIN
  SET @uid = NULL;
  SELECT id INTO @uid FROM users WHERE name = _name;

  IF @uid IS NULL THEN
    SIGNAL SQLSTATE '02000' SET MESSAGE_TEXT = 'User not found.';
  END IF;

  RETURN @uid;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS GetUsers;
CREATE PROCEDURE GetUsers()
SQL SECURITY DEFINER
BEGIN
  SELECT name, height, IF(sex, 'female', 'male') sex, dob FROM users;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS GetUser;
CREATE PROCEDURE GetUser(_user VARCHAR(255), _date DATE, _history INT UNSIGNED)
SQL SECURITY DEFINER
BEGIN
  SET @uid     = GetUserID(_user);
  SET @date    = IFNULL(_date, DATE(NOW()));
  SET @history = IFNULL(_history, 0);
  SET @min     = DATE_SUB(@date, INTERVAL @history DAY);

  SET @last_weight = NULL;
  SELECT weight INTO @last_weight FROM weight
    WHERE uid = @uid AND date < @date ORDER BY date DESC LIMIT 1;

  SELECT height, IF(sex, 'female', 'male') sex, dob, weight,
    @last_weight last_weight, goal FROM users u
    JOIN goals g ON g.uid = u.id AND g.date = (
      SELECT MAX(date) FROM goals WHERE date <= @date AND uid = @uid)
    JOIN weight w ON w.uid = u.id AND w.date = (
      SELECT MAX(date) FROM weight WHERE date <= @date AND uid = @uid)
    WHERE u.id = @uid;

  SELECT id, cals, item FROM calories WHERE date = @date AND uid = @uid;
  SELECT id, cals, item FROM activity WHERE date = @date AND uid = @uid;

  SELECT weight, date FROM weight
    WHERE @min < date AND date <= @date AND uid = @uid ORDER BY DATE;

  SELECT goal, date FROM goals
    WHERE @min < date AND date <= @date AND uid = @uid ORDER BY DATE;

  SELECT SUM(cals) cals, date FROM calories
    WHERE uid = @uid AND @min < date AND date < @date AND uid = @uid
    GROUP BY date ORDER BY date;

  SELECT SUM(cals) cals, date FROM activity
    WHERE uid = @uid AND @min < date AND date < @date AND uid = @uid
    GROUP BY date ORDER BY date;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS AddCalories;
CREATE PROCEDURE AddCalories(
  _user VARCHAR(255), _date DATE, _calories INT UNSIGNED,
  _description VARCHAR(255))
SQL SECURITY DEFINER
BEGIN
  SET @date = IFNULL(_date, DATE(NOW()));

  INSERT INTO calories (date, cals, item, uid)
    VALUES (@date, _calories, _description, GetUserID(_user));
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS DeleteCalories;
CREATE PROCEDURE DeleteCalories(_user VARCHAR(255), _id INT UNSIGNED)
SQL SECURITY DEFINER
BEGIN
  DELETE FROM calories WHERE id = _id AND uid = GetUserID(_user);
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS AddActivity;
CREATE PROCEDURE AddActivity(
  _user VARCHAR(255), _date DATE, _calories INT UNSIGNED,
  _description VARCHAR(255))
SQL SECURITY DEFINER
BEGIN
  SET @date = IFNULL(_date, DATE(NOW()));

  INSERT INTO activity (date, cals, item, uid)
    VALUES (@date, _calories, _description, GetUserID(_user));
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS DeleteActivity;
CREATE PROCEDURE DeleteActivity(_user VARCHAR(255), _id INT UNSIGNED)
SQL SECURITY DEFINER
BEGIN
  DELETE FROM activity WHERE id = _id AND uid = GetUserID(_user);
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS SetWeight;
CREATE PROCEDURE SetWeight(_user VARCHAR(255), _date DATE, _weight FLOAT)
SQL SECURITY DEFINER
BEGIN
  INSERT INTO weight (date, weight, uid)
    VALUES (_date, _weight, GetUserID(_user))
    ON DUPLICATE KEY UPDATE weight = VALUES(weight);
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS SetGoal;
CREATE PROCEDURE SetGoal(_user VARCHAR(255), _date DATE, _goal INT UNSIGNED)
SQL SECURITY DEFINER
BEGIN
  INSERT INTO goals (date, goal, uid) VALUES (_date, _goal, GetUserID(_user))
    ON DUPLICATE KEY UPDATE goal = VALUES(goal);
END //
DELIMITER ;
