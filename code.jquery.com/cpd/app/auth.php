<?php
// auth.php

require_once __DIR__ . "../helpers.php";

function require_login(){
  if(empty($_SESSION['user_id'])) redirect("/cpd/app/index.php");
}

function require_role($role){
  require_login();
  if(($_SESSION['role'] ?? '') !== $role) redirect("/cpd/app/index.php");
}