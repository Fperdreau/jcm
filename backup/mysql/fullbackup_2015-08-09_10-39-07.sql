DROP TABLE pjc_chairs;

CREATE TABLE `pjc_chairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `chair` varchar(200) NOT NULL,
  `presid` bigint(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

INSERT INTO pjc_chairs VALUES("1","2015-08-06","TBA","0");
INSERT INTO pjc_chairs VALUES("2","2015-08-06","TBA","0");
INSERT INTO pjc_chairs VALUES("3","2015-08-13","TBA","0");
INSERT INTO pjc_chairs VALUES("4","2015-08-13","TBA","0");
INSERT INTO pjc_chairs VALUES("5","2015-08-20","TBA","0");
INSERT INTO pjc_chairs VALUES("6","2015-08-20","TBA","0");
INSERT INTO pjc_chairs VALUES("7","2015-08-27","TBA","0");
INSERT INTO pjc_chairs VALUES("8","2015-08-27","TBA","0");



DROP TABLE pjc_config;

CREATE TABLE `pjc_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_post;

CREATE TABLE `pjc_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postid` char(30) NOT NULL,
  `date` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `username` char(30) NOT NULL,
  `homepage` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_presentations;

CREATE TABLE `pjc_presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `up_date` datetime DEFAULT NULL,
  `id_pres` bigint(15) DEFAULT NULL,
  `username` char(30) NOT NULL,
  `type` char(30) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `jc_time` char(15) DEFAULT NULL,
  `title` char(150) DEFAULT NULL,
  `authors` char(150) DEFAULT NULL,
  `summary` text,
  `orator` char(50) DEFAULT NULL,
  `notified` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_session;

CREATE TABLE `pjc_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `status` char(10) DEFAULT 'FREE',
  `time` varchar(200) DEFAULT NULL,
  `type` char(30) NOT NULL,
  `nbpres` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_users;

CREATE TABLE `pjc_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `firstname` char(30) DEFAULT NULL,
  `lastname` char(30) DEFAULT NULL,
  `fullname` char(30) DEFAULT NULL,
  `username` char(30) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `position` char(10) DEFAULT NULL,
  `email` char(100) DEFAULT NULL,
  `notification` int(1) DEFAULT '1',
  `reminder` int(1) DEFAULT '1',
  `nbpres` int(3) DEFAULT NULL,
  `status` char(10) DEFAULT NULL,
  `hash` char(32) DEFAULT NULL,
  `active` int(1) DEFAULT NULL,
  `attempt` int(1) DEFAULT NULL,
  `last_login` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




