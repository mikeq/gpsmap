CREATE DATABASE velo;
# change username/password to something secure
GRANT ALL PRIVILEGES ON velo.* to 'username'@'localhost' identified by 'password';
USE velo;

DROP TABLE IF EXISTS gpsdata;

CREATE TABLE gpsdata (
  `devicekey` varchar(30) NOT NULL COMMENT 'Key of device',
  `datatime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'timestamp when datapoint was logged',
  `coord` point NOT NULL COMMENT 'latitude and longitude of logged datapoint',
  `altitude` float(5,1) NOT NULL COMMENT 'altitude in meters',
  `speed` float(6,2) NOT NULL COMMENT 'speed in miles per hour',
  `heading` smallint(6) NOT NULL COMMENT 'direction travelling in degrees',
  `distance` float(6,3) DEFAULT NULL COMMENT 'miles',
  `tracktag` varchar(10) NOT NULL,
  PRIMARY KEY (`devicekey`,`datatime`),
  SPATIAL KEY `idx_coord` (`coord`),
  KEY `idx_devicekey` (`devicekey`),
  KEY `idx_track_time` (`tracktag`,`datatime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS daypoints;

CREATE TABLE daypoints (
dayid INT NOT NULL,
start_coords POINT NOT NULL,
end_coords POINT NOT NULL,
title VARCHAR(200)) CHARSET=utf8;

INSERT INTO daypoints
VALUES
(1, GeomFromText('POINT(55.95021 -3.20325)'), GeomFromText('POINT(55.54653 -2.01098)'), 'Edinburgh to Wooler'),
(2, GeomFromText('POINT(55.54653 -2.01098)'), GeomFromText('POINT(54.99335 -1.4528)'), 'Wooler to Newcastle');

