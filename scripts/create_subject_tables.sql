-- Create tblsubjects table
CREATE TABLE IF NOT EXISTS `tblsubjects` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `subjectName` varchar(255) NOT NULL,
  `classId` varchar(10) NOT NULL,
  `dateCreated` varchar(50) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Create tblsubjectteachers table
CREATE TABLE IF NOT EXISTS `tblsubjectteachers` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(50) NOT NULL,
  `subjectId` varchar(10) NOT NULL,
  `dateCreated` varchar(50) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `emailAddress` (`emailAddress`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Insert test subject and subject teacher
INSERT INTO `tblsubjects` (`subjectName`, `classId`, `dateCreated`) VALUES
('Mathematics', '1', NOW());

INSERT INTO `tblsubjectteachers` (`firstName`, `lastName`, `emailAddress`, `password`, `phoneNo`, `subjectId`, `dateCreated`) 
VALUES ('Math', 'Teacher', 'math.teacher@school.com', MD5('Math@123'), '1234567890', '1', NOW());
