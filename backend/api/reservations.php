<?php
function handleReservations($pdo, $method, $segments) {
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            getReservations($pdo);
            break;
        case 'POST':
            createReservation($pdo);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            deleteReservation($pdo, $id);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getReservations($pdo) {
    $eventId = $_GET['event_id'] ?? null;
    $timeSlotId = $_GET['time_slot_id'] ?? null;

    $sql = "
        SELECT sr.*, ts.start_time as slot_start, ts.end_time as slot_end,
            ed.event_date, ed.event_id,
            s.first_name, s.last_name, s.club_name
        FROM slot_reservations sr
        JOIN time_slots ts ON sr.time_slot_id = ts.id
        JOIN event_dates ed ON ts.event_date_id = ed.id
        LEFT JOIN shooters s ON sr.shooter_id = s.id
        WHERE 1=1
    ";
    $params = [];

    if ($eventId) {
        $sql .= " AND ed.event_id = ?";
        $params[] = $eventId;
    }
    if ($timeSlotId) {
        $sql .= " AND sr.time_slot_id = ?";
        $params[] = $timeSlotId;
    }

    $sql .= " ORDER BY ed.event_date, ts.start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

function createReservation($pdo) {
    $data = getJsonInput();

    if (empty($data['time_slot_ids']) || !is_array($data['time_slot_ids'])) {
        jsonResponse(['error' => 'time_slot_ids (Array) ist erforderlich'], 400);
    }

    $isGroup = !empty($data['is_group']) ? 1 : 0;
    $participantCount = $data['participant_count'] ?? 1;

    // Check availability for all slots
    foreach ($data['time_slot_ids'] as $slotId) {
        $stmt = $pdo->prepare("
            SELECT ts.max_participants, 
                COALESCE(SUM(sr.participant_count), 0) as reserved
            FROM time_slots ts
            LEFT JOIN slot_reservations sr ON sr.time_slot_id = ts.id
            WHERE ts.id = ?
            GROUP BY ts.id
        ");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            jsonResponse(['error' => "Zeitslot $slotId nicht gefunden"], 404);
        }
        if (($slot['reserved'] + $participantCount) > $slot['max_participants']) {
            jsonResponse(['error' => "Zeitslot $slotId ist bereits voll belegt"], 409);
        }
    }

    // Create reservations
    $ids = [];
    $stmt = $pdo->prepare("
        INSERT INTO slot_reservations (time_slot_id, shooter_id, group_name, is_group, participant_count, contact_name, contact_email, contact_phone)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($data['time_slot_ids'] as $slotId) {
        $stmt->execute([
            $slotId,
            $data['shooter_id'] ?? null,
            $data['group_name'] ?? null,
            $isGroup,
            $participantCount,
            $data['contact_name'] ?? null,
            $data['contact_email'] ?? null,
            $data['contact_phone'] ?? null,
        ]);
        $ids[] = $pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'reservation_ids' => $ids], 201);
}

function deleteReservation($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM slot_reservations WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
