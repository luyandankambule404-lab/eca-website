<?php
// auth.php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";

function require_login(){
  if(empty($_SESSION['user_id'])) redirect("/cpd/index.php");
}

function require_role($roles)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }

    $userRole = strtoupper(trim($_SESSION['role']));

    if (is_array($roles)) {
        $allowed = array_map(function ($r) {
            return strtoupper(trim($r));
        }, $roles);

        if (!in_array($userRole, $allowed, true)) {
            http_response_code(403);
            exit('Access denied.');
        }
    } else {
        if ($userRole !== strtoupper(trim($roles))) {
            http_response_code(403);
            exit('Access denied.');
        }
    }
}