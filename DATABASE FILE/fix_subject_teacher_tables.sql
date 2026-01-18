-- Direct SQL fix for Subject Teacher module
-- Run this in phpMyAdmin or MySQL command line

USE attendancesystem;

-- Create tblsubjectteacher_student table
CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectTeacherId` int(11) NOT NULL,
  `studentId` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`),
  KEY `subjectTeacherId` (`subjectTeacherId`),
  KEY `studentId` (`studentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create tblsubjectattendance table
CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `studentId` int(11) NOT NULL,
  `subjectTeacherId` int(11) NOT NULL,
  `subjectId` int(11) NOT NULL,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `sessionTermId` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1=Present, 0=Absent',
  `date` date NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_attendance` (`studentId`, `subjectTeacherId`, `date`),
  KEY `studentId` (`studentId`),
  KEY `subjectTeacherId` (`subjectTeacherId`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add subjectCode column if it doesn't exist
ALTER TABLE tblsubjects ADD COLUMN IF NOT EXISTS subjectCode VARCHAR(10) AFTER subjectName;

-- Update subject codes
UPDATE tblsubjects SET subjectCode = 'MATH' WHERE subjectName LIKE '%math%' AND subjectCode IS NULL;
UPDATE tblsubjects SET subjectCode = 'ENG' WHERE subjectName LIKE '%english%' AND subjectCode IS NULL;
UPDATE tblsubjects SET subjectCode = 'SCI' WHERE subjectName LIKE '%science%' AND subjectCode IS NULL;
UPDATE tblsubjects SET subjectCode = 'HIST' WHERE subjectName LIKE '%history%' AND subjectCode IS NULL;
UPDATE tblsubjects SET subjectCode = CONCAT('SUB', Id) WHERE subjectCode IS NULL;

-- Create sample students if table is empty
INSERT IGNORE INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId, dateCreated) VALUES
('Alice', 'Johnson', 'STU001', 1, 1, NOW()),
('Bob', 'Smith', 'STU002', 1, 1, NOW()),
('Carol', 'Davis', 'STU003', 1, 1, NOW()),
('David', 'Wilson', 'STU004', 1, 1, NOW()),
('Emma', 'Brown', 'STU005', 1, 1, NOW());

-- Link subject teacher with students
INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId)
SELECT st.Id, s.Id 
FROM tblsubjectteachers st
CROSS JOIN tblstudents s
WHERE st.emailAddress = 'john.smith@school.com'
LIMIT 5;

-- Verify tables exist
SHOW TABLES LIKE 'tblsubjectteacher_student';
SHOW TABLES LIKE 'tblsubjectattendance';

-- Show table contents
SELECT 'Subject Teachers' as TableName, COUNT(*) as RecordCount FROM tblsubjectteachers
UNION ALL
SELECT 'Subject Teacher-Student Links', COUNT(*) FROM tblsubjectteacher_student
UNION ALL
SELECT 'Students', COUNT(*) FROM tblstudents
UNION ALL
SELECT 'Subjects', COUNT(*) FROM tblsubjects;
