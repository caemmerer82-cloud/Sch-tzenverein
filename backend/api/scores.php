<?php
function handleScores($pdo, $method, $segments) {
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                getScore($pdo, $id);
            } else {
                getScores($pdo);
            }
            break;
        case 'POST':
            createScore($pdo);
            break;
        case 'PUT':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            updateScore($pdo, $id);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            deleteScore($pdo, $id);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getScores($pdo) {
    $eventId = $_GET['event_id'] ?? null;
    $shooterId = $_GET['shooter_id'] ?? null;
    $eventDateId = $_GET['event_date_id'] ?? null;

    $sql = "
        SELECT sc.*, 
            s.first_name, s.last_name, s.club_name,
            e.name as event_name,
            ed.event_date
        FROM scores sc
        JOIN shooters s ON sc.shooter_id = s.id
        JOIN events e ON sc.event_id = e.id
        LEFT JOIN event_dates ed ON sc.event_date_id = ed.id
        WHERE 1=1
    ";
    $params = [];

    if ($eventId) {
        $sql .= " AND sc.event_id = ?";
        $params[] = $eventId;
    }
    if ($shooterId) {
        $sql .= " AND sc.shooter_id = ?";
        $params[] = $shooterId;
    }
    if ($eventDateId) {
        $sql .= " AND sc.event_date_id = ?";
        $params[] = $eventDateId;
    }

    $sql .= " ORDER BY sc.points DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

function getScore($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT sc.*, s.first_name, s.last_name, e.name as event_name
        FROM scores sc
        JOIN shooters s ON sc.shooter_id = s.id
        JOIN events e ON sc.event_id = e.id
        WHERE sc.id = ?
    ");
    $stmt->execute([$id]);
    $score = $stmt->fetch();
    if (!$score) jsonResponse(['error' => 'Ergebnis nicht gefunden'], 404);
    jsonResponse($score);
}

function createScore($pdo) {
    $data = getJsonInput();
    if (empty($data['event_id']) || empty($data['shooter_id']) || !isset($data['points'])) {
        jsonResponse(['error' => 'event_id, shooter_id und points sind erforderlich'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO scores (event_id, event_date_id, shooter_id, points, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['event_id'],
        $data['event_date_id'] ?? null,
        $data['shooter_id'],
        $data['points'],
        $data['notes'] ?? null,
    ]);

    jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()], 201);
}

function updateScore($pdo, $id) {
    $data = getJsonInput();
    if (!isset($data['points'])) {
        jsonResponse(['error' => 'points ist erforderlich'], 400);
    }

    $stmt = $pdo->prepare("UPDATE scores SET points = ?, notes = ?, event_date_id = ? WHERE id = ?");
    $stmt->execute([
        $data['points'],
        $data['notes'] ?? null,
        $data['event_date_id'] ?? null,
        $id,
    ]);

    jsonResponse(['success' => true]);
}

function deleteScore($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM scores WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
