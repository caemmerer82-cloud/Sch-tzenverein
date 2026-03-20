<?php
require_once __DIR__ . '/../config.php';

// Simple router
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api';

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

$path = trim($path, '/');
$segments = $path ? explode('/', $path) : [];
$method = $_SERVER['REQUEST_METHOD'];

// Route to appropriate handler
$resource = $segments[0] ?? '';

switch ($resource) {
    case 'events':
        require_once __DIR__ . '/events.php';
        handleEvents($pdo, $method, $segments);
        break;
    case 'shooters':
        require_once __DIR__ . '/shooters.php';
        handleShooters($pdo, $method, $segments);
        break;
    case 'scores':
        require_once __DIR__ . '/scores.php';
        handleScores($pdo, $method, $segments);
        break;
    case 'timeslots':
        require_once __DIR__ . '/timeslots.php';
        handleTimeslots($pdo, $method, $segments);
        break;
    case 'reservations':
        require_once __DIR__ . '/reservations.php';
        handleReservations($pdo, $method, $segments);
        break;
    case 'age-groups':
        require_once __DIR__ . '/age_groups.php';
        handleAgeGroups($pdo, $method, $segments);
        break;
    case 'reports':
        require_once __DIR__ . '/reports.php';
        handleReports($pdo, $method, $segments);
        break;
    default:
        jsonResponse(['error' => 'Unbekannte Route', 'path' => $path], 404);
}
