CREATE TABLE IF NOT EXISTS event_route_stops (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX event_route_stops_event_index (event_id, sort_order, id),
  CONSTRAINT event_route_stops_event_fk FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
