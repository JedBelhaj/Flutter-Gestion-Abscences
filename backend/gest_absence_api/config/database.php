<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!defined('GEST_ABSENCE_DB_HOST')) {
	define('GEST_ABSENCE_DB_HOST', getenv('GEST_ABSENCE_DB_HOST') ?: 'localhost');
}

if (!defined('GEST_ABSENCE_DB_USER')) {
	define('GEST_ABSENCE_DB_USER', getenv('GEST_ABSENCE_DB_USER') ?: 'root');
}

if (!defined('GEST_ABSENCE_DB_PASSWORD')) {
	define('GEST_ABSENCE_DB_PASSWORD', getenv('GEST_ABSENCE_DB_PASSWORD') ?: '');
}

if (!defined('GEST_ABSENCE_DB_PORT')) {
	define('GEST_ABSENCE_DB_PORT', (int) (getenv('GEST_ABSENCE_DB_PORT') ?: 3306));
}

if (!defined('GEST_ABSENCE_DB_NAME')) {
	define('GEST_ABSENCE_DB_NAME', getenv('GEST_ABSENCE_DB_NAME') ?: 'gest_absence');
}

if (!defined('GEST_ABSENCE_TOKEN_SECRET')) {
	define('GEST_ABSENCE_TOKEN_SECRET', getenv('GEST_ABSENCE_TOKEN_SECRET') ?: 'gest-absence-dev-secret');
}

function connectMysqlServer(?string $database = null): mysqli
{
	$connection = new mysqli(
		GEST_ABSENCE_DB_HOST,
		GEST_ABSENCE_DB_USER,
		GEST_ABSENCE_DB_PASSWORD,
		$database ?? '',
		GEST_ABSENCE_DB_PORT
	);
	$connection->set_charset('utf8mb4');

	return $connection;
}

function bootstrapDatabase(mysqli $connection): void
{
	$connection->query(
		"CREATE DATABASE IF NOT EXISTS `" . GEST_ABSENCE_DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
	);
	$connection->select_db(GEST_ABSENCE_DB_NAME);
	$connection->set_charset('utf8mb4');

	$connection->multi_query(
		"CREATE TABLE IF NOT EXISTS utilisateurs (
			id INT AUTO_INCREMENT PRIMARY KEY,
			nom VARCHAR(100) NOT NULL,
			prenom VARCHAR(100) NOT NULL,
			email VARCHAR(150) NOT NULL UNIQUE,
			password VARCHAR(255) NOT NULL,
			role ENUM('admin','enseignant','etudiant') NOT NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);

		CREATE TABLE IF NOT EXISTS classes (
			id INT AUTO_INCREMENT PRIMARY KEY,
			nom VARCHAR(50) NOT NULL,
			niveau VARCHAR(50)
		);

		CREATE TABLE IF NOT EXISTS etudiants (
			id INT AUTO_INCREMENT PRIMARY KEY,
			utilisateur_id INT NOT NULL,
			classe_id INT NOT NULL,
			FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
			FOREIGN KEY (classe_id) REFERENCES classes(id)
		);

		CREATE TABLE IF NOT EXISTS enseignants (
			id INT AUTO_INCREMENT PRIMARY KEY,
			utilisateur_id INT NOT NULL,
			specialite VARCHAR(100),
			FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
		);

		CREATE TABLE IF NOT EXISTS matieres (
			id INT AUTO_INCREMENT PRIMARY KEY,
			nom VARCHAR(100) NOT NULL
		);

		CREATE TABLE IF NOT EXISTS seances (
			id INT AUTO_INCREMENT PRIMARY KEY,
			enseignant_id INT NOT NULL,
			classe_id INT NOT NULL,
			matiere_id INT NOT NULL,
			date_seance DATE NOT NULL,
			heure_debut TIME NOT NULL,
			heure_fin TIME NOT NULL,
			FOREIGN KEY (enseignant_id) REFERENCES enseignants(id),
			FOREIGN KEY (classe_id) REFERENCES classes(id),
			FOREIGN KEY (matiere_id) REFERENCES matieres(id)
		);

		CREATE TABLE IF NOT EXISTS absences (
			id INT AUTO_INCREMENT PRIMARY KEY,
			seance_id INT NOT NULL,
			etudiant_id INT NOT NULL,
			statut ENUM('present','absent') NOT NULL DEFAULT 'present',
			UNIQUE KEY unique_appel (seance_id, etudiant_id),
			FOREIGN KEY (seance_id) REFERENCES seances(id),
			FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
		);"
	);

	while ($connection->more_results() && $connection->next_result()) {
		$result = $connection->store_result();
		if ($result instanceof mysqli_result) {
			$result->free();
		}
	}

	$connection->query(
		"INSERT IGNORE INTO utilisateurs (nom, prenom, email, password, role) VALUES
		('Admin','Systeme','admin@school.tn','admin123','admin'),
		('Ben Ali','Sami','sami@school.tn','prof123','enseignant'),
		('Trabelsi','Amine','amine@school.tn','etu123','etudiant')"
	);

	$connection->query(
		"INSERT IGNORE INTO classes (nom, niveau)
		VALUES ('CI2-A', 'Cycle Ingenieur 2')"
	);

	$connection->query(
		"INSERT IGNORE INTO matieres (nom) VALUES
		('Developpement Mobile'),
		('Reseaux'),
		('BDD')"
	);

	$connection->query(
		"INSERT INTO enseignants (utilisateur_id, specialite)
		SELECT u.id, 'Informatique'
		FROM utilisateurs u
		WHERE u.email = 'sami@school.tn'
		  AND NOT EXISTS (
			  SELECT 1 FROM enseignants e WHERE e.utilisateur_id = u.id
		  )"
	);

	$connection->query(
		"INSERT INTO etudiants (utilisateur_id, classe_id)
		SELECT u.id, c.id
		FROM utilisateurs u
		JOIN classes c ON c.nom = 'CI2-A'
		WHERE u.email = 'amine@school.tn'
		  AND NOT EXISTS (
			  SELECT 1 FROM etudiants e WHERE e.utilisateur_id = u.id
		  )"
	);
}

