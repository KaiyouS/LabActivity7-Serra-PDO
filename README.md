# LabActivity7-Serra-PDO

A simple, secure, text-only blog website built with PHP 8.3 and MySQL PDO, featuring session-based authentication, a community news feed, comments, ownership-based authorization, and cascading relational database integrity.

> **Note**: Per assignment instructions, this project strictly uses pure, semantic HTML with **zero CSS**.

---

## 1. Features & Requirements

### Relational Database Design (`blog_site`)
* **Tables**: Only three tables: `users`, `posts`, and `comments`.
* **Pivot / Junction Table**: The `comments` table serves as a junction table between `posts` and `users`, storing `post_id` and `user_id` as foreign keys.
* **Constraints**:
  * All Primary Keys (`id`) use `AUTO_INCREMENT`.
  * `email` column in `users` enforces a `UNIQUE` constraint.
  * All Foreign Keys (`posts.user_id`, `comments.post_id`, `comments.user_id`) enforce `ON DELETE CASCADE`.

### Form Validation
* **Client-side Validations**: HTML5 attributes (`required`, `type="email"`, `minlength`, `maxlength`).
* **Server-side Validations**: Rigorous server-side sanitization, empty checks, length checks, format validation (`filter_var`), and database uniqueness checks.

### Dedicated Database File (`db.php`)
* Configures and instantiates a robust `PDO` instance connected to `blog_site` using `utf8mb4`.
* Imported across application scripts using the PHP `require` construct.
* Configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` and `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.

### Onboarding & Authentication
* **Guest-Only Onboarding**: `register.php` and `login.php` are accessible only to unauthenticated visitors (logged-in users are redirected to `index.php`).
* **Native Password Security**: Uses PHP native `password_hash($password, PASSWORD_DEFAULT)` on registration and `password_verify($password, $user['password'])` on login.
* **Session Gates**: All inner pages (`index.php`, `edit_post.php`, `delete_post.php`, `edit_comment.php`, `delete_comment.php`) gate access, redirecting unauthenticated users to `login.php`.
* **Logout (`logout.php`)**: Wipes in-memory session superglobal (`$_SESSION = []`), destroys server-side storage (`session_destroy()`), and expires the session cookie.

### Community News Feed & Interactions
* **Home Page (`index.php`)**: Displays all published posts sorted by most recently posted (`ORDER BY created_at DESC, id DESC`).
* **Text-Only Posts**: Authenticated users can publish blog posts with a title and content.
* **Text-Only Comments**: Authenticated users can comment on any blog post.
* **Ownership Restrictions**:
  * Users can only edit or delete their own posts.
  * Users can only edit or delete their own comments.
  * Unauthorized edit/delete requests are blocked on the server.
* **Edited Markers**:
  * An `(edited)` tag is displayed on posts if they have been updated (`updated_at IS NOT NULL`).
  * An `(edited)` tag is displayed on comments if they have been updated (`updated_at IS NOT NULL`).
* **XSS Defense**: All dynamic user inputs are safely escaped using `htmlspecialchars()` prior to rendering.

---

## 2. Database Schema (`schema.sql`)

```sql
CREATE DATABASE IF NOT EXISTS `blog_site`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `blog_site`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_posts_user` (`user_id`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_comments_post` (`post_id`),
  KEY `fk_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Project Structure

```text
LabActivity7-Serra-PDO/
├── .gitignore          # Ignores IDE and temporary OS files
├── README.md           # Documentation and assignment overview
├── schema.sql          # Complete DDL SQL script for MySQL
├── db.php              # PDO configuration and connection
├── auth.php            # Session gates and authentication helper functions
├── header.php          # Semantic HTML header and navigation (No CSS)
├── footer.php          # Semantic HTML footer (No CSS)
├── register.php        # Guest onboarding: user registration
├── login.php           # Guest onboarding: user login
├── logout.php          # Session termination script
├── index.php           # Authenticated home feed & post/comment submission
├── edit_post.php       # Post edit form & ownership handler
├── delete_post.php     # Post deletion handler (Cascades comments)
├── edit_comment.php    # Comment edit form & ownership handler
└── delete_comment.php  # Comment deletion handler
```

---

## 4. Setup Instructions

1. Ensure **Apache** and **MySQL** are running in XAMPP.
2. Clone or place this repository under `C:/xampp/htdocs/LabActivity7-Serra-PDO`.
3. Import `schema.sql` into MySQL:
   ```bash
   mysql -u root -p < schema.sql
   ```
4. Access the web application in your browser:
   ```text
   http://localhost/LabActivity7-Serra-PDO/index.php
   ```
5. Register a new account or sign in to begin posting!
