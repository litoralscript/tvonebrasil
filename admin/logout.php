<?php
/**
 * TV One Portal - Logout
 */

require_once __DIR__ . '/../config/functions.php';

logActivity('LOGOUT', 'Admin fez logout');
adminLogout();

header('Location: login.php?logout=1');
exit;
?>
