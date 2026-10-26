<?php
/**
 * Create or reset an admin user.  Run from the command line (never web):
 *
 *   php bin/create_user.php <username> <display name>
 *
 * You will be prompted for a password (hidden). The password is hashed
 * with bcrypt before storage; the plain text is never written anywhere.
 */
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }

$user = $argv[1] ?? '';
$name = $argv[2] ?? $user;
if ($user === '') {
    fwrite(STDERR, "Usage: php bin/create_user.php <username> [display name]\n");
    exit(1);
}

// Read password without echoing.
fwrite(STDOUT, "Password: ");
shell_exec('stty -echo 2>/dev/null');
$pw = trim(fgets(STDIN));
shell_exec('stty echo 2>/dev/null');
fwrite(STDOUT, "\nConfirm:  ");
shell_exec('stty -echo 2>/dev/null');
$pw2 = trim(fgets(STDIN));
shell_exec('stty echo 2>/dev/null');
fwrite(STDOUT, "\n");

if ($pw === '' || $pw !== $pw2) { fwrite(STDERR, "Passwords empty or do not match.\n"); exit(1); }

$hash = password_hash($pw, PASSWORD_DEFAULT);
$existing = q1('SELECT id FROM users WHERE username = ?', [$user]);
if ($existing) {
    exec_sql('UPDATE users SET password_hash = ?, display_name = ? WHERE id = ?', [$hash, $name, $existing['id']]);
    echo "Updated password for '{$user}'.\n";
} else {
    exec_sql('INSERT INTO users (username, password_hash, display_name) VALUES (?,?,?)', [$user, $hash, $name]);
    echo "Created admin user '{$user}'.\n";
}
