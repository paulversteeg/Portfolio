-- =====================================================================
--  Waxine CMS - MySQL schema
--  Charset utf8mb4 so emoji / accents in old posts survive intact.
--  Run once:  mysql -u USER -p DBNAME < db/schema.sql
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
--  Admin users (single-author blog, but built to allow more)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(60)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash() / bcrypt
  display_name  VARCHAR(120) NOT NULL DEFAULT '',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Categories  (old site had a single "Thoughts" category, kept flexible)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name  VARCHAR(80)  NOT NULL,
  slug  VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Posts  (the "thoughts")
--   slug          = old fileNameWithoutDate (underscores), keeps URLs stable
--   legacy_filename = original YYYYMMDD_title.php, for reference / redirects
--   body_html     = fully rendered HTML content (migrated or authored)
--   status        = draft | published
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS posts (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) NOT NULL,
  legacy_filename VARCHAR(255) NULL,
  body_html       MEDIUMTEXT   NOT NULL,
  excerpt         VARCHAR(500) NOT NULL DEFAULT '',
  cover_image     VARCHAR(255) NULL,
  status          ENUM('draft','published') NOT NULL DEFAULT 'published',
  author_id       INT UNSIGNED NULL,
  published_at    DATETIME     NOT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_published (status, published_at),
  CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Post <-> Category (many-to-many)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS post_categories (
  post_id     INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, category_id),
  CONSTRAINT fk_pc_post     FOREIGN KEY (post_id)     REFERENCES posts(id)      ON DELETE CASCADE,
  CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Static pages (about / talks / publications / etc.)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title      VARCHAR(255) NOT NULL,
  slug       VARCHAR(255) NOT NULL,
  subtitle   VARCHAR(255) NOT NULL DEFAULT '',
  body_html  MEDIUMTEXT   NOT NULL,
  nav_order  INT          NOT NULL DEFAULT 0,
  in_nav     TINYINT(1)   NOT NULL DEFAULT 1,
  updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Seed data
-- ---------------------------------------------------------------------
INSERT IGNORE INTO categories (name, slug) VALUES ('Thoughts', 'thoughts');
