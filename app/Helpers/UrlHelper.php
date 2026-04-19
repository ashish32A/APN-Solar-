<?php
// app/Helpers/UrlHelper.php

$appConfig = require __DIR__ . '/../../config/app.php';
define('BASE_URL', $appConfig['url']);

function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header("Location: " . url($path));
    exit;
}

function asset(string $path): string {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}
