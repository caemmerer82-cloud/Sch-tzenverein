<?php
function handleEvents($pdo, $method, $segments) {
    $id = $segments[1] ?? null;
    $sub = $segments[2] ?? null;
    $subId = $segments[3] ?? null;

    // Sub-resources: /events/{id}/dates, /events/{id}/participants
    if ($id && $sub === 'dates') {
        handleEventDates($pdo, $method, $id, $subId);
        return;
    }
    if ($id && $sub === 'participants') {
        handleEventParticipants($pdo, $method, $id, $subId);
        return;
    }

    switch ($method) {
        case 'GET':
            if ($id) {
                getEvent($pdo, $id);
            } else {
                getEvents($pdo);
            }
            break;
        case 'POST':
            createEvent($pdo);
            break;
        case 'PUT':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            updateEvent($pdo, $id);
            break;
        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'ID erforderlich'], 400);
            deleteEvent($pdo, $id);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

function getEvents($pdo) {
    $stmt = $pdo->query("
        SELECT e.*, 
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) as participant_count,
            (SELECT COUNT(*) FROM event_dates ed WHERE ed.event_id = e.id) as date_count
        FROM events e 
        ORDER BY e.created_at DESC
    ");
    jsonResponse($stmt->fetchAll());
}

function getEvent($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    if (!$event) jsonResponse(['error' => 'Event nicht gefunden'], 404);

    // Get dates
    $stmt = $pdo->prepare("SELECT * FROM event_dates WHERE event_id = ? ORDER BY event_date, start_time");
    $stmt->execute([$id]);
    $event['dates'] = $stmt->fetchAll();

    // Get participants with shooter info
    $stmt = $pdo->prepare("
        SELECT ep.id as participant_id, ep.registered_at, s.*
        FROM event_participants ep
        JOIN shooters s ON ep.shooter_id = s.id
        WHERE ep.event_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$id]);
    $event['participants'] = $stmt->fetchAll();

    jsonResponse($event);
}

function createEvent($pdo) {
    $data = getJsonInput();
    if (empty($data['name'])) {
        jsonResponse(['error' => 'Name ist erforderlich'], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO events (name, description, location) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['location'] ?? null,
    ]);

    $eventId = $pdo->lastInsertId();

    // Create dates if provided
    if (!empty($data['dates'])) {
        $stmtDate = $pdo->prepare("INSERT INTO event_dates (event_id, event_date, start_time, end_time) VALUES (?, ?, ?, ?)");
        foreach ($data['dates'] as $date) {
            $stmtDate->execute([$eventId, $date['event_date'], $date['start_time'], $date['end_time']]);

            // Auto-generate 15-min time slots
            $dateId = $pdo->lastInsertId();
            generateTimeSlots($pdo, $dateId, $date['start_time'], $date['end_time']);
        }
    }

    getEvent($pdo, $eventId);
}

function updateEvent($pdo, $id) {
    $data = getJsonInput();
    if (empty($data['name'])) {
        jsonResponse(['error' => 'Name ist erforderlich'], 400);
    }

    $stmt = $pdo->prepare("UPDATE events SET name = ?, description = ?, location = ? WHERE id = ?");
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['location'] ?? null,
        $id,
    ]);

    getEvent($pdo, $id);
}

function deleteEvent($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// --- Event Dates ---
function handleEventDates($pdo, $method, $eventId, $dateId) {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->prepare("SELECT * FROM event_dates WHERE event_id = ? ORDER BY event_date, start_time");
            $stmt->execute([$eventId]);
            jsonResponse($stmt->fetchAll());
            break;
        case 'POST':
            $data = getJsonInput();
            $stmt = $pdo->prepare("INSERT INTO event_dates (event_id, event_date, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$eventId, $data['event_date'], $data['start_time'], $data['end_time']]);
            $newDateId = $pdo->lastInsertId();
            generateTimeSlots($pdo, $newDateId, $data['start_time'], $data['end_time']);
            $stmt = $pdo->prepare("SELECT * FROM event_dates WHERE id = ?");
            $stmt->execute([$newDateId]);
            jsonResponse($stmt->fetch(), 201);
            break;
        case 'DELETE':
            if (!$dateId) jsonResponse(['error' => 'Termin-ID erforderlich'], 400);
            $stmt = $pdo->prepare("DELETE FROM event_dates WHERE id = ? AND event_id = ?");
            $stmt->execute([$dateId, $eventId]);
            jsonResponse(['success' => true]);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

// --- Event Participants ---
function handleEventParticipants($pdo, $method, $eventId, $participantId) {
    switch ($method) {
        case 'POST':
            $data = getJsonInput();
            if (empty($data['shooter_id'])) {
                jsonResponse(['error' => 'shooter_id ist erforderlich'], 400);
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, shooter_id) VALUES (?, ?)");
                $stmt->execute([$eventId, $data['shooter_id']]);
                jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()], 201);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'Teilnehmer bereits angemeldet'], 409);
                }
                throw $e;
            }
            break;
        case 'DELETE':
            if (!$participantId) jsonResponse(['error' => 'Teilnehmer-ID erforderlich'], 400);
            // participantId here is the shooter_id
            $stmt = $pdo->prepare("DELETE FROM event_participants WHERE event_id = ? AND shooter_id = ?");
            $stmt->execute([$eventId, $participantId]);
            jsonResponse(['success' => true]);
            break;
        default:
            jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }
}

// --- Helper: Generate 15-min time slots ---
function generateTimeSlots($pdo, $dateId, $startTime, $endTime) {
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    $interval = 15 * 60; // 15 minutes

    $stmt = $pdo->prepare("INSERT INTO time_slots (event_date_id, start_time, end_time) VALUES (?, ?, ?)");
    
    while ($start < $end) {
        $slotEnd = min($start + $interval, $end);
        $stmt->execute([
            $dateId,
            date('H:i:s', $start),
            date('H:i:s', $slotEnd),
        ]);
        $start = $slotEnd;
    }
}
