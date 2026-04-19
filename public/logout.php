<?php
// public/logout.php

session_start();
require_once '../app/Controllers/AuthController.php';
require_once '../config/database.php';
require_once '../app/Helpers/FlashHelper.php';

(new AuthController())->logout();
