-- Add Subject Tables
CREATE TABLE IF NOT EXISTS `tblsubjects` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectName` varchar(255) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Subject Teachers Table
CREATE TABLE IF NOT EXISTS `tblsubjectteachers` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(50) DEFAULT NULL,
  `subjectId` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`subjectId`) REFERENCES `tblsubjects`(`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Subject Teacher-Student Relationship Table
CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectTeacherId` int(11) NOT NULL,
  `studentId` int(11) NOT NULL,
  `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`subjectTeacherId`) REFERENCES `tblsubjectteachers`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`studentId`) REFERENCES `tblstudents`(`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add Subject Attendance Table
CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `studentId` int(11) NOT NULL,
  `subjectId` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `dateTimeTaken` date NOT NULL,
  `sessionTermId` int(11) NOT NULL,
  PRIMARY KEY (`Id`),
  FOREIGN KEY (`studentId`) REFERENCES `tblstudents`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`subjectId`) REFERENCES `tblsubjects`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`sessionTermId`) REFERENCES `tblsessionterm`(`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
