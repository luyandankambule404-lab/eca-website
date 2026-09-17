<?php

function eca_hub_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_hub_first_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    $parts = preg_split('/\s+/', $name);
    return $parts[0] ?? $name;
}

function eca_hub_hello(string $name): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        $part = 'Good morning';
    } elseif ($hour < 17) {
        $part = 'Good afternoon';
    } else {
        $part = 'Good evening';
    }
    $first = eca_hub_first_name($name);
    return $first !== '' ? $part . ' ' . $first . '!' : $part . '!';
}

function eca_hub_initial(string $name): string
{
    $name = trim($name);
    return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'E';
}

function eca_hub_assets(): void
{
    ?>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/dashboard-hub.css?v=4">
    <?php
}
