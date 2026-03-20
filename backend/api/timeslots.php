<?php
function handleTimeslots($pdo, $method, $segments) {
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            getTimeslots($pdo);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getTimeslots($pdo) {
    $eventDateId = $_GET['event_date_id'] ?? null;
    $eventId = $_GET['event_id'] ?? null;

    if ($eventDateId) {
        $stmt = $pdo->prepare("
            SELECT ts.*, 
                (SELECT COALESCE(SUM(sr.participant_count), 0) FROM slot_reservations sr WHERE sr.time_slot_id = ts.id) as reserved_count
            FROM time_slots ts
            WHERE ts.event_date_id = ?
            ORDER BY ts.start_time
        ");
        $stmt->execute([$eventDateId]);
        jsonResponse($stmt->fetchAll());
    } elseif ($eventId) {
        $stmt = $pdo->prepare("
            SELECT ts.*, ed.event_date,
                (SELECT COALESCE(SUM(sr.participant_count), 0) FROM slot_reservations sr WHERE sr.time_slot_id = ts.id) as reserved_count
            FROM time_slots ts
            JOIN event_dates ed ON ts.event_date_id = ed.id
            WHERE ed.event_id = ?
            ORDER BY ed.event_date, ts.start_time
        ");
        $stmt->execute([$eventId]);
        jsonResponse($stmt->fetchAll());
    } else {
        jsonResponse(['error' => 'event_date_id oder event_id erforderlich'], 400);
    }
}
