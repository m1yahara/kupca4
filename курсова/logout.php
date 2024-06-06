<?php
include 'session.php';

destroy_session();
header("Location: index.php");
exit();
?>
