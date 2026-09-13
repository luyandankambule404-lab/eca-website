<?php
require_once "config.php";
session_destroy();
header("Location: /cpd/index.php");
exit();