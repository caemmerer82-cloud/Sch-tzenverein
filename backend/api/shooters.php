<?php
function handleShooters($pdo, $method, $segments) {
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                getShooter($pdo, $id);
            } else {
                getShooters($pdo);
            }
            break;
        case 'POST':
            createShooter($pdo);
            break;
        case 'PUT':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            updateShooter($pdo, $id);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            deleteShooter($pdo, $id);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getShooters($pdo) {
    $search = $_GET['search'] ?? null;
    $clubName = $_GET['club'] ?? null;

    $sql = "SELECT * FROM shooters WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($clubName) {
        $sql .= " AND club_name = ?";
        $params[] = $clubName;
    }

    $sql .= " ORDER BY last_name, first_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

function getShooter($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM shooters WHERE id = ?");
    $stmt->execute([$id]);
    $shooter = $stmt->fetch();
    if (!$shooter) jsonResponse(['error' => 'Schütze nicht gefunden'], 404);

    // Get events
    $stmt = $pdo->prepare("
        SELECT e.* FROM events e
        JOIN event_participants ep ON e.id = ep.event_id
        WHERE ep.shooter_id = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$id]);
    $shooter['events'] = $stmt->fetchAll();

    // Get scores
    $stmt = $pdo->prepare("
        SELECT sc.*, e.name as event_name, ed.event_date
        FROM scores sc
        JOIN events e ON sc.event_id = e.id
        LEFT JOIN event_dates ed ON sc.event_date_id = ed.id
        WHERE sc.shooter_id = ?
        ORDER BY sc.created_at DESC
    ");
    $stmt->execute([$id]);
    $shooter['scores'] = $stmt->fetchAll();

    jsonResponse($shooter);
}

function createShooter($pdo) {
    $data = getJsonInput();
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['birth_year'])) {
        jsonResponse(['error' => 'Vorname, Nachname und Geburtsjahr sind erforderlich'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO shooters (first_name, last_name, birth_year, gender, club_name, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['birth_year'],
        $data['gender'] ?? 'm',
        $data['club_name'] ?? null,
        $data['email'] ?? null,
        $data['phone'] ?? null,
    ]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM shooters WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch(), 201);
}

function updateShooter($pdo, $id) {
    $data = getJsonInput();
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['birth_year'])) {
        jsonResponse(['error' => 'Vorname, Nachname und Geburtsjahr sind erforderlich'], 400);
    }

    $stmt = $pdo->prepare("UPDATE shooters SET first_name = ?, last_name = ?, birth_year = ?, gender = ?, club_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['birth_year'],
        $data['gender'] ?? 'm',
        $data['club_name'] ?? null,
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $id,
    ]);

    $stmt = $pdo->prepare("SELECT * FROM shooters WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch());
}

function deleteShooter($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM shooters WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
