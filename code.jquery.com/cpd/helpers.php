<?php
// helpers.php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($path){
  header("Location: $path");
  exit();
}

function is_post(){ return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }