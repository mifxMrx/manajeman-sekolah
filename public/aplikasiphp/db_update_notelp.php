<?php
include 'config.php';
$sql = "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20) DEFAULT NULL AFTER role";
if (mysqli_query($conn, $sql)) {
    echo "Column no_telp added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
