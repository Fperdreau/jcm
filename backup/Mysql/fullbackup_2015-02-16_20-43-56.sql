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

INSERT INTO jcm_users VALUES("1","2015-02-16 19:54:25","drop","","drop","drop","sha256:1000:IdCFaW4Nb9GC9LWfWJdnSz7oDTGEN839:CCr/E","","dropfantasy@msn.com","1","1","0","admin","1068c6e4c8051cfd4e9ea8072e3189e2","1");


DROP TABLE IF EXISTS pjc_config;

CREATE TABLE `pjc_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` char(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;

INSERT INTO pjc_config VALUES("1","app_name","Journal Club Manager");
INSERT INTO pjc_config VALUES("2","version","v1.3");
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
INSERT INTO pjc_config VALUES("29","upl_types","pdf,doc,docx,ppt,pptx,opt,odp");
INSERT INTO pjc_config VALUES("30","upl_maxsize","10000000");


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
  `date` datetime DEFAULT '2015-02-16 10:08:22',
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



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
INSERT INTO rmd_config VALUES("8","instruction","<p>\n					During a succession of trials, pairs of hand-drawings are going to be presented, as well as\n					the original model (see opposite).<br><br>\n					Your task is to choose (by clicking on) which of the two drawings is closer to the original.\n					<b>Importantly</b>, do not make your decision on the basis of aesthetism or style!.<br>\n					</p>");
INSERT INTO rmd_config VALUES("9","consent","<p><strong>Experiment&apos;s aim :</strong> All data of this experiment are\n					collected for scientific reasons and will contribute to a better understanding\n					of the brain and of visual perception. These data might be published in\n					scientific journals.</p>\n					<p><strong>Experimental task: </strong>You are going to see pictures on your\n					monitor and you will have to give responses by clicking on a mouse.</p>\n					<p><strong>Remuneration: </strong>This experiment is not remunerated.</p>\n					<p><strong>Confidentiality: </strong>Your participation to this experiment is\n					confidential and your identity will not be recorder with your data.\n					We attribute a code to your responses, and the list relating your name to\n					this code will be destroyed once the data will be recorded and analyzed.\n					You have the right to access and to modify your data accordingly to the\n					Law on Information Technology andCivil Liberties. Any publication of the\n					results will not include identifying individual results.</p>\n					<p><strong>Participation: </strong>Your participation to this experiment is\n					entirely voluntary and you can, at any time, stop the experiment.</p>");


DROP TABLE IF EXISTS rmd_photo_comp_mat;

CREATE TABLE `rmd_photo_comp_mat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(20) DEFAULT NULL,
  `photo_4004` int(11) NOT NULL,
  `photo_5647` int(11) NOT NULL,
  `photo_839` int(11) NOT NULL,
  `photo_9617` int(11) NOT NULL,
  `photo_756` int(11) NOT NULL,
  `photo_4678` int(11) NOT NULL,
  `photo_7940` int(11) NOT NULL,
  `photo_9707` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_comp_mat VALUES("1","photo_4004","0","1","2","0","1","1","0","0");
INSERT INTO rmd_photo_comp_mat VALUES("2","photo_5647","1","0","1","0","0","1","1","1");
INSERT INTO rmd_photo_comp_mat VALUES("3","photo_839","1","1","0","0","1","1","1","1");
INSERT INTO rmd_photo_comp_mat VALUES("4","photo_9617","0","0","0","0","1","1","1","1");
INSERT INTO rmd_photo_comp_mat VALUES("5","photo_756","1","0","1","1","0","1","1","1");
INSERT INTO rmd_photo_comp_mat VALUES("6","photo_4678","1","1","1","1","1","0","1","1");
INSERT INTO rmd_photo_comp_mat VALUES("7","photo_7940","0","1","1","1","1","1","0","1");
INSERT INTO rmd_photo_comp_mat VALUES("8","photo_9707","0","1","1","1","1","1","1","0");


DROP TABLE IF EXISTS rmd_photo_content;

CREATE TABLE `rmd_photo_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` char(20) DEFAULT NULL,
  `lang` char(20) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_content VALUES("1","instruction","en","<p>\n					During a succession of trials, pairs of hand-drawings are going to be presented, as well as\n					the original model (see opposite).<br><br>\n					Your task is to choose (by clicking on) which of the two drawings is closer to the original.\n					<b>Importantly</b>, do not make your decision on the basis of aesthetism or style!.<br>\n					</p>");
INSERT INTO rmd_photo_content VALUES("2","consent","en","<p><strong>Experiment&apos;s aim :</strong> All data of this experiment are\n					collected for scientific reasons and will contribute to a better understanding\n					of the brain and of visual perception. These data might be published in\n					scientific journals.</p>\n					<p><strong>Experimental task: </strong>You are going to see pictures on your\n					monitor and you will have to give responses by clicking on a mouse.</p>\n					<p><strong>Remuneration: </strong>This experiment is not remunerated.</p>\n					<p><strong>Confidentiality: </strong>Your participation to this experiment is\n					confidential and your identity will not be recorder with your data.\n					We attribute a code to your responses, and the list relating your name to\n					this code will be destroyed once the data will be recorded and analyzed.\n					You have the right to access and to modify your data accordingly to the\n					Law on Information Technology andCivil Liberties. Any publication of the\n					results will not include identifying individual results.</p>\n					<p><strong>Participation: </strong>Your participation to this experiment is\n					entirely voluntary and you can, at any time, stop the experiment.</p>");


DROP TABLE IF EXISTS rmd_photo_ranking;

CREATE TABLE `rmd_photo_ranking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(20) DEFAULT NULL,
  `filename` char(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `nb_win` int(4) NOT NULL,
  `nb_occ` int(4) NOT NULL,
  `score` float NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_ranking VALUES("1","photo_4004","photo_4004.png","2015-02-09","1","4","1294.27","0");
INSERT INTO rmd_photo_ranking VALUES("2","photo_5647","photo_5647.png","2015-02-09","4","5","1628.14","0");
INSERT INTO rmd_photo_ranking VALUES("3","photo_839","photo_839.png","2015-02-09","2","6","1328.68","0");
INSERT INTO rmd_photo_ranking VALUES("4","photo_9617","photo_9617.png","2015-02-09","4","4","1868.24","0");
INSERT INTO rmd_photo_ranking VALUES("5","photo_756","photo_756.png","2015-02-09","2","6","1317.48","0");
INSERT INTO rmd_photo_ranking VALUES("6","photo_4678","photo_4678.png","2015-02-09","4","7","1545.19","0");
INSERT INTO rmd_photo_ranking VALUES("7","photo_7940","photo_7940.png","2015-02-09","5","6","1712.03","0");
INSERT INTO rmd_photo_ranking VALUES("8","photo_9707","photo_9707.png","2015-02-09","0","6","1227.65","0");


DROP TABLE IF EXISTS rmd_photo_res_mat;

CREATE TABLE `rmd_photo_res_mat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(20) DEFAULT NULL,
  `photo_4004` int(11) NOT NULL,
  `photo_5647` int(11) NOT NULL,
  `photo_839` int(11) NOT NULL,
  `photo_9617` int(11) NOT NULL,
  `photo_756` int(11) NOT NULL,
  `photo_4678` int(11) NOT NULL,
  `photo_7940` int(11) NOT NULL,
  `photo_9707` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_res_mat VALUES("1","photo_4004","0","0","1","0","0","0","0","0");
INSERT INTO rmd_photo_res_mat VALUES("2","photo_5647","1","0","1","0","0","1","0","1");
INSERT INTO rmd_photo_res_mat VALUES("3","photo_839","0","0","0","0","1","0","0","1");
INSERT INTO rmd_photo_res_mat VALUES("4","photo_9617","0","0","0","0","1","1","1","1");
INSERT INTO rmd_photo_res_mat VALUES("5","photo_756","1","0","0","0","0","0","0","1");
INSERT INTO rmd_photo_res_mat VALUES("6","photo_4678","1","0","1","0","1","0","0","1");
INSERT INTO rmd_photo_res_mat VALUES("7","photo_7940","0","1","1","0","1","1","0","1");
INSERT INTO rmd_photo_res_mat VALUES("8","photo_9707","0","0","0","0","0","0","0","0");


DROP TABLE IF EXISTS rmd_photo_users;

CREATE TABLE `rmd_photo_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` char(50) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `userid` char(10) DEFAULT NULL,
  `refid` char(20) DEFAULT NULL,
  `nb_visit` int(11) NOT NULL,
  `name` char(10) DEFAULT NULL,
  `email` tinytext,
  `language` char(3) DEFAULT NULL,
  `age` int(3) NOT NULL,
  `gender` char(6) DEFAULT NULL,
  `drawlvl` char(10) DEFAULT NULL,
  `artint` char(3) NOT NULL,
  `response1` text,
  `response2` text,
  `pair1` text,
  `pair2` text,
  `time_start` int(50) NOT NULL,
  `time_end` int(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_users VALUES("1","::1","0000-00-00 00:00:00","photo_6992","photo","0","FP","dropfantasy@msn.com","en","30","Male","Low","No",",photo_7940,photo_7940,photo_4678,photo_9617,photo_9617,photo_9617,photo_839,photo_4678,photo_4678",",photo_839,photo_9707,photo_839,photo_7940,photo_9707,photo_4678,photo_9707,photo_9707,photo_756","photo_9617,photo_839,photo_7940,photo_839,photo_9617,photo_9617,photo_9617,photo_839,photo_4678,photo_756","photo_756,photo_7940,photo_9707,photo_4678,photo_7940,photo_9707,photo_4678,photo_9707,photo_9707,photo_4678","1423471227","0");
INSERT INTO rmd_photo_users VALUES("2","::1","0000-00-00 00:00:00","photo_9580","photo","0","constantin","dropfantasy@msn.com","en","30","Female","Low","No",",photo_839,photo_7940,photo_7940,photo_9617,photo_5647,photo_7940,photo_5647,photo_5647,photo_756",",photo_756,photo_4678,photo_5647,photo_756,photo_4678,photo_756,photo_9707,photo_4004,photo_9707","photo_839,photo_839,photo_4678,photo_5647,photo_9617,photo_5647,photo_756,photo_5647,photo_4004,photo_756","photo_9617,photo_756,photo_7940,photo_7940,photo_756,photo_4678,photo_7940,photo_9707,photo_5647,photo_9707","1423471536","0");
INSERT INTO rmd_photo_users VALUES("3","::1","0000-00-00 00:00:00","photo_9409","photo","0","FP","dropfantasy@msn.com","en","30","Male","Low","No",",photo_756,photo_5647,photo_4004,photo_4678",",photo_4004,photo_839,photo_839,photo_4004","photo_4004,photo_4004,photo_5647,photo_4004,photo_4004,photo_4004,photo_5647,photo_839,photo_4004,photo_5647","photo_7940,photo_756,photo_839,photo_839,photo_4678,photo_9617,photo_9617,photo_9617,photo_9707,photo_756","1423472068","0");


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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

INSERT INTO rmd_ref_drawings VALUES("7","photo","photo_8140.png","0000-00-00","3","200","8","28","1500","10","on","off");


DROP TABLE IF EXISTS rmd_users;

CREATE TABLE `rmd_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(50) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO rmd_users VALUES("1","drop","sha256:1000:mYpFustbz4ZZg9tfUcopcj97ZirWb87+:ES8Pj","dropfantasy@msn.com");


