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
INSERT INTO jcm_config VALUES("8","jc_day","friday");
INSERT INTO jcm_config VALUES("9","room","H432");
INSERT INTO jcm_config VALUES("10","jc_time_from","09:00");
INSERT INTO jcm_config VALUES("11","jc_time_to","11:00");
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
  `userid` char(30) DEFAULT NULL,
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

INSERT INTO jcm_users VALUES("1","2015-02-17 08:13:49","","drop","","drop","drop","sha256:1000:HRBnvFkPa7dWjcyvnksW6G8UzrHlpPBv:SF2fF","","dropfantasy@msn.com","1","1","0","admin","d5cfead94f5350c12c322b5b664544c1","1");


DROP TABLE IF EXISTS pjc_config;

CREATE TABLE `pjc_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variable` char(20) DEFAULT NULL,
  `value` char(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;

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
INSERT INTO pjc_config VALUES("12","notification","monday");
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
INSERT INTO pjc_config VALUES("25","mail_username","experience.dessin@gmail.com");
INSERT INTO pjc_config VALUES("26","mail_password","[Drop69]");
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO pjc_post VALUES("1","2015-02-15 19:32:57","&lt;p&gt;Hello, &lt;strong&gt;new&lt;/strong&gt; features&lt;/p&gt;","");


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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;

INSERT INTO pjc_presentations VALUES("2","2015-02-14 10:40:29","201502144964","paper","2015-02-19","17:00,18:00","The artists advantage: Better integration of object information across eye movements","Perdreau   F","Over their careers, figurative artists spend thousands of hours analyzing objects and scene layout. We examined what impact this extensive training has on the ability to encode complex scenes, comparing participants with a wide range of training and drawing skills on a possible versus impossible objects task. We used a gaze-contingent display to control the amount of information the participants could sample on each fixation either from central or peripheral visual field. Test objects were displayed and participants reported, as quickly as possible, whether the object was structurally possible or not. Our results show that when viewing the image through a small central window, performance improved with the years of training, and to a lesser extent with the level of skill. This suggests that the extensive training itself confers an advantage for integrating object structure into more robust object descriptions.","","Admin","0");
INSERT INTO pjc_presentations VALUES("5","2015-02-15 02:15:00","20150215259","paper","2015-02-19","17:00,18:00","The Artists visual span: better performance through smaller windows","Perdreau, F., Cavanagh, P."," Our perception starts with the image that falls on our retina and on this retinal image, distant objects are small and shadowed surfaces are dark. But this is not what we see. Visual constancies correct for distance so that, for example, a person approaching us does not appear to become a larger person. Interestingly, an artist, when rendering a scene realistically, must undo all these corrections, making distant objects again small. To determine whether years of art training and practice have conferred any specialized visual expertise, we compared the perceptual abilities of artists to those of non-artists in three tasks. We first asked them to adjust either the size or the brightness of a target to match it to a standard that was presented on a perspective grid or within a cast shadow. We instructed them to ignore the context, judging size, for example, by imagining the separation between their fingers if they were to pick up the test object from the display screen. In the third task, we tested the speed with which artists access visual representations. Subjects searched for an L-shape in contact with a circle; the target was an L-shape, but because of visual completion, it appeared to be a square occluded behind a circle, camouflaging the L-shape that is explicit on the retinal image. Surprisingly, artists were as affected by context as non-artists in all three tests. Moreover, artists took, on average, significantly more time to make their judgments, implying that they were doing their best to demonstrate the special skills that we, and they, believed they had acquired. Our data therefore support the proposal from Gombrich that artists do not have special perceptual expertise to undo the effects of constancies. Instead, once the context is present in their drawing, they need only compare the drawing to the scene to match the effect of constancies in both.","","","0");
INSERT INTO pjc_presentations VALUES("16","2015-02-15 03:05:57","20150215571","paper","2015-02-26","17:00,18:00","etsheherdh","shrsheeh","Abstract (2000 characters maximum)","","","0");
INSERT INTO pjc_presentations VALUES("17","2015-02-15 03:07:27","201502159274","paper","2015-03-05","17:00,18:00","zgzqgqze","zqgqgezg","Abstract (2000 characters maximum)","","","0");
INSERT INTO pjc_presentations VALUES("20","2015-02-15 08:18:20","201502158053","wishlist","0000-00-00","17:00,18:00","gfqefqzgzegqzeg","gqzgzegzee","Abstract (2000 characters maximum)","","","0");
INSERT INTO pjc_presentations VALUES("21","2015-02-15 08:29:27","201502156344","business","2015-02-26","17:00,18:00","efzqefqzefzqef","qerfqzefqzefqzef","Abstract (2000 characters maximum)","","Admin","0");


DROP TABLE IF EXISTS pjc_users;

CREATE TABLE `pjc_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT '2015-02-14 09:27:38',
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

INSERT INTO pjc_users VALUES("1","2015-02-14 09:27:51","Admin","","","drop","sha256:1000:BHJMN2qMYMQcBiW6lUH0iJ4vcASxGVUN:OYPXR","","dropfantasy@msn.com","1","1","0","admin","c9892a989183de32e976c6f04e700201","1");
INSERT INTO pjc_users VALUES("2","2015-02-15 15:53:09","Florian","Perdreau","Florian Perdreau","Fperdreau","sha256:1000:4TRYDYtHR5mjTlp1hCXzGx7Bkesh5xEY:eMUkC","researcher","florian.perdreau@gmail.com","1","1","0","member","f3f27a324736617f20abbf2ffd806f6d","1");
INSERT INTO pjc_users VALUES("8","2015-02-16 08:17:35","Pierre","Richard","Pierre Richard","prichard","sha256:1000:+XjzgAzwYrLF3n2N45QncvgOkAl+FS0d:MwEWO","researcher","florian.perdreau@parisdescarte","1","1","0","member","c8fbbc86abe8bd6a5eb6a3b4d0411301","0");


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
INSERT INTO rmd_config VALUES("7","redirecturl","");
INSERT INTO rmd_config VALUES("8","instruction","<p>\n                    During a succession of trials, pairs of hand-drawings are going to be presented, as well as\n                    the original model (see opposite).<br><br>\n                    Your task is to choose (by clicking on) which of the two drawings is closer to the original.\n                    <b>Importantly</b>, do not make your decision on the basis of aesthetism or style!.<br>\n                    </p>");
INSERT INTO rmd_config VALUES("9","consent","<p><strong>Experiment&apos;s aim :</strong> All data of this experiment are\n                    collected for scientific reasons and will contribute to a better understanding\n                    of the brain and of visual perception. These data might be published in\n                    scientific journals.</p>\n                    <p><strong>Experimental task: </strong>You are going to see pictures on your\n                    monitor and you will have to give responses by clicking on a mouse.</p>\n                    <p><strong>Remuneration: </strong>This experiment is not remunerated.</p>\n                    <p><strong>Confidentiality: </strong>Your participation to this experiment is\n                    confidential and your identity will not be recorder with your data.\n                    We attribute a code to your responses, and the list relating your name to\n                    this code will be destroyed once the data will be recorded and analyzed.\n                    You have the right to access and to modify your data accordingly to the\n                    Law on Information Technology andCivil Liberties. Any publication of the\n                    results will not include identifying individual results.</p>\n                    <p><strong>Participation: </strong>Your participation to this experiment is\n                    entirely voluntary and you can, at any time, stop the experiment.</p>");


DROP TABLE IF EXISTS rmd_photo_comp_mat;

CREATE TABLE `rmd_photo_comp_mat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(20) DEFAULT NULL,
  `photo_8325` int(11) NOT NULL,
  `photo_596` int(11) NOT NULL,
  `photo_8489` int(11) NOT NULL,
  `photo_8029` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_comp_mat VALUES("1","photo_8325","0","1","2","0");
INSERT INTO rmd_photo_comp_mat VALUES("2","photo_596","1","0","2","1");
INSERT INTO rmd_photo_comp_mat VALUES("3","photo_8489","2","1","0","1");
INSERT INTO rmd_photo_comp_mat VALUES("4","photo_8029","0","1","1","0");


DROP TABLE IF EXISTS rmd_photo_content;

CREATE TABLE `rmd_photo_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` char(20) DEFAULT NULL,
  `lang` char(20) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_content VALUES("1","instruction","en","<p>\n                    During a succession of trials, pairs of hand-drawings are going to be presented, as well as\n                    the original model (see opposite).<br><br>\n                    Your task is to choose (by clicking on) which of the two drawings is closer to the original.\n                    <b>Importantly</b>, do not make your decision on the basis of aesthetism or style!.<br>\n                    </p>");
INSERT INTO rmd_photo_content VALUES("2","consent","en","<p><strong>Experiment&apos;s aim :</strong> All data of this experiment are\n                    collected for scientific reasons and will contribute to a better understanding\n                    of the brain and of visual perception. These data might be published in\n                    scientific journals.</p>\n                    <p><strong>Experimental task: </strong>You are going to see pictures on your\n                    monitor and you will have to give responses by clicking on a mouse.</p>\n                    <p><strong>Remuneration: </strong>This experiment is not remunerated.</p>\n                    <p><strong>Confidentiality: </strong>Your participation to this experiment is\n                    confidential and your identity will not be recorder with your data.\n                    We attribute a code to your responses, and the list relating your name to\n                    this code will be destroyed once the data will be recorded and analyzed.\n                    You have the right to access and to modify your data accordingly to the\n                    Law on Information Technology andCivil Liberties. Any publication of the\n                    results will not include identifying individual results.</p>\n                    <p><strong>Participation: </strong>Your participation to this experiment is\n                    entirely voluntary and you can, at any time, stop the experiment.</p>");


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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_ranking VALUES("1","photo_8325","photo_8325.png","2015-02-10","1","2","1450.33","0");
INSERT INTO rmd_photo_ranking VALUES("2","photo_596","photo_596.png","2015-02-10","3","3","1752.17","0");
INSERT INTO rmd_photo_ranking VALUES("3","photo_8489","photo_8489.png","2015-02-10","0","3","1145.55","0");
INSERT INTO rmd_photo_ranking VALUES("4","photo_8029","photo_8029.png","2015-02-10","1","2","1451.95","0");


DROP TABLE IF EXISTS rmd_photo_res_mat;

CREATE TABLE `rmd_photo_res_mat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` char(20) DEFAULT NULL,
  `photo_8325` int(11) NOT NULL,
  `photo_596` int(11) NOT NULL,
  `photo_8489` int(11) NOT NULL,
  `photo_8029` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_res_mat VALUES("1","photo_8325","0","0","1","0");
INSERT INTO rmd_photo_res_mat VALUES("2","photo_596","1","0","2","1");
INSERT INTO rmd_photo_res_mat VALUES("3","photo_8489","1","0","0","0");
INSERT INTO rmd_photo_res_mat VALUES("4","photo_8029","0","0","1","0");


DROP TABLE IF EXISTS rmd_photo_users;

CREATE TABLE `rmd_photo_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` char(50) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `userid` char(20) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO rmd_photo_users VALUES("1","::1","2015-02-10 14:11:41","photo_9383","photo","0","FPvision","dropfantasy@msn.com","en","30","Female","Low","No",",photo_596,photo_596,photo_8029,photo_8325,photo_596",",photo_8029,photo_8325,photo_8489,photo_8489,photo_8489","photo_8325,photo_596,photo_8325,photo_8489,photo_8325,photo_596","photo_8029,photo_8029,photo_596,photo_8029,photo_8489,photo_8489","1423573905","0");


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO rmd_ref_drawings VALUES("1","photo","photo_824.png","2015-02-10","1","1","4","6","1500","6","on","off");


DROP TABLE IF EXISTS rmd_users;

CREATE TABLE `rmd_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(50) DEFAULT NULL,
  `password` char(50) DEFAULT NULL,
  `email` char(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

INSERT INTO rmd_users VALUES("1","drop","sha256:1000:x4n51lLhdRaR/gDNdXhk5gNRmPwSlI0E:1sgX/","dropfantasy@msn.com");


