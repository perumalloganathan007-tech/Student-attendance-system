-- Add subject teachers and subjects tables

-- Create subjects table if it doesn't exist
CREATE TABLE IF NOT EXISTS `tblsubjects` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `subjectName` varchar(255) NOT NULL,
  `classId` varchar(10) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Create subject teachers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `tblsubjectteachers` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(50) NOT NULL,
  `subjectId` varchar(10) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `emailAddress` (`emailAddress`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Add subject attendance table if it doesn't exist
CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `admissionNo` varchar(255) NOT NULL,
  `subjectId` varchar(10) NOT NULL,
  `classId` varchar(10) NOT NULL,
  `status` varchar(10) NOT NULL,
  `dateTimeTaken` date NOT NULL,
  `sessionTermId` varchar(10) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Insert sample subject
INSERT INTO `tblsubjects` (`subjectName`, `classId`) VALUES
('Mathematics', '1');

-- Insert sample subject teacher with properly hashed password (Math@123)
INSERT INTO `tblsubjectteachers` (`firstName`, `lastName`, `emailAddress`, `password`, `phoneNo`, `subjectId`) VALUES
('Math', 'Teacher', 'math.teacher@school.com', '$2y$10$YourHashedPasswordHere', '1234567890', '1');
