-- Add subject code field to tblsubjects table
ALTER TABLE `tblsubjects` 
ADD COLUMN `subjectCode` varchar(50) NOT NULL AFTER `subjectName`,
ADD UNIQUE KEY `subjectCode` (`subjectCode`);
