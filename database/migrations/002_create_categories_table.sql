CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL UNIQUE,
    api_slug    VARCHAR(80)  NULL COMMENT 'Matching DummyJSON category',
    icon        VARCHAR(30)  NOT NULL DEFAULT 'chip',
    description VARCHAR(255) NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, slug, api_slug, icon, description, sort_order) VALUES
 ('Smartphones', 'smartphones', 'smartphones',        'phone',  'Flagships, mid-rangers and budget phones', 1),
 ('Laptops',     'laptops',     'laptops',            'laptop', 'Ultrabooks, gaming rigs and workstations', 2),
 ('Tablets',     'tablets',     'tablets',            'tablet', 'Tablets for drawing, study and streaming', 3),
 ('Accessories', 'accessories', 'mobile-accessories', 'plug',   'Chargers, earbuds, cases, watches and more', 4);
