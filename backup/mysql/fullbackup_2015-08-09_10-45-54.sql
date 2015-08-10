DROP TABLE pjc_chairs;

CREATE TABLE `pjc_chairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `chair` varchar(200) NOT NULL,
  `presid` bigint(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_config;

CREATE TABLE `pjc_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1;

INSERT INTO pjc_config VALUES("1","status","On");
INSERT INTO pjc_config VALUES("2","app_name","Journal Club Manager");
INSERT INTO pjc_config VALUES("3","version","v1.3.1");
INSERT INTO pjc_config VALUES("4","author","Florian Perdreau");
INSERT INTO pjc_config VALUES("5","repository","https://github.com/Fperdreau/jcm");
INSERT INTO pjc_config VALUES("6","sitetitle","Journal Club");
INSERT INTO pjc_config VALUES("7","site_url","http://localhost:8080/jcm/");
INSERT INTO pjc_config VALUES("8","clean_day","10");
INSERT INTO pjc_config VALUES("9","max_nb_attempt","5");
INSERT INTO pjc_config VALUES("10","jc_day","thursday");
INSERT INTO pjc_config VALUES("11","room","H432");
INSERT INTO pjc_config VALUES("12","jc_time_from","17:00");
INSERT INTO pjc_config VALUES("13","jc_time_to","18:00");
INSERT INTO pjc_config VALUES("14","max_nb_session","2");
INSERT INTO pjc_config VALUES("15","nbsessiontoplan","10");
INSERT INTO pjc_config VALUES("16","chair_assign","manual");
INSERT INTO pjc_config VALUES("17","session_type","{\"Journal Club\":[\"TBA\"],\"Business Meeting\":[\"TBA\"],\"No group meeting\":[\"TBA\"]}");
INSERT INTO pjc_config VALUES("18","session_type_default","Journal Club");
INSERT INTO pjc_config VALUES("19","pres_type","paper,research,methodology,guest,minute");
INSERT INTO pjc_config VALUES("20","lab_name","Your Lab name");
INSERT INTO pjc_config VALUES("21","lab_street","Your Lab address");
INSERT INTO pjc_config VALUES("22","lab_postcode","Your Lab postal code");
INSERT INTO pjc_config VALUES("23","lab_city","Your Lab city");
INSERT INTO pjc_config VALUES("24","lab_country","Your Lab country");
INSERT INTO pjc_config VALUES("25","lab_mapurl","Google Map");
INSERT INTO pjc_config VALUES("26","mail_from","jc@journalclub.com");
INSERT INTO pjc_config VALUES("27","mail_from_name","Journal Club");
INSERT INTO pjc_config VALUES("28","mail_host","smtp.gmail.com");
INSERT INTO pjc_config VALUES("29","mail_port","465");
INSERT INTO pjc_config VALUES("30","mail_username","");
INSERT INTO pjc_config VALUES("31","mail_password","");
INSERT INTO pjc_config VALUES("32","SMTP_secure","ssl");
INSERT INTO pjc_config VALUES("33","pre_header","[Journal Club]");
INSERT INTO pjc_config VALUES("34","upl_types","pdf,doc,docx,ppt,pptx,opt,odp");
INSERT INTO pjc_config VALUES("35","upl_maxsize","10000000");



DROP TABLE pjc_crons;

CREATE TABLE `pjc_crons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(20) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `dayName` char(15) DEFAULT NULL,
  `dayNb` int(2) DEFAULT NULL,
  `hour` int(2) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `status` char(3) DEFAULT NULL,
  `options` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_media;

CREATE TABLE `pjc_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `fileid` char(20) DEFAULT NULL,
  `filename` char(20) DEFAULT NULL,
  `presid` char(20) DEFAULT NULL,
  `type` char(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE pjc_plugins;

CREATE TABLE `pjc_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(20) DEFAULT NULL,
  `version` char(5) DEFAULT NULL,
  `page` char(20) DEFAULT NULL,
  `status` char(3) DEFAULT NULL,
  `options` text,
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




