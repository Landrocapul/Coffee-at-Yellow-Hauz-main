<?php
require_once 'db.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
redirect('index.php');
?>
