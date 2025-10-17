<?php
//Start session
session_start();
//destroy it
session_destroy();
// Redirect to home page
header("Location:homePage.php"); 
exit();
?>

