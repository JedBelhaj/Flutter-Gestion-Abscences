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

requireRole($cnx, ['admin']);

function fetchClasseById(mysqli $cnx, int $classeId): ?array
{
	$stmt = mysqli_prepare($cnx, 'SELECT id, nom, niveau FROM classes WHERE id = ? LIMIT 1');
	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $classeId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
	$classeId = normalizeInt(requestInput('classe_id'));

	if ($classeId !== null) {
		$row = fetchClasseById($cnx, $classeId);
		if ($row === null) {
			respondJson(404, ['success' => false, 'message' => 'Class not found']);
		}

		respondJson(200, ['success' => true, 'data' => $row]);
	}

	$stmt = mysqli_prepare($cnx, 'SELECT id, nom, niveau FROM classes ORDER BY nom ASC');
	if (!$stmt) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	mysqli_stmt_execute($stmt);
	$rows = fetchAllAssoc($stmt);
	mysqli_stmt_close($stmt);

	respondJson(200, ['success' => true, 'data' => $rows]);
}

if ($method !== 'POST') {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

$classeId = normalizeInt(requestInput('classe_id'));
$nom = normalizeString(requestInput('nom'));
$niveau = normalizeString(requestInput('niveau'));

if ($classeId === null) {
	if ($nom === null) {
		respondJson(400, ['success' => false, 'message' => 'nom is required']);
	}

	$stmt = mysqli_prepare($cnx, 'INSERT INTO classes (nom, niveau) VALUES (?, ?)');
	if (!$stmt) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	mysqli_stmt_bind_param($stmt, 'ss', $nom, $niveau);
	mysqli_stmt_execute($stmt);
	$newId = mysqli_insert_id($cnx);
	mysqli_stmt_close($stmt);

	respondJson(201, [
		'success' => true,
		'message' => 'Class created successfully',
		'data' => [
			'id' => $newId,
			'nom' => $nom,
			'niveau' => $niveau,
		],
	]);
}

$existing = fetchClasseById($cnx, $classeId);
if ($existing === null) {
	respondJson(404, ['success' => false, 'message' => 'Class not found']);
}

$fields = [];
$types = '';
$params = [];

if ($nom !== null) {
	$fields[] = 'nom = ?';
	$types .= 's';
	$params[] = $nom;
}

if ($niveau !== null) {
	$fields[] = 'niveau = ?';
	$types .= 's';
	$params[] = $niveau;
}

if ($fields === []) {
	respondJson(400, ['success' => false, 'message' => 'No fields to update']);
}

$types .= 'i';
$params[] = $classeId;

$sql = 'UPDATE classes SET ' . implode(', ', $fields) . ' WHERE id = ?';
$stmt = mysqli_prepare($cnx, $sql);
if (!$stmt) {
	respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
}

bindStatementParams($stmt, $types, $params);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$updated = fetchClasseById($cnx, $classeId);

respondJson(200, [
	'success' => true,
	'message' => 'Class updated successfully',
	'data' => $updated,
]);

