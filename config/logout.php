<?php

session_start();
session_destroy();
header("Location: ../apps/views/global/Connexion.php");
exit;

?>