CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(512) NOT NULL,
  email VARCHAR(512) NOT NULL,
  subject VARCHAR(512) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new', 'read', 'archived') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX contacts_created_at_index (created_at),
  INDEX contacts_status_index (status),
  INDEX contacts_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS event_attendees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  name VARCHAR(512) NOT NULL,
  motorcycle_model VARCHAR(512) NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX event_attendees_event_index (event_id, created_at),
  CONSTRAINT event_attendees_event_fk FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  sizes VARCHAR(255) NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX shop_products_published_index (is_published),
  INDEX shop_products_sort_index (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shop_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(180) NOT NULL,
  selected_size VARCHAR(40) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  customer_name VARCHAR(512) NOT NULL,
  customer_email VARCHAR(512) NOT NULL,
  customer_phone VARCHAR(512) NULL,
  notes TEXT NULL,
  status ENUM('new', 'processing', 'completed', 'archived') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX shop_requests_status_index (status),
  INDEX shop_requests_created_at_index (created_at),
  INDEX shop_requests_product_index (product_id),
  CONSTRAINT shop_requests_product_fk FOREIGN KEY (product_id) REFERENCES shop_products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shop_products (name, description, sizes, image_path, sort_order, is_published)
SELECT 'Cappellino Ufficiale Unfaired Motoclub', 'Cappellino ufficiale riservato ai soci, pensato per eventi, ride e attivita del club.', 'Unica', 'assets/img/cappellini.jpeg', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM shop_products WHERE name = 'Cappellino Ufficiale Unfaired Motoclub');

INSERT INTO shop_products (name, description, sizes, image_path, sort_order, is_published)
SELECT 'Felpa Ufficiale Unfaired Motoclub', 'Felpa ufficiale del club, riservata ai soci e utilizzata negli eventi ufficiali.', 'S, M, L, XL, XXL', 'assets/img/felpa.jpeg', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM shop_products WHERE name = 'Felpa Ufficiale Unfaired Motoclub');

INSERT INTO shop_products (name, description, sizes, image_path, sort_order, is_published)
SELECT 'T-shirt Ufficiale Unfaired Motoclub', 'T-shirt ufficiale riservata ai soci, pensata per rappresentare il club nelle attivita ufficiali.', 'S, M, L, XL, XXL', 'assets/img/t-shirt.jpeg', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM shop_products WHERE name = 'T-shirt Ufficiale Unfaired Motoclub');

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Giro delle Alpi', 'Una giornata tra passi di montagna, curve e panorami spettacolari.', '2026-07-12', '08:30:00', 'Torino', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Giro delle Alpi');

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Unfired Summer Night', 'Serata aperta a tutti con musica, moto, food e nuovi incontri.', '2026-07-26', '19:00:00', 'Club House', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Unfired Summer Night');

INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published)
SELECT 'Ride for Charity', 'Un motoraduno solidale per sostenere un progetto del territorio.', '2026-09-06', '09:00:00', 'Piemonte', 'assets/img/home.png', 1
WHERE NOT EXISTS (SELECT 1 FROM events WHERE title = 'Ride for Charity');
