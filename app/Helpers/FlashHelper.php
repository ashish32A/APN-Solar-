<?php
// app/Helpers/FlashHelper.php - Session flash messages

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type    = $flash['type'];  // success | error | warning | info
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type}'>{$message}</div>";
    }
}
