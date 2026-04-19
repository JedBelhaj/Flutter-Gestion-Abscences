<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

apiCors(['GET', 'POST', 'DELETE', 'OPTIONS']);

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

if (!in_array($method, ['POST', 'DELETE'], true)) {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

if ($method === 'DELETE') {
	$classeId = normalizeInt(requestInput('classe_id'));
	if ($classeId === null) {
		respondJson(400, ['success' => false, 'message' => 'classe_id is required']);
	}

	$existing = fetchClasseById($cnx, $classeId);
	if ($existing === null) {
		respondJson(404, ['success' => false, 'message' => 'Class not found']);
	}

	$stmtStudentCheck = mysqli_prepare($cnx, 'SELECT id FROM etudiants WHERE classe_id = ? LIMIT 1');
	if (!$stmtStudentCheck) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}
	mysqli_stmt_bind_param($stmtStudentCheck, 'i', $classeId);
	mysqli_stmt_execute($stmtStudentCheck);
	$studentResult = mysqli_stmt_get_result($stmtStudentCheck);
	$hasStudents = $studentResult instanceof mysqli_result && mysqli_num_rows($studentResult) > 0;
	if ($studentResult instanceof mysqli_result) {
		mysqli_free_result($studentResult);
	}
	mysqli_stmt_close($stmtStudentCheck);

	if ($hasStudents) {
		respondJson(409, ['success' => false, 'message' => 'Cannot delete class containing students']);
	}

	$stmtSeanceCheck = mysqli_prepare($cnx, 'SELECT id FROM seances WHERE classe_id = ? LIMIT 1');
	if (!$stmtSeanceCheck) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}
	mysqli_stmt_bind_param($stmtSeanceCheck, 'i', $classeId);
	mysqli_stmt_execute($stmtSeanceCheck);
	$seanceResult = mysqli_stmt_get_result($stmtSeanceCheck);
	$hasSeances = $seanceResult instanceof mysqli_result && mysqli_num_rows($seanceResult) > 0;
	if ($seanceResult instanceof mysqli_result) {
		mysqli_free_result($seanceResult);
	}
	mysqli_stmt_close($stmtSeanceCheck);

	if ($hasSeances) {
		respondJson(409, ['success' => false, 'message' => 'Cannot delete class assigned to sessions']);
	}

	$stmtDelete = mysqli_prepare($cnx, 'DELETE FROM classes WHERE id = ?');
	if (!$stmtDelete) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}
	mysqli_stmt_bind_param($stmtDelete, 'i', $classeId);
	if (!mysqli_stmt_execute($stmtDelete)) {
		mysqli_stmt_close($stmtDelete);
		respondJson(400, ['success' => false, 'message' => 'Failed to delete class']);
	}
	mysqli_stmt_close($stmtDelete);

	respondJson(200, [
		'success' => true,
		'message' => 'Class deleted successfully',
	]);
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

