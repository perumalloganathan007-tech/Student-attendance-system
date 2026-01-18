-- Update all subject teacher passwords with a properly generated hash
-- The hash is for 'Password@123'
UPDATE `tblsubjectteachers` 
SET `password` = '$2y$10$vLnr8C5ApdOSm13FFyBiK.etJJ39Wd1qOqjXrRy9I3xEiCF./zplK';

-- Verify counts
SELECT 'Number of subject teachers:', COUNT(*) FROM tblsubjectteachers;
