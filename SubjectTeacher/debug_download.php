<?php
// Debug script to help identify session/parameter issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";
echo "<h2>SESSION Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>GET Variables:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Database Test:</h2>";
try {
    include '../Includes/dbcon.php';
    
    if (!isset($_SESSION['userId'])) {
        echo "Session userId not set!";
    } else {
        $query = "SELECT * FROM tblsubjectteachers WHERE Id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo "Subject Teacher found:<br>";
            print_r($row);
            
            echo "<br><br>Subject Info:<br>";
            $subjectQuery = "SELECT * FROM tblsubjects WHERE Id = ?";
            $subjectStmt = $conn->prepare($subjectQuery);
            $subjectStmt->bind_param("i", $row['subjectId']);
            $subjectStmt->execute();
            $subjectResult = $subjectStmt->get_result();
            
            if ($subjectRow = $subjectResult->fetch_assoc()) {
                print_r($subjectRow);
            } else {
                echo "No subject found with ID: " . $row['subjectId'];
            }
        } else {
            echo "No subject teacher found with ID: " . $_SESSION['userId'];
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
