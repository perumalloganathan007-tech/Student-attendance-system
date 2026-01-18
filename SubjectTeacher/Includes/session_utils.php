<?php
/**
 * Session Utility Functions
 * These functions handle retrieving session and term information, with compatibility for different database schemas
 */

/**
 * Get the active session and term information with fallbacks for different schema versions
 * 
 * @param mysqli $conn The database connection
 * @return array|null The session information or null if not found
 */
function getActiveSessionTerm($conn) {
    try {        // First, try using termId field in tblsessionterm (standard schema)
        $stmt = $conn->prepare("SELECT st.Id as sessionTermId, st.sessionName, t.Id as termId, t.termName 
                              FROM tblsessionterm st 
                              INNER JOIN tblterm t ON t.Id = st.termId 
                              WHERE st.isActive = '1'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Log the error but continue to next attempt
        error_log('First schema query failed: ' . $e->getMessage());
    }
    
    try {
        // If that fails, try using termId (older schema)
        $stmt = $conn->prepare("SELECT st.Id as sessionTermId, st.sessionName, t.Id as termId, t.termName 
                              FROM tblsessionterm st 
                              INNER JOIN tblterm t ON t.Id = st.termId 
                              WHERE st.isActive = '1'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    } catch (Exception $e) {
        // Log the error but continue to fallback
        error_log('Second schema query failed: ' . $e->getMessage());
    }
    
    // If both queries fail or return no results, return default values
    return [
        'sessionTermId' => 1,
        'sessionName' => 'Current Session',
        'termId' => 1,
        'termName' => 'Current Term'
    ];
}

/**
 * Check if the required tables for subject teacher functionality exist
 * 
 * @param mysqli $conn The database connection
 * @return array Status of each required table
 */
function checkSubjectTeacherTables($conn) {
    $requiredTables = [
        'tblsubjects',
        'tblsubjectteachers',
        'tblsubjectteacher_student',
        'tblsubjectattendance'
    ];
    
    $result = [];
    
    foreach ($requiredTables as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        $result[$table] = ($check->num_rows > 0);
    }
    
    return $result;
}

/**
 * Create any missing tables required for subject teacher functionality
 * 
 * @param mysqli $conn The database connection
 * @return array Results of table creation operations
 */
function createMissingTables($conn) {
    $tableStatus = checkSubjectTeacherTables($conn);
    $results = [];
    
    // Create tblsubjectteacher_student if it doesn't exist
    if (!$tableStatus['tblsubjectteacher_student']) {
        $createTable = "CREATE TABLE `tblsubjectteacher_student` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `subjectTeacherId` int(11) NOT NULL,
            `studentId` int(11) NOT NULL,
            `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `subjectTeacherId` (`subjectTeacherId`),
            KEY `studentId` (`studentId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $results['tblsubjectteacher_student'] = $conn->query($createTable);
    }
    
    // Create tblsubjectattendance if it doesn't exist
    if (!$tableStatus['tblsubjectattendance']) {
        $createTable = "CREATE TABLE `tblsubjectattendance` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `subjectTeacherId` int(11) NOT NULL,
            `studentId` int(11) NOT NULL,
            `status` tinyint(1) NOT NULL,
            `date` date NOT NULL,
            `sessionTermId` int(11) NOT NULL,
            `dateTimeTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`Id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $results['tblsubjectattendance'] = $conn->query($createTable);
    }
    
    return $results;
}
?>
