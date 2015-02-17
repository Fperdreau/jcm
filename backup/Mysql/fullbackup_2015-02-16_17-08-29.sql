DROP TABLE IF EXISTS jcm_config;

CREATE TABLE `jcm_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;

INSERT INTO jcm_config VALUES("1","app_name","Journal Club Manager");
INSERT INTO jcm_config VALUES("2","version","v1.3");
INSERT INTO jcm_config VALUES("3","author","Florian Perdreau");
INSERT INTO jcm_config VALUES("4","repository","https://github.com/Fperdreau/jcm");
INSERT INTO jcm_config VALUES("5","sitetitle","Journal Club");
INSERT INTO jcm_config VALUES("6","site_url","http://localhost/jcm/");
INSERT INTO jcm_config VALUES("7","clean_day","10");
INSERT INTO jcm_config VALUES("8","jc_day","thursday");
INSERT INTO jcm_config VALUES("9","room","H432");
INSERT INTO jcm_config VALUES("10","jc_time_from","17:00");
INSERT INTO jcm_config VALUES("11","jc_time_to","18:00");
INSERT INTO jcm_config VALUES("12","notification","sunday");
INSERT INTO jcm_config VALUES("13","max_nb_session","2");
INSERT INTO jcm_config VALUES("14","reminder","1");
INSERT INTO jcm_config VALUES("15","lab_name","Your Lab name");
INSERT INTO jcm_config VALUES("16","lab_street","Your Lab address");
INSERT INTO jcm_config VALUES("17","lab_postcode","Your Lab postal code");
INSERT INTO jcm_config VALUES("18","lab_city","Your Lab city");
INSERT INTO jcm_config VALUES("19","lab_country","Your Lab country");
INSERT INTO jcm_config VALUES("20","lab_mapurl","Google Map");
INSERT INTO jcm_config VALUES("21","mail_from","jc@journalclub.com");
INSERT INTO jcm_config VALUES("22","mail_from_name","Journal Club");
INSERT INTO jcm_config VALUES("23","mail_host","smtp.gmail.com");
INSERT INTO jcm_config VALUES("24","mail_port","465");
INSERT INTO jcm_config VALUES("25","mail_username","experience.dessin@gmail.com");
INSERT INTO jcm_config VALUES("26","mail_password","[Drop69]");
INSERT INTO jcm_config VALUES("27","SMTP_secure","ssl");
INSERT INTO jcm_config VALUES("28","pre_header","[Journal Club]");
INSERT INTO jcm_config VALUES("29","upl_types","pdf,doc,docx,ppt,pptx,opt,odp");
INSERT INTO jcm_config VALUES("30","upl_maxsize","10000000");


DROP TABLE IF EXISTS jcm_post;

CREATE TABLE `jcm_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `post` text,
  `username` char(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS jcm_presentations;

CREATE TABLE `jcm_presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `up_date` datetime DEFAULT NULL,
  `id_pres` bigint(15) DEFAULT NULL,
  `type` char(30) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `jc_time` char(15) DEFAULT NULL,
  `title` char(150) DEFAULT NULL,
  `authors` char(150) DEFAULT NULL,
  `summary` text,
  `link` text,
  `orator` char(50) DEFAULT NULL,
  `presented` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS jcm_users;

CREATE TABLE `jcm_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `firstname` char(30) DEFAULT NULL,
  `lastname` char(30) DEFAULT NULL,
  `fullname` char(30) DEFAULT NULL,
  `username` char(30) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `position` char(10) DEFAULT NULL,
  `email` char(30) DEFAULT NULL,
  `notification` int(1) DEFAULT NULL,
  `reminder` int(1) DEFAULT NULL,
  `nbpres` int(3) DEFAULT NULL,
  `status` char(10) DEFAULT NULL,
  `hash` char(32) DEFAULT NULL,
  `active` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO jcm_users VALUES("1","2015-02-16 16:50:36","drop","","drop","drop","sha256:1000:Nxy8aDul1Fn04/84CmgvtOxa9YKFbzEF:F9Kam","","dropfantasy@msn.com","1","1","0","admin","ae0eb3eed39d2bcef4622b2499a05fe6","1");


DROP TABLE IF EXISTS pjc_config;

CREATE TABLE `pjc_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` char(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

INSERT INTO pjc_config VALUES("1","app_name","Journal Club Manager");
INSERT INTO pjc_config VALUES("2","version","v1.2.2");
INSERT INTO pjc_config VALUES("3","author","Florian Perdreau");
INSERT INTO pjc_config VALUES("4","repository","https://github.com/Fperdreau/jcm");
INSERT INTO pjc_config VALUES("5","sitetitle","Journal Club");
INSERT INTO pjc_config VALUES("6","site_url","(e.g. http://www.mydomain.com/Pjc/)");
INSERT INTO pjc_config VALUES("7","clean_day","10");
INSERT INTO pjc_config VALUES("8","jc_day","thursday");
INSERT INTO pjc_config VALUES("9","room","H432");
INSERT INTO pjc_config VALUES("10","jc_time_from","17:00");
INSERT INTO pjc_config VALUES("11","jc_time_to","18:00");
INSERT INTO pjc_config VALUES("12","notification","sunday");
INSERT INTO pjc_config VALUES("13","max_nb_session","2");
INSERT INTO pjc_config VALUES("14","reminder","1");
INSERT INTO pjc_config VALUES("15","lab_name","Your Lab name");
INSERT INTO pjc_config VALUES("16","lab_street","Your Lab address");
INSERT INTO pjc_config VALUES("17","lab_postcode","Your Lab postal code");
INSERT INTO pjc_config VALUES("18","lab_city","Your Lab city");
INSERT INTO pjc_config VALUES("19","lab_country","Your Lab country");
INSERT INTO pjc_config VALUES("20","lab_mapurl","");
INSERT INTO pjc_config VALUES("21","mail_from","jc@journalclub.com");
INSERT INTO pjc_config VALUES("22","mail_from_name","Journal Club");
INSERT INTO pjc_config VALUES("23","mail_host","smtp.gmail.com");
INSERT INTO pjc_config VALUES("24","mail_port","465");
INSERT INTO pjc_config VALUES("25","mail_username","");
INSERT INTO pjc_config VALUES("26","mail_password","");
INSERT INTO pjc_config VALUES("27","SMTP_secure","ssl");
INSERT INTO pjc_config VALUES("28","pre_header","[Journal Club]");


DROP TABLE IF EXISTS pjc_post;

CREATE TABLE `pjc_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `post` text,
  `username` char(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS pjc_presentations;

CREATE TABLE `pjc_presentations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `up_date` datetime DEFAULT NULL,
  `id_pres` bigint(15) DEFAULT NULL,
  `type` char(30) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `jc_time` char(15) DEFAULT NULL,
  `title` char(150) DEFAULT NULL,
  `authors` char(150) DEFAULT NULL,
  `summary` text,
  `link` text,
  `orator` char(50) DEFAULT NULL,
  `presented` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS pjc_users;

CREATE TABLE `pjc_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT '2015-02-13 04:42:50',
  `firstname` char(30) DEFAULT NULL,
  `lastname` char(30) DEFAULT NULL,
  `fullname` char(30) DEFAULT NULL,
  `username` char(30) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `position` char(10) DEFAULT NULL,
  `email` char(30) DEFAULT NULL,
  `notification` int(1) DEFAULT '1',
  `reminder` int(1) DEFAULT '1',
  `nbpres` int(3) DEFAULT NULL,
  `status` char(10) DEFAULT NULL,
  `hash` char(32) DEFAULT NULL,
  `active` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO pjc_users VALUES("1","2015-02-13 16:43:01","","","","drop","sha256:1000:pByiu/eSZUmYgEOJ/nb63EKKJJDM0s6K:kxvqG","","dropfantasy@msn.com","1","1","0","admin","757b505cfd34c64c85ca5b5690ee5293","1");


DROP TABLE IF EXISTS rmd_config;

CREATE TABLE `rmd_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

INSERT INTO rmd_config VALUES("1","sitetitle","RankMyDrawings");
INSERT INTO rmd_config VALUES("2","site_url","(e.g. http://www.mydomain.com/RankMyDrawings/)");
INSERT INTO rmd_config VALUES("3","npair","0");
INSERT INTO rmd_config VALUES("4","expon","on");
INSERT INTO rmd_config VALUES("5","initial_score","1500");
INSERT INTO rmd_config VALUES("6","filter","on");
INSERT INTO rmd_config VALUES("7","redirecturl","http://www.google.fr");
INSERT INTO rmd_config VALUES("8","instruction","<p>\n                    During a succession of trials, pairs of hand-drawings are going to be presented, as well as\n                    the original model (see opposite).<br><br>\n                    Your task is to choose (by clicking on) which of the two drawings is closer to the original.\n                    <b>Importantly</b>, do not make your decision on the basis of aesthetism or style!.<br>\n                    </p>");
INSERT INTO rmd_config VALUES("9","consent","<p><strong>Experiment&apos;s aim :</strong> All data of this experiment are\n                    collected for scientific reasons and will contribute to a better understanding\n                    of the brain and of visual perception. These data might be published in\n                    scientific journals.</p>\n                    <p><strong>Experimental task: </strong>You are going to see pictures on your\n                    monitor and you will have to give responses by clicking on a mouse.</p>\n                    <p><strong>Remuneration: </strong>This experiment is not remunerated.</p>\n                    <p><strong>Confidentiality: </strong>Your participation to this experiment is\n                    confidential and your identity will not be recorder with your data.\n                    We attribute a code to your responses, and the list relating your name to\n                    this code will be destroyed once the data will be recorded and analyzed.\n                    You have the right to access and to modify your data accordingly to the\n                    Law on Information Technology andCivil Liberties. Any publication of the\n                    results will not include identifying individual results.</p>\n                    <p><strong>Participation: </strong>Your participation to this experiment is\n                    entirely voluntary and you can, at any time, stop the experiment.</p>");


DROP TABLE IF EXISTS rmd_ref_drawings;

CREATE TABLE `rmd_ref_drawings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(50) DEFAULT NULL,
  `filename` char(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `nb_users` int(11) DEFAULT NULL,
  `max_nb_users` int(11) DEFAULT NULL,
  `nb_draw` int(11) DEFAULT NULL,
  `max_nb_pairs` int(5) DEFAULT NULL,
  `initial_score` int(5) DEFAULT NULL,
  `nb_pairs` int(5) DEFAULT NULL,
  `status` char(3) DEFAULT NULL,
  `filter` char(3) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



DROP TABLE IF EXISTS rmd_users;

CREATE TABLE `rmd_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(50) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO rmd_users VALUES("1","drop","sha256:1000:WeXL9yHNSxL67faust4lkSETl78V9qqg:fF3Vy","dropfantasy@msn.com");


