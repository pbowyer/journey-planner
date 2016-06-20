
DROP TABLE IF EXISTS `fastest_connection`;
CREATE TABLE `fastest_connection` (
  `departureTime` TIME DEFAULT NULL,
  `arrivalTime` TIME DEFAULT NULL,
  `origin` char(3) NOT NULL,
  `destination` char(3) NOT NULL,
  `service` char(8) NOT NULL,
  PRIMARY KEY (`departureTime`,`arrivalTime`,`origin`,`destination`,`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `timetable_connection`;
CREATE TABLE `timetable_connection` (
  `departureTime` TIME DEFAULT NULL,
  `arrivalTime` TIME DEFAULT NULL,
  `origin` char(3) NOT NULL,
  `destination` char(3) NOT NULL,
  `service` VARCHAR(8) NOT NULL,
  `monday` TINYINT(1) NOT NULL,
  `tuesday` TINYINT(1) NOT NULL,
  `wednesday` TINYINT(1) NOT NULL,
  `thursday` TINYINT(1) NOT NULL,
  `friday` TINYINT(1) NOT NULL,
  `saturday` TINYINT(1) NOT NULL,
  `sunday` TINYINT(1) NOT NULL,
  `startDate` DATE NOT NULL,
  `endDate` DATE NOT NULL,
  `dummy` char(3) DEFAULT '---',
  `dummy2` TIME DEFAULT NULL,
  PRIMARY KEY (`departureTime`,`arrivalTime`,`origin`,`destination`,`service`, `endDate`),
  KEY `startDate` (`startDate`),
  KEY `endDate` (`endDate`),
  KEY `origin` (`origin`),
  KEY `monday` (`monday`),
  KEY `tuesday` (`tuesday`),
  KEY `wednesday` (`wednesday`),
  KEY `thursday` (`thursday`),
  KEY `friday` (`friday`),
  KEY `saturday` (`saturday`),
  KEY `sunday` (`sunday`),
  KEY `destination` (`destination`),
  KEY `arrivalTime` (`arrivalTime`),
  KEY `departureTime` (`departureTime`),
  KEY `service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `shortest_path`;
CREATE TABLE `shortest_path` (
  `origin` char(3) NOT NULL,
  `destination` char(3) NOT NULL,
  `duration` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`origin`,`destination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `interchange`;
CREATE TABLE `interchange` (
  `station` char(3) NOT NULL,
  `duration` int(11) unsigned NOT NULL,
  PRIMARY KEY (`station`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `transfer_pattern`;
CREATE TABLE `transfer_pattern` (
  `id` INT(12) unsigned AUTO_INCREMENT,
  `origin` char(3) NOT NULL,
  `destination` char(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `origin` (`origin`),
  KEY `destination` (`destination`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `transfer_pattern_leg`;
CREATE TABLE `transfer_pattern_leg` (
  `id` INT(12) unsigned AUTO_INCREMENT,
  `transfer_pattern` INT(12) unsigned NOT NULL,
  `origin` char(3) NOT NULL,
  `destination` char(3) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_pattern` (`transfer_pattern`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
