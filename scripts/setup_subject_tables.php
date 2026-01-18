<?php
// Include database connection
require_once '../Includes/dbcon.php';

// Create tblsubjects table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `tblsubjects` (
    `Id` int(11) NOT NULL AUTO_INCREMENT,
    `subjectName` varchar(255) NOT NULL,
    `subjectCode` varchar(50) NOT NULL,
    `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create tblsubjectteachers table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `tblsubjectteachers` (
    `Id` int(11) NOT NULL AUTO_INCREMENT,
    `firstName` varchar(255) NOT NULL,
    `lastName` varchar(255) NOT NULL,
    `emailAddress` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phoneNo` varchar(50) DEFAULT NULL,
    `subjectId` int(11) NOT NULL,
    `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`),
    KEY `subjectId` (`subjectId`),
    CONSTRAINT `tblsubjectteachers_ibfk_1` FOREIGN KEY (`subjectId`) 
    REFERENCES `tblsubjects` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create tblsubjectteacher_student table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
    `Id` int(11) NOT NULL AUTO_INCREMENT,
    `subjectTeacherId` int(11) NOT NULL,
    `studentId` int(11) NOT NULL,
    `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`),
    KEY `subjectTeacherId` (`subjectTeacherId`),
    KEY `studentId` (`studentId`),
    CONSTRAINT `tblsubjectteacher_student_ibfk_1` FOREIGN KEY (`subjectTeacherId`) 
    REFERENCES `tblsubjectteachers` (`Id`) ON DELETE CASCADE,
    CONSTRAINT `tblsubjectteacher_student_ibfk_2` FOREIGN KEY (`studentId`) 
    REFERENCES `tblstudents` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create tblsubjectattendance table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
    `Id` int(11) NOT NULL AUTO_INCREMENT,
    `studentId` int(11) NOT NULL,
    `subjectTeacherId` int(11) NOT NULL,
    `status` tinyint(1) NOT NULL DEFAULT '0',
    `date` date NOT NULL,
    PRIMARY KEY (`Id`),
    KEY `studentId` (`studentId`),
    KEY `subjectTeacherId` (`subjectTeacherId`),
    CONSTRAINT `tblsubjectattendance_ibfk_1` FOREIGN KEY (`studentId`) 
    REFERENCES `tblstudents` (`Id`) ON DELETE CASCADE,
    CONSTRAINT `tblsubjectattendance_ibfk_2` FOREIGN KEY (`subjectTeacherId`) 
    REFERENCES `tblsubjectteachers` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

echo "Database tables created successfully.";
?>
