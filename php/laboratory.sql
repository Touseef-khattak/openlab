DROP TABLE doctors;

CREATE TABLE `doctors` (
  `doc_ID` int(99) NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `department` varchar(20) NOT NULL,
  `hospital` varchar(70) NOT NULL,
  `phone` varchar(15) NOT NULL,
  PRIMARY KEY (`doc_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO doctors VALUES("1","N/A","N/A","N/A","0");
INSERT INTO doctors VALUES("2","Lab Reference","N/A","N/A","00000000");



DROP TABLE patient_test;

CREATE TABLE `patient_test` (
  `patient_test_ID` int(15) NOT NULL AUTO_INCREMENT,
  `patient_ID` int(10) NOT NULL,
  `referred_by` int(10) NOT NULL,
  `Date` date NOT NULL,
  `Time` varchar(13) NOT NULL,
  `specimen` varchar(20) NOT NULL,
  `amount` int(10) NOT NULL,
  `discount` int(5) NOT NULL,
  `net_bill` int(10) NOT NULL,
  PRIMARY KEY (`patient_test_ID`),
  KEY `Foriegn Key` (`patient_ID`),
  KEY `doctor` (`referred_by`),
  CONSTRAINT `Foriegn Key` FOREIGN KEY (`patient_ID`) REFERENCES `patients` (`patient_ID`),
  CONSTRAINT `doctor` FOREIGN KEY (`referred_by`) REFERENCES `doctors` (`doc_ID`) ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO patient_test VALUES("1","1","2","2020-12-30","11:28:55 AM","blood","4000","10","3600");



DROP TABLE patient_test_record;

CREATE TABLE `patient_test_record` (
  `patient_test_ID` int(15) NOT NULL,
  `test_ID` int(10) NOT NULL,
  `result` varchar(15) NOT NULL,
  `rel_test_ID` int(10) NOT NULL,
  KEY `Link tests` (`test_ID`),
  KEY `foreign key` (`patient_test_ID`),
  CONSTRAINT `Link tests` FOREIGN KEY (`test_ID`) REFERENCES `tests` (`test_ID`),
  CONSTRAINT `foreign key` FOREIGN KEY (`patient_test_ID`) REFERENCES `patient_test` (`patient_test_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO patient_test_record VALUES("1","1","0","0");
INSERT INTO patient_test_record VALUES("1","2","0","0");
INSERT INTO patient_test_record VALUES("1","3","0","0");
INSERT INTO patient_test_record VALUES("1","4","0","1");
INSERT INTO patient_test_record VALUES("1","4","0","2");
INSERT INTO patient_test_record VALUES("1","4","0","3");
INSERT INTO patient_test_record VALUES("1","4","0","4");
INSERT INTO patient_test_record VALUES("1","4","0","5");
INSERT INTO patient_test_record VALUES("1","5","0","0");
INSERT INTO patient_test_record VALUES("1","6","0","0");
INSERT INTO patient_test_record VALUES("1","7","0","0");
INSERT INTO patient_test_record VALUES("1","8","0","0");
INSERT INTO patient_test_record VALUES("1","9","0","0");



DROP TABLE patients;

CREATE TABLE `patients` (
  `patient_ID` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `gender` varchar(6) NOT NULL,
  `age` int(3) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  PRIMARY KEY (`patient_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO patients VALUES("1","Testing","23121-3333333-3","Male","21","41234351451");



DROP TABLE related_tests;

CREATE TABLE `related_tests` (
  `rel_test_ID` int(10) NOT NULL AUTO_INCREMENT,
  `test_ID` int(10) NOT NULL,
  `related_test` varchar(20) NOT NULL,
  `min` varchar(15) NOT NULL,
  `max` varchar(15) NOT NULL,
  PRIMARY KEY (`rel_test_ID`),
  KEY `parent_test` (`test_ID`),
  CONSTRAINT `parent_test` FOREIGN KEY (`test_ID`) REFERENCES `tests` (`test_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO related_tests VALUES("1","4","Haemoglobin","130","180 g/l");
INSERT INTO related_tests VALUES("2","4","Red Cell Count","4.50","6.50 x1012/l");
INSERT INTO related_tests VALUES("3","4","Haematocrit","0.40","0.54 l/l");
INSERT INTO related_tests VALUES("4","4","White Cell Count","3.6","11.0 x109/l");
INSERT INTO related_tests VALUES("5","4","Platelet Count","140","400 x109/l");



DROP TABLE tests;

CREATE TABLE `tests` (
  `test_ID` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `reference_min` varchar(15) NOT NULL,
  `reference_max` varchar(15) NOT NULL,
  `price` int(10) NOT NULL,
  `type` int(1) NOT NULL,
  PRIMARY KEY (`test_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

INSERT INTO tests VALUES("1","A/G ratio","37","51 g/L","400","0");
INSERT INTO tests VALUES("2","ESR","0","29 mm/hr","200","0");
INSERT INTO tests VALUES("3","P SMEAR","N/A","N/A","800","0");
INSERT INTO tests VALUES("4","FBC + Platelets","0","0","800","1");
INSERT INTO tests VALUES("5","Blood Urea","2.5","7.1 mmol/l","200","0");
INSERT INTO tests VALUES("6","Creatinine (serum","3","15 μg/dL ","200","0");
INSERT INTO tests VALUES("7","Hemoglobin","13","17 g/dL","200","0");
INSERT INTO tests VALUES("8","HBA1C","3.6","5.0 g/L","1000","0");
INSERT INTO tests VALUES("9","Blood Group","N/A","N/A","200","0");



DROP TABLE units;

CREATE TABLE `units` (
  `unit_ID` int(10) NOT NULL,
  `lable` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




DROP TABLE user_role;

CREATE TABLE `user_role` (
  `role_ID` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  PRIMARY KEY (`role_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO user_role VALUES("1","admin");
INSERT INTO user_role VALUES("2","counter");
INSERT INTO user_role VALUES("3","Pathologist");



DROP TABLE users;

CREATE TABLE `users` (
  `user_ID` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `role` int(10) NOT NULL,
  `mobile` varchar(11) NOT NULL,
  `password` varchar(40) NOT NULL,
  PRIMARY KEY (`user_ID`),
  KEY `role link` (`role`),
  CONSTRAINT `role link` FOREIGN KEY (`role`) REFERENCES `user_role` (`role_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO users VALUES("1","admin","12345-9876895-1","1","03331234567","admin");
INSERT INTO users VALUES("2","counter","12345-7856789-1","2","98765434526","counter");
INSERT INTO users VALUES("5","pathologist","13134-3124324-3","3","09876543243","pathologist");



