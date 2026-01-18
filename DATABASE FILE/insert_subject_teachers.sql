-- First add some sample subjects if they don't exist
INSERT INTO `tblsubjects` (`subjectName`, `subjectCode`, `classId`) VALUES
('Mathematics', 'MATH101', '1'),
('Physics', 'PHY101', '1'),
('Chemistry', 'CHEM101', '2'),
('Biology', 'BIO101', '2'),
('English', 'ENG101', '1')
ON DUPLICATE KEY UPDATE subjectName=subjectName;

-- Insert subject teachers with properly hashed passwords
-- Default password for all teachers is 'Password@123'
-- Password hash is generated from 'Password@123' using bcrypt
INSERT INTO `tblsubjectteachers` (
    `firstName`, 
    `lastName`, 
    `emailAddress`, 
    `password`, 
    `phoneNo`, 
    `subjectId`
) VALUES
('John', 'Smith', 'john.smith@school.com', '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS', '1234567890', 
 (SELECT Id FROM tblsubjects WHERE subjectCode = 'MATH101')),
 
('Sarah', 'Johnson', 'sarah.johnson@school.com', '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS', '2345678901',
 (SELECT Id FROM tblsubjects WHERE subjectCode = 'PHY101')),
 
('Michael', 'Williams', 'michael.williams@school.com', '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS', '3456789012',
 (SELECT Id FROM tblsubjects WHERE subjectCode = 'CHEM101')),
 
('Emily', 'Brown', 'emily.brown@school.com', '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS', '4567890123',
 (SELECT Id FROM tblsubjects WHERE subjectCode = 'BIO101')),
 
('David', 'Jones', 'david.jones@school.com', '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS', '5678901234',
 (SELECT Id FROM tblsubjects WHERE subjectCode = 'ENG101'))
ON DUPLICATE KEY UPDATE emailAddress=emailAddress;
