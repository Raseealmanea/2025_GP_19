<?php
//Start session
session_start();
//destroy it
session_destroy();
// Redirect to login page
header("Location:homePage.php"); 
exit();
?>
