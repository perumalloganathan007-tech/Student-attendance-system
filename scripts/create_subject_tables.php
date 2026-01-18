<?php
// Include database connection
include '../Includes/dbcon.php';

// SQL statements to create tables
$queries = array(
    // Add Subject Table
    "CREATE TABLE IF NOT EXISTS `tblsubjects` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectName` varchar(255) NOT NULL,
        `subjectCode` varchar(50) NOT NULL,
        `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Add Subject Teachers Table
    "CREATE TABLE IF NOT EXISTS `tblsubjectteachers` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Add Subject Teacher-Student Relationship Table
    "CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`),
        FOREIGN KEY (`subjectTeacherId`) REFERENCES `tblsubjectteachers`(`Id`) ON DELETE CASCADE,
        FOREIGN KEY (`studentId`) REFERENCES `tblstudents`(`Id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Add Subject Attendance Table
    "CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `studentId` int(11) NOT NULL,
        `subjectTeacherId` int(11) NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0',
        `date` date NOT NULL,
        PRIMARY KEY (`Id`),
        FOREIGN KEY (`studentId`) REFERENCES `tblstudents`(`Id`) ON DELETE CASCADE,
        FOREIGN KEY (`subjectTeacherId`) REFERENCES `tblsubjectteachers`(`Id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
);

// Execute each query
foreach ($queries as $query) {
    if (!$conn->query($query)) {
        die("Error creating table: " . $conn->error);
    }
}

echo "Tables created successfully";
?>
