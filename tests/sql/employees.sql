/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50612
Source Host           : localhost:3306
Source Database       : test

Target Server Type    : MYSQL
Target Server Version : 50612
File Encoding         : 65001

Date: 2014-09-20 15:27:11
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for employees
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `emp_no` int(11) NOT NULL,
  `birth_date` date NOT NULL,
  `first_name` varchar(14) NOT NULL,
  `last_name` varchar(16) NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `hire_date` date NOT NULL,
  PRIMARY KEY (`emp_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of employees
-- ----------------------------
INSERT INTO `employees` VALUES ('10001', '1953-09-02', 'Georgi', 'Facello', 'M', '1986-06-26');
INSERT INTO `employees` VALUES ('10002', '1964-06-02', 'Bezalel', 'Simmel', 'F', '1985-11-21');
INSERT INTO `employees` VALUES ('10003', '1959-12-03', 'Parto', 'Bamford', 'M', '1986-08-28');
INSERT INTO `employees` VALUES ('10004', '1954-05-01', 'Chirstian', 'Koblick', 'M', '1986-12-01');
INSERT INTO `employees` VALUES ('10005', '1955-01-21', 'Kyoichi', 'Maliniak', 'M', '1989-09-12');
INSERT INTO `employees` VALUES ('10006', '1953-04-20', 'Anneke', 'Preusig', 'F', '1989-06-02');
INSERT INTO `employees` VALUES ('10007', '1957-05-23', 'Tzvetan', 'Zielinski', 'F', '1989-02-10');
INSERT INTO `employees` VALUES ('10008', '1958-02-19', 'Saniya', 'Kalloufi', 'M', '1994-09-15');
INSERT INTO `employees` VALUES ('10009', '1952-04-19', 'Sumant', 'Peac', 'F', '1985-02-18');
INSERT INTO `employees` VALUES ('10010', '1963-06-01', 'Duangkaew', 'Piveteau', 'F', '1989-08-24');
INSERT INTO `employees` VALUES ('10011', '1953-11-07', 'Mary', 'Sluis', 'F', '1990-01-22');
INSERT INTO `employees` VALUES ('10012', '1960-10-04', 'Patricio', 'Bridgland', 'M', '1992-12-18');
INSERT INTO `employees` VALUES ('10013', '1963-06-07', 'Eberhardt', 'Terkki', 'M', '1985-10-20');
INSERT INTO `employees` VALUES ('10014', '1956-02-12', 'Berni', 'Genin', 'M', '1987-03-11');
INSERT INTO `employees` VALUES ('10015', '1959-08-19', 'Guoxiang', 'Nooteboom', 'M', '1987-07-02');
INSERT INTO `employees` VALUES ('10016', '1961-05-02', 'Kazuhito', 'Cappelletti', 'M', '1995-01-27');
INSERT INTO `employees` VALUES ('10017', '1979-12-16', 'George', 'Nooteboom', 'M', '1993-07-02');
INSERT INTO `employees` VALUES ('10018', '1987-03-15', 'Gordon', 'Nooteboom', 'M', '1990-11-26');
INSERT INTO `employees` VALUES ('10019', '1954-02-10', 'Peter', 'Terkki', 'M', '1999-03-03');
INSERT INTO `employees` VALUES ('10020', '1963-06-07', 'Grace', 'Nooteboom', 'F', '1989-08-24');
INSERT INTO `employees` VALUES ('10021', '1956-06-23', 'Georgi', 'Nooteboom', 'M', '1988-01-27');
INSERT INTO `employees` VALUES ('10022', '1988-02-15', 'Annie', 'Nooteboom', 'F', '1999-11-23');
