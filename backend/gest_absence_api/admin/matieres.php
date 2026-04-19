<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

apiCors(['GET', 'OPTIONS']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
	respondJson(204, []);
}

if (!isset($cnx) || !$cnx) {
	respondJson(500, ['success' => false, 'message' => 'Database connection failed']);
}

requireRole($cnx, ['admin']);

function fetchMatiereById(mysqli $cnx, int $matiereId): ?array
{
	$stmt = mysqli_prepare($cnx, 'SELECT id, nom FROM matieres WHERE id = ? LIMIT 1');
	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $matiereId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

$matiereId = normalizeInt(requestInput('matiere_id'));
if ($matiereId !== null) {
	$row = fetchMatiereById($cnx, $matiereId);
	if ($row === null) {
		respondJson(404, ['success' => false, 'message' => 'Subject not found']);
	}

	respondJson(200, ['success' => true, 'data' => $row]);
}

$stmt = mysqli_prepare($cnx, 'SELECT id, nom FROM matieres ORDER BY nom ASC');
if (!$stmt) {
	respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
}

mysqli_stmt_execute($stmt);
$rows = fetchAllAssoc($stmt);
mysqli_stmt_close($stmt);

respondJson(200, ['success' => true, 'data' => $rows]);
