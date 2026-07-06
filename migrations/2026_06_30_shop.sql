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
  customer_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(40) NULL,
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