function getDbConnection(): ?mysqli
{
	try {
		$connection = connectMysqlServer(GEST_ABSENCE_DB_NAME);
		return $connection;
	} catch (Throwable $e) {
		try {
			$server = connectMysqlServer(null);
			bootstrapDatabase($server);
			return $server;
		} catch (Throwable $bootstrapError) {
			if (strcasecmp(GEST_ABSENCE_DB_HOST, 'localhost') === 0) {
				try {
					$server = new mysqli(
						'127.0.0.1',
						GEST_ABSENCE_DB_USER,
						GEST_ABSENCE_DB_PASSWORD,
						'',
						GEST_ABSENCE_DB_PORT
					);
					$server->set_charset('utf8mb4');
					bootstrapDatabase($server);
					return $server;
				} catch (Throwable $fallbackError) {
					return null;
				}
			}

			return null;
		}
	}
}

function apiCors(array $allowedMethods = ['GET', 'POST', 'OPTIONS']): void
{
	header('Content-Type: application/json; charset=utf-8');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
	header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

function respondJson(int $statusCode, array $payload): void
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

function requestPayload(): array
{
	static $payload = null;

	if (is_array($payload)) {
		return $payload;
	}

	$payload = [];
	$rawBody = file_get_contents('php://input');
	if ($rawBody !== false && trim($rawBody) !== '') {
		$decoded = json_decode($rawBody, true);
		if (is_array($decoded)) {
			$payload = $decoded;
			return $payload;
		}
	}

	if (!empty($_POST)) {
		$payload = $_POST;
	}

	return $payload;
}

function requestInput(string $key, mixed $default = null): mixed
{
	$payload = requestPayload();
	if (array_key_exists($key, $payload)) {
		return $payload[$key];
	}
	if (array_key_exists($key, $_GET)) {
		return $_GET[$key];
	}
	if (array_key_exists($key, $_POST)) {
		return $_POST[$key];
	}
	if (array_key_exists($key, $_REQUEST)) {
		return $_REQUEST[$key];
	}

	return $default;
}

function requireHttpMethod(array $allowedMethods): void
{
	if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $allowedMethods, true)) {
		respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
	}
}

function normalizeString(mixed $value): ?string
{
	if ($value === null) {
		return null;
	}

	$text = trim((string) $value);
	return $text === '' ? null : $text;
}

function normalizeInt(mixed $value): ?int
{
	if ($value === null || $value === '') {
		return null;
	}

	if (is_int($value)) {
		return $value > 0 ? $value : null;
	}

	if (!is_numeric($value)) {
		return null;
	}

	$intValue = (int) $value;
	return $intValue > 0 ? $intValue : null;
}

function validateDateOrNull(mixed $value): ?string
{
	$text = normalizeString($value);
	if ($text === null) {
		return null;
	}

	return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) === 1 ? $text : null;
}

function validateTimeOrNull(mixed $value): ?string
{
	$text = normalizeString($value);
	if ($text === null) {
		return null;
	}

	return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $text) === 1 ? $text : null;
}

function bindStatementParams(mysqli_stmt $stmt, string $types, array $params): void
{
	if ($types === '') {
		return;
	}

	$references = [];
	foreach ($params as $index => $value) {
		$references[$index] = &$params[$index];
	}

	array_unshift($references, $types);
	mysqli_stmt_bind_param($stmt, ...$references);
}

function fetchAllAssoc(mysqli_stmt $stmt): array
{
	$result = mysqli_stmt_get_result($stmt);
	$rows = [];
	if ($result instanceof mysqli_result) {
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		mysqli_free_result($result);
	}

	return $rows;
}

