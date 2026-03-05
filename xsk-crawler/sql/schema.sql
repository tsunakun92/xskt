CREATE TABLE IF NOT EXISTS sources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  base_url VARCHAR(255) NOT NULL,
  weight TINYINT NOT NULL DEFAULT 1,
  is_official TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS draws (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  region ENUM('MB','MT','MN') NOT NULL,
  province_code VARCHAR(32) NOT NULL,
  station_code VARCHAR(32) NOT NULL,
  draw_date DATE NOT NULL,
  status ENUM('initial','collecting_live','disputed','confirmed','confirmed_with_conflict') NOT NULL DEFAULT 'initial',
  confirmed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_draw (region, province_code, station_code, draw_date)
);

CREATE TABLE IF NOT EXISTS results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  draw_id BIGINT UNSIGNED NOT NULL,
  prize_code VARCHAR(16) NOT NULL,
  index_in_prize INT NOT NULL DEFAULT 0,
  number VARCHAR(16) NOT NULL,
  confirmed_by_rule ENUM('majority','single_source_weighted','correction') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_results_draw FOREIGN KEY (draw_id) REFERENCES draws(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS raw_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  draw_id BIGINT UNSIGNED NOT NULL,
  source_id INT UNSIGNED NOT NULL,
  snapshot_time DATETIME NOT NULL,
  raw_payload MEDIUMTEXT NULL,
  parsed_ok TINYINT(1) NOT NULL DEFAULT 0,
  sanity_ok TINYINT(1) NOT NULL DEFAULT 0,
  parsed_result JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_snapshots_draw FOREIGN KEY (draw_id) REFERENCES draws(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_snapshots_source FOREIGN KEY (source_id) REFERENCES sources(id)
    ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS anomalies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  draw_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(64) NOT NULL,
  details JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_anomalies_draw FOREIGN KEY (draw_id) REFERENCES draws(id)
    ON DELETE CASCADE
);

INSERT INTO sources (code, name, base_url, weight, is_official, active)
VALUES
  ('xoso_com_vn', 'Xoso.com.vn', 'https://xoso.com.vn', 2, 0, 1),
  ('az24_vn', 'AZ24.vn', 'https://az24.vn', 1, 0, 1),
  ('xosodaiphat_com', 'XosoDaiPhat.com', 'https://xosodaiphat.com', 1, 0, 1),
  ('minh_ngoc_net', 'MinhNgoc.net', 'https://www.minhngoc.net', 1, 0, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  base_url = VALUES(base_url),
  weight = VALUES(weight),
  is_official = VALUES(is_official),
  active = VALUES(active);


