<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';

echo "<h1>Check tblterm Table Structure</h1>";

// Check if tblterm table exists
$result = $conn->query("SHOW TABLES LIKE 'tblterm'");
if ($result->num_rows > 0) {
    echo "<p>tblterm table exists</p>";
    
    // Check columns in tblterm
    $columns = $conn->query("DESCRIBE tblterm");
    
    echo "<h2>tblterm columns:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($column = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show sample data
    $data = $conn->query("SELECT * FROM tblterm LIMIT 5");
    
    echo "<h2>tblterm sample data:</h2>";
    if ($data->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        
        // Print header row
        $row = $data->fetch_assoc();
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        // Reset pointer and print data rows
        $data->data_seek(0);
        while ($row = $data->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data in tblterm</p>";
    }
} else {
    echo "<p>tblterm table does not exist!</p>";
}

$conn->close();
?>
