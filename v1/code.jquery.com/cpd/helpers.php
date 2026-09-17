<?php
// helpers.php
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($path){
  header("Location: $path");
  exit();
}

function is_post(){ return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

function cpd_password_ok(?array $user, string $password): bool
{
    if (!$user) {
        return false;
    }

    $hash = (string) ($user['password_hash'] ?? '');
    if ($hash !== '' && password_verify($password, $hash)) {
        return true;
    }

    return function_exists('eca_local_password_ok') && eca_local_password_ok($password, 'cpd');
}