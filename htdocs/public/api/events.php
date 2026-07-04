<?php
declare(strict_types=1);

require __DIR__ . '/../../src/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id > 0) {
        $statement = db()->prepare(
            'SELECT e.id, e.title, e.description, e.event_date, e.event_time, e.location, e.route_summary, e.map_url, e.image_url,
                    (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
             FROM events e
             WHERE e.id = :id AND e.is_published = 1
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $event = $statement->fetch();

        if (!$event) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'event' => null,
                'photos' => [],
            ]);
            exit;
        }

        $photosStatement = db()->prepare(
            'SELECT image_path, caption
             FROM event_photos
             WHERE event_id = :event_id
             ORDER BY sort_order ASC, id ASC'
        );
        $photosStatement->execute(['event_id' => $id]);

        $routeStopsStatement = db()->prepare(
            'SELECT title, description
             FROM event_route_stops
             WHERE event_id = :event_id
             ORDER BY sort_order ASC, id ASC'
        );
        $routeStopsStatement->execute(['event_id' => $id]);

        echo json_encode([
            'success' => true,
            'event' => $event,
            'photos' => $photosStatement->fetchAll(),
            'route_stops' => $routeStopsStatement->fetchAll(),
        ]);
        exit;
    }

    $limit = min(max((int) ($_GET['limit'] ?? 12), 1), 60);

    $statement = db()->query(
        'SELECT e.id, e.title, e.description, e.event_date, e.event_time, e.location, e.route_summary, e.map_url, e.image_url,
                (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
         FROM events e
         WHERE e.is_published = 1
         ORDER BY (e.event_date < CURDATE()) ASC,
                  CASE WHEN e.event_date >= CURDATE() THEN e.event_date END ASC,
                  CASE WHEN e.event_date < CURDATE() THEN e.event_date END DESC,
                  e.event_time ASC
         LIMIT ' . $limit
    );

    echo json_encode([
        'success' => true,
        'events' => $statement->fetchAll(),
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'events' => [],
    ]);
}
