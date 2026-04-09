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

$stmt = mysqli_prepare(
	$cnx,
	'SELECT e.id AS etudiant_id,
			u.id AS utilisateur_id,
			u.nom,
			u.prenom,
			u.email,
			c.id AS classe_id,
			c.nom AS classe_nom,
			c.niveau
	 FROM etudiants e
	 JOIN utilisateurs u ON u.id = e.utilisateur_id
	 JOIN classes c ON c.id = e.classe_id
	 WHERE e.id = ?
	 LIMIT 1'
);

if (!$stmt) {
	respond(500, ['success' => false, 'message' => 'Failed to prepare query']);
}

mysqli_stmt_bind_param($stmt, 'i', $etudiantId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$row) {
	respond(404, ['success' => false, 'message' => 'Etudiant not found']);
}

respond(200, ['success' => true, 'data' => $row]);

