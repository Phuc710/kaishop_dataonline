<?php
require_once __DIR__ . '/HeaderComponent.php';

$header = new HeaderComponent($pdo);
$header->render();
