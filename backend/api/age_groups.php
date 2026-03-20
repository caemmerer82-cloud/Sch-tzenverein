<?php
function handleAgeGroups($pdo, $method, $segments) {
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            getAgeGroups($pdo);
            break;
        case 'POST':
            createAgeGroup($pdo);
            break;
        case 'PUT':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            updateAgeGroup($pdo, $id);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            deleteAgeGroup($pdo, $id);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getAgeGroups($pdo) {
    $stmt = $pdo->query("SELECT * FROM age_groups ORDER BY sort_order, name");
    jsonResponse($stmt->fetchAll());
}

function createAgeGroup($pdo) {
    $data = getJsonInput();
    if (empty($data['name']) || !isset($data['min_birth_year']) || !isset($data['max_birth_year'])) {
        jsonResponse(['error' => 'Name, min_birth_year und max_birth_year sind erforderlich'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO age_groups (name, min_birth_year, max_birth_year, gender, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['min_birth_year'],
        $data['max_birth_year'],
        $data['gender'] ?? 'all',
        $data['sort_order'] ?? 0,
    ]);

    jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()], 201);
}

function updateAgeGroup($pdo, $id) {
    $data = getJsonInput();
    $stmt = $pdo->prepare("UPDATE age_groups SET name = ?, min_birth_year = ?, max_birth_year = ?, gender = ?, sort_order = ? WHERE id = ?");
    $stmt->execute([
        $data['name'],
        $data['min_birth_year'],
        $data['max_birth_year'],
        $data['gender'] ?? 'all',
        $data['sort_order'] ?? 0,
        $id,
    ]);
    jsonResponse(['success' => true]);
}

function deleteAgeGroup($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM age_groups WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
