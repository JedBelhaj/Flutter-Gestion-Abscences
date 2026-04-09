<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

require_once __DIR__ . '/../config/database.php';

if (!isset($cnx) || !$cnx) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Database connection failed']);
	exit;
}

mysqli_set_charset($cnx, 'utf8mb4');

function respond(int $status, array $payload): void
{
	http_response_code($status);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function getValidatedDate(?string $date): ?string
{
	if ($date === null || $date === '') {
		return null;
	}
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return null;
	}
	return $date;
}

function resolveEtudiantId(mysqli $cnx): ?int
{
	$etudiantId = isset($_GET['etudiant_id']) ? (int) $_GET['etudiant_id'] : 0;
	if ($etudiantId > 0) {
		return $etudiantId;
	}

	$utilisateurId = 0;
	if (isset($_GET['utilisateur_id'])) {
		$utilisateurId = (int) $_GET['utilisateur_id'];
	} elseif (isset($_GET['token'])) {
		// Temporary token strategy: token is utilisateur_id.
		$utilisateurId = (int) $_GET['token'];
	}

	if ($utilisateurId <= 0) {
		return null;
	}

	$stmt = mysqli_prepare(
		$cnx,
		'SELECT e.id
		 FROM etudiants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 WHERE e.utilisateur_id = ? AND u.role = "etudiant"
		 LIMIT 1'
	);
	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $utilisateurId);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	$row = $res ? mysqli_fetch_assoc($res) : null;
	mysqli_stmt_close($stmt);

	return $row ? (int) $row['id'] : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	respond(405, ['success' => false, 'message' => 'Method not allowed']);
}

$etudiantId = resolveEtudiantId($cnx);
if ($etudiantId === null) {
	respond(400, ['success' => false, 'message' => 'Missing or invalid etudiant identity']);
}

$dateFrom = getValidatedDate($_GET['date_from'] ?? null);
$dateTo = getValidatedDate($_GET['date_to'] ?? null);
if ((isset($_GET['date_from']) && $dateFrom === null) || (isset($_GET['date_to']) && $dateTo === null)) {
	respond(400, ['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
}

$matiereId = isset($_GET['matiere_id']) ? (int) $_GET['matiere_id'] : 0;

$sql = 'SELECT a.id AS absence_id,
			   a.seance_id,
			   s.date_seance,
			   s.heure_debut,
			   s.heure_fin,
			   m.id AS matiere_id,
			   m.nom AS matiere_nom,
			   a.statut
		FROM absences a
		JOIN seances s ON s.id = a.seance_id
		JOIN matieres m ON m.id = s.matiere_id
		WHERE a.etudiant_id = ?';

$types = 'i';
$params = [$etudiantId];

if ($dateFrom !== null) {
	$sql .= ' AND s.date_seance >= ?';
	$types .= 's';
	$params[] = $dateFrom;
}
if ($dateTo !== null) {
	$sql .= ' AND s.date_seance <= ?';
	$types .= 's';
	$params[] = $dateTo;
}
if ($matiereId > 0) {
	$sql .= ' AND s.matiere_id = ?';
	$types .= 'i';
	$params[] = $matiereId;
}

$sql .= ' ORDER BY s.date_seance DESC, s.heure_debut DESC';

$stmt = mysqli_prepare($cnx, $sql);
if (!$stmt) {
	respond(500, ['success' => false, 'message' => 'Failed to prepare query']);
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
	$rows[] = $row;
}

mysqli_stmt_close($stmt);

respond(200, ['success' => true, 'data' => $rows]);


