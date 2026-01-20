<?php
session_start();
// Hapus semua session
session_unset();
session_destroy();

// Balik ke login
header("location:login.php?pesan=logout");
exit();
?>