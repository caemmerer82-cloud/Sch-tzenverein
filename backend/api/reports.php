<?php
function handleReports($pdo, $method, $segments) {
    if ($method !== 'GET') {
        jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
    }

    $type = $segments[1] ?? null;

    switch ($type) {
        case 'event':
            reportByEvent($pdo);
            break;
        case 'shooter':
            reportByShooter($pdo);
            break;
        case 'age-group':
            reportByAgeGroup($pdo);
            break;
        default:
            jsonResponse(['error' => 'Unbekannter Report-Typ. Verfügbar: event, shooter, age-group'], 400);
    }
}

function reportByEvent($pdo) {
    $eventId = $_GET['event_id'] ?? null;
    if (!$eventId) {
        jsonResponse(['error' => 'event_id erforderlich'], 400);
    }

    // Event info
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    if (!$event) jsonResponse(['error' => 'Event nicht gefunden'], 404);

    // Results grouped by shooter with total points
    $stmt = $pdo->prepare("
        SELECT 
            s.id as shooter_id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name,
            SUM(sc.points) as total_points,
            COUNT(sc.id) as score_count,
            AVG(sc.points) as avg_points,
            MAX(sc.points) as max_points
        FROM scores sc
        JOIN shooters s ON sc.shooter_id = s.id
        WHERE sc.event_id = ?
        GROUP BY s.id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name
        ORDER BY total_points DESC
    ");
    $stmt->execute([$eventId]);
    $results = $stmt->fetchAll();

    // Add rank
    $rank = 1;
    foreach ($results as &$r) {
        $r['rank'] = $rank++;
        $r['total_points'] = floatval($r['total_points']);
        $r['avg_points'] = round(floatval($r['avg_points']), 2);
        $r['max_points'] = floatval($r['max_points']);
    }

    // Results per date
    $stmt = $pdo->prepare("
        SELECT ed.id, ed.event_date, ed.start_time, ed.end_time
        FROM event_dates ed
        WHERE ed.event_id = ?
        ORDER BY ed.event_date
    ");
    $stmt->execute([$eventId]);
    $dates = $stmt->fetchAll();

    $dateResults = [];
    foreach ($dates as $date) {
        $stmt = $pdo->prepare("
            SELECT 
                s.id as shooter_id, s.first_name, s.last_name, s.club_name,
                sc.points, sc.notes
            FROM scores sc
            JOIN shooters s ON sc.shooter_id = s.id
            WHERE sc.event_date_id = ?
            ORDER BY sc.points DESC
        ");
        $stmt->execute([$date['id']]);
        $dateResults[] = [
            'date' => $date,
            'scores' => $stmt->fetchAll(),
        ];
    }

    jsonResponse([
        'event' => $event,
        'overall_ranking' => $results,
        'date_results' => $dateResults,
    ]);
}

function reportByShooter($pdo) {
    $shooterId = $_GET['shooter_id'] ?? null;

    if ($shooterId) {
        // Single shooter report
        $stmt = $pdo->prepare("SELECT * FROM shooters WHERE id = ?");
        $stmt->execute([$shooterId]);
        $shooter = $stmt->fetch();
        if (!$shooter) jsonResponse(['error' => 'Schütze nicht gefunden'], 404);

        $stmt = $pdo->prepare("
            SELECT 
                e.id as event_id, e.name as event_name,
                SUM(sc.points) as total_points,
                COUNT(sc.id) as score_count,
                AVG(sc.points) as avg_points,
                MAX(sc.points) as max_points
            FROM scores sc
            JOIN events e ON sc.event_id = e.id
            WHERE sc.shooter_id = ?
            GROUP BY e.id, e.name
            ORDER BY e.name
        ");
        $stmt->execute([$shooterId]);
        $events = $stmt->fetchAll();

        foreach ($events as &$ev) {
            $ev['total_points'] = floatval($ev['total_points']);
            $ev['avg_points'] = round(floatval($ev['avg_points']), 2);
            $ev['max_points'] = floatval($ev['max_points']);
        }

        // All individual scores
        $stmt = $pdo->prepare("
            SELECT sc.*, e.name as event_name, ed.event_date
            FROM scores sc
            JOIN events e ON sc.event_id = e.id
            LEFT JOIN event_dates ed ON sc.event_date_id = ed.id
            WHERE sc.shooter_id = ?
            ORDER BY sc.created_at DESC
        ");
        $stmt->execute([$shooterId]);

        jsonResponse([
            'shooter' => $shooter,
            'event_summary' => $events,
            'scores' => $stmt->fetchAll(),
        ]);
    } else {
        // All shooters summary
        $stmt = $pdo->query("
            SELECT 
                s.id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name,
                COUNT(DISTINCT sc.event_id) as event_count,
                COALESCE(SUM(sc.points), 0) as total_points,
                COALESCE(AVG(sc.points), 0) as avg_points
            FROM shooters s
            LEFT JOIN scores sc ON s.id = sc.shooter_id
            GROUP BY s.id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name
            ORDER BY total_points DESC
        ");
        jsonResponse($stmt->fetchAll());
    }
}

function reportByAgeGroup($pdo) {
    $eventId = $_GET['event_id'] ?? null;

    // Get age groups
    $stmt = $pdo->query("SELECT * FROM age_groups ORDER BY sort_order");
    $ageGroups = $stmt->fetchAll();

    $results = [];
    foreach ($ageGroups as $group) {
        $sql = "
            SELECT 
                s.id as shooter_id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name,
                SUM(sc.points) as total_points,
                COUNT(sc.id) as score_count,
                AVG(sc.points) as avg_points
            FROM scores sc
            JOIN shooters s ON sc.shooter_id = s.id
            WHERE s.birth_year BETWEEN ? AND ?
        ";
        $params = [$group['min_birth_year'], $group['max_birth_year']];

        if ($group['gender'] !== 'all') {
            $sql .= " AND s.gender = ?";
            $params[] = $group['gender'];
        }

        if ($eventId) {
            $sql .= " AND sc.event_id = ?";
            $params[] = $eventId;
        }

        $sql .= " GROUP BY s.id, s.first_name, s.last_name, s.birth_year, s.gender, s.club_name
                   ORDER BY total_points DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $shooters = $stmt->fetchAll();

        $rank = 1;
        foreach ($shooters as &$sh) {
            $sh['rank'] = $rank++;
            $sh['total_points'] = floatval($sh['total_points']);
            $sh['avg_points'] = round(floatval($sh['avg_points']), 2);
        }

        $results[] = [
            'age_group' => $group,
            'shooters' => $shooters,
        ];
    }

    jsonResponse($results);
}
