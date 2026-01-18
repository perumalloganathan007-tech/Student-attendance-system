-- Add subjectCode and classId columns if they don't exist
ALTER TABLE `tblsubjects` 
ADD COLUMN IF NOT EXISTS `subjectCode` varchar(50) NOT NULL AFTER `subjectName`,
ADD COLUMN IF NOT EXISTS `classId` varchar(10) NOT NULL AFTER `subjectCode`,
ADD UNIQUE KEY IF NOT EXISTS `uk_subject_code` (`subjectCode`);
