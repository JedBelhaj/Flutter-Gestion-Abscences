<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

apiCors(['GET', 'POST', 'OPTIONS']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
	respondJson(204, []);
}

if (!isset($cnx) || !$cnx) {
	respondJson(500, ['success' => false, 'message' => 'Database connection failed']);
}

function fetchAuthUserByEmail(mysqli $cnx, string $email): ?array
{
	$stmt = mysqli_prepare(
		$cnx,
		'SELECT u.id,
				u.nom,
				u.prenom,
				u.email,
				u.password,
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
		 WHERE u.email = ?
		 LIMIT 1'
	);

	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 's', $email);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

function publicUserData(array $row): array
{
	$user = [
		'id' => (int) $row['id'],
		'nom' => $row['nom'],
		'prenom' => $row['prenom'],
		'email' => $row['email'],
		'role' => $row['role'],
	];

	if (!empty($row['enseignant_id'])) {
		$user['enseignant_id'] = (int) $row['enseignant_id'];
		$user['specialite'] = $row['specialite'];
	}

	if (!empty($row['etudiant_id'])) {
		$user['etudiant_id'] = (int) $row['etudiant_id'];
		$user['classe_id'] = isset($row['classe_id']) ? (int) $row['classe_id'] : null;
		$user['classe_nom'] = $row['classe_nom'];
		$user['niveau'] = $row['niveau'];
	}

	return $user;
}

function passwordIsValid(string $plainPassword, string $storedPassword): bool
{
	if ($storedPassword === '') {
		return false;
	}

	if (password_verify($plainPassword, $storedPassword)) {
		return true;
	}

	return hash_equals($storedPassword, $plainPassword);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
	$user = requireRole($cnx, ['admin', 'enseignant', 'etudiant']);
	respondJson(200, ['success' => true, 'user' => $user]);
}

if ($method !== 'POST') {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

$action = strtolower((string) normalizeString(requestInput('action', 'login')));

if ($action === 'logout' || normalizeString(requestInput('logout')) !== null) {
	$user = requireRole($cnx, ['admin', 'enseignant', 'etudiant']);
	respondJson(200, [
		'success' => true,
		'message' => 'Logged out successfully',
		'user' => publicUserData($user),
	]);
}

$email = normalizeString(requestInput('email'));
$password = normalizeString(requestInput('password'));

if ($email === null || $password === null) {
	respondJson(400, ['success' => false, 'message' => 'email and password are required']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	respondJson(400, ['success' => false, 'message' => 'Invalid email address']);
}

$row = fetchAuthUserByEmail($cnx, $email);
if ($row === null) {
	respondJson(401, ['success' => false, 'message' => 'Invalid credentials']);
}

if (!passwordIsValid($password, (string) $row['password'])) {
	respondJson(401, ['success' => false, 'message' => 'Invalid credentials']);
}

$user = publicUserData($row);
$token = createAuthToken($user);

respondJson(200, [
	'success' => true,
	'token' => $token,
	'user' => $user,
]);

