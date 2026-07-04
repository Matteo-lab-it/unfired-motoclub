ALTER TABLE contacts
  ADD COLUMN IF NOT EXISTS status ENUM('new', 'read', 'archived') NOT NULL DEFAULT 'new' AFTER message;

CREATE INDEX IF NOT EXISTS contacts_status_index ON contacts (status);

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  event_date DATE NOT NULL,
  event_time TIME NOT NULL,
  location VARCHAR(160) NOT NULL,
  route_summary TEXT NULL,
  map_url VARCHAR(500) NULL,
  image_url VARCHAR(500) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX events_date_index (event_date, event_time),
  INDEX events_published_index (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE events
  ADD COLUMN IF NOT EXISTS route_summary TEXT NULL AFTER location,
  ADD COLUMN IF NOT EXISTS map_url VARCHAR(500) NULL AFTER route_summary;
CREATE TABLE IF NOT EXISTS event_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  caption VARCHAR(180) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX event_photos_event_index (event_id, sort_order, id),
  CONSTRAINT event_photos_event_fk FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Giro delle Alpi', 'Una giornata tra passi di montagna, curve e panorami spettacolari.', '2026-07-12', '08:30:00', 'Torino', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Giro delle Alpi');

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Unfired Summer Night', 'Serata aperta a tutti con musica, moto, food e nuovi incontri.', '2026-07-26', '19:00:00', 'Club House', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Unfired Summer Night');

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Ride for Charity', 'Un motoraduno solidale per sostenere un progetto del territorio.', '2026-09-06', '09:00:00', 'Piemonte', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Ride for Charity');