function base64UrlEncode(string $value): string
{
	return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): string|false
{
	$padding = strlen($value) % 4;
	if ($padding > 0) {
		$value .= str_repeat('=', 4 - $padding);
	}

	return base64_decode(strtr($value, '-_', '+/'), true);
}

function getRequestToken(): ?string
{
	$headerCandidates = [
		$_SERVER['HTTP_AUTHORIZATION'] ?? null,
		$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
		$_SERVER['Authorization'] ?? null,
	];

	if (function_exists('getallheaders')) {
		$headers = getallheaders();
		if (is_array($headers)) {
			$headerCandidates[] = $headers['Authorization'] ?? null;
			$headerCandidates[] = $headers['authorization'] ?? null;
		}
	}

	foreach ($headerCandidates as $header) {
		if (is_string($header) && preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) === 1) {
			return trim($matches[1]);
		}
	}

	$token = normalizeString(requestInput('token'));
	if ($token !== null) {
		return $token;
	}

	$authorization = normalizeString(requestInput('authorization'));
	if ($authorization !== null && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
		return trim($matches[1]);
	}

	return null;
}

function createAuthToken(array $user, int $ttlSeconds = 86400): string
{
	$issuedAt = time();
	$payload = [
		'sub' => (int) $user['id'],
		'role' => (string) $user['role'],
		'nom' => (string) $user['nom'],
		'prenom' => (string) $user['prenom'],
		'email' => (string) ($user['email'] ?? ''),
		'iat' => $issuedAt,
		'exp' => $issuedAt + $ttlSeconds,
	];

	$encodedPayload = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	$signature = hash_hmac('sha256', $encodedPayload, GEST_ABSENCE_TOKEN_SECRET, true);

	return $encodedPayload . '.' . base64UrlEncode($signature);
}

function decodeAuthToken(string $token): ?array
{
	$parts = explode('.', $token);
	if (count($parts) !== 2) {
		return null;
	}

	[$encodedPayload, $encodedSignature] = $parts;
	$payloadJson = base64UrlDecode($encodedPayload);
	$expectedSignature = base64UrlEncode(hash_hmac('sha256', $encodedPayload, GEST_ABSENCE_TOKEN_SECRET, true));

	if ($payloadJson === false || !hash_equals($expectedSignature, $encodedSignature)) {
		return null;
	}

	$payload = json_decode($payloadJson, true);
	if (!is_array($payload) || !isset($payload['sub'], $payload['role'], $payload['exp'])) {
		return null;
	}

	if ((int) $payload['exp'] < time()) {
		return null;
	}

	return $payload;
}

function fetchUserRecordById(mysqli $cnx, int $userId): ?array
{
	$stmt = mysqli_prepare(
		$cnx,
		'SELECT u.id,
				u.nom,
				u.prenom,
				u.email,
				u.role,
				e.id AS enseignant_id,
				e.specialite,
				t.id AS etudiant_id,
				t.classe_id,
				c.nom AS classe_nom,
				c.niveau
		 FROM utilisateurs u
		 LEFT JOIN enseignants e ON e.utilisateur_id = u.id
		 LEFT JOIN etudiants t ON t.utilisateur_id = u.id
		 LEFT JOIN classes c ON c.id = t.classe_id
		 WHERE u.id = ?
		 LIMIT 1'
	);

	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $userId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

function currentUserFromToken(mysqli $cnx, string $token): ?array
{
	$payload = decodeAuthToken($token);
	if ($payload === null) {
		return null;
	}

	$user = fetchUserRecordById($cnx, (int) $payload['sub']);
	if ($user === null) {
		return null;
	}

	if ((string) $user['role'] !== (string) $payload['role']) {
		return null;
	}

	return $user;
}

function requireRole(mysqli $cnx, array $allowedRoles = []): array
{
	$token = getRequestToken();
	if ($token === null) {
		respondJson(401, ['success' => false, 'message' => 'Authentication token is required']);
	}

	$user = currentUserFromToken($cnx, $token);
	if ($user === null) {
		respondJson(401, ['success' => false, 'message' => 'Invalid or expired token']);
	}

	if ($allowedRoles !== [] && !in_array($user['role'], $allowedRoles, true)) {
		respondJson(403, ['success' => false, 'message' => 'You do not have access to this resource']);
	}

	return $user;
}

function recordExists(mysqli $cnx, string $table, int $id): bool
{
	$allowedTables = ['classes', 'enseignants', 'etudiants', 'matieres', 'seances', 'utilisateurs'];
	if (!in_array($table, $allowedTables, true) || $id <= 0) {
		return false;
	}

	$stmt = mysqli_prepare($cnx, "SELECT id FROM `$table` WHERE id = ? LIMIT 1");
	if (!$stmt) {
		return false;
	}

	mysqli_stmt_bind_param($stmt, 'i', $id);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$exists = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;
	if ($result instanceof mysqli_result) {
		mysqli_free_result($result);
	}
	mysqli_stmt_close($stmt);

	return $exists;
}

$cnx = getDbConnection();