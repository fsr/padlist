<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header('Location: index.php');