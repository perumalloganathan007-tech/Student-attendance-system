-- Add subjectCode column if it doesn't exist
ALTER TABLE `tblsubjects` 
ADD COLUMN IF NOT EXISTS `subjectCode` varchar(50) NOT NULL AFTER `subjectName`,
ADD UNIQUE KEY IF NOT EXISTS `uk_subject_code` (`subjectCode`);
