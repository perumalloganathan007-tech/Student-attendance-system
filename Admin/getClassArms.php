<?php
include '../Includes/dbcon.php';

$cid = intval($_GET['cid']);

// Get all class sections for the selected class that aren't assigned
$query = mysqli_query($conn, "SELECT DISTINCT classArmName FROM tblclassarms WHERE classId = $cid ORDER BY classArmName ASC");
$count = mysqli_num_rows($query);

echo '<select required name="classArmName" class="form-control mb-3">';
echo '<option value="">--Select Class Section--</option>';
while ($row = mysqli_fetch_array($query)) {
    echo '<option value="'.$row['classArmName'].'">'.$row['classArmName'].'</option>';
}
echo '</select>';
?>