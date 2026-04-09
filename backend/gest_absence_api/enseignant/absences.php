<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

function parseJsonBody(): array
{
	$raw = file_get_contents('php://input');
	if ($raw === false || trim($raw) === '') {
		return [];
	}
	$decoded = json_decode($raw, true);
	return is_array($decoded) ? $decoded : [];
}

function resolveEnseignantId(mysqli $cnx, array $payload = []): ?int
{
	$enseignantId = isset($payload['enseignant_id']) ? (int) $payload['enseignant_id'] : 0;
	if ($enseignantId <= 0 && isset($_REQUEST['enseignant_id'])) {
		$enseignantId = (int) $_REQUEST['enseignant_id'];
	}
	if ($enseignantId > 0) {
		return $enseignantId;
	}

	$utilisateurId = 0;
	if (isset($payload['utilisateur_id'])) {
		$utilisateurId = (int) $payload['utilisateur_id'];
	} elseif (isset($payload['token'])) {
		// Temporary token strategy: token is utilisateur_id.
		$utilisateurId = (int) $payload['token'];
	} elseif (isset($_REQUEST['utilisateur_id'])) {
		$utilisateurId = (int) $_REQUEST['utilisateur_id'];
	} elseif (isset($_REQUEST['token'])) {
		$utilisateurId = (int) $_REQUEST['token'];
	}

	if ($utilisateurId <= 0) {
		return null;
	}

	$stmt = mysqli_prepare(
		$cnx,
		'SELECT e.id
		 FROM enseignants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 WHERE e.utilisateur_id = ? AND u.role = "enseignant"
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

function assertSeanceOwner(mysqli $cnx, int $seanceId, int $enseignantId): bool
{
	$stmt = mysqli_prepare($cnx, 'SELECT id FROM seances WHERE id = ? AND enseignant_id = ? LIMIT 1');
	if (!$stmt) {
		return false;
	}

	mysqli_stmt_bind_param($stmt, 'ii', $seanceId, $enseignantId);
	mysqli_stmt_execute($stmt);
	$res = mysqli_stmt_get_result($stmt);
	$ok = $res && mysqli_num_rows($res) > 0;
	mysqli_stmt_close($stmt);

	return $ok;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$seanceId = isset($_GET['seance_id']) ? (int) $_GET['seance_id'] : 0;
	if ($seanceId <= 0) {
		respond(400, ['success' => false, 'message' => 'seance_id is required']);
	}

	$enseignantId = resolveEnseignantId($cnx);
	if ($enseignantId === null) {
		respond(400, ['success' => false, 'message' => 'Missing or invalid enseignant identity']);
	}

	if (!assertSeanceOwner($cnx, $seanceId, $enseignantId)) {
		respond(403, ['success' => false, 'message' => 'You are not allowed to access this seance']);
	}

	$stmt = mysqli_prepare(
		$cnx,
		'SELECT et.id AS etudiant_id,
				u.nom,
				u.prenom,
				COALESCE(a.statut, "present") AS statut
		 FROM seances s
		 JOIN etudiants et ON et.classe_id = s.classe_id
		 JOIN utilisateurs u ON u.id = et.utilisateur_id
		 LEFT JOIN absences a ON a.seance_id = s.id AND a.etudiant_id = et.id
		 WHERE s.id = ?
		 ORDER BY u.nom ASC, u.prenom ASC'
	);

	if (!$stmt) {
		respond(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	mysqli_stmt_bind_param($stmt, 'i', $seanceId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	$rows = [];
	while ($row = mysqli_fetch_assoc($result)) {
		$rows[] = $row;
	}

	mysqli_stmt_close($stmt);
	respond(200, ['success' => true, 'data' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$payload = parseJsonBody();
	if (!$payload) {
		$payload = $_POST;
	}

	$seanceId = isset($payload['seance_id']) ? (int) $payload['seance_id'] : 0;
	$absences = $payload['absences'] ?? null;

	if ($seanceId <= 0 || !is_array($absences)) {
		respond(400, ['success' => false, 'message' => 'seance_id and absences[] are required']);
	}

	$enseignantId = resolveEnseignantId($cnx, $payload);
	if ($enseignantId === null) {
		respond(400, ['success' => false, 'message' => 'Missing or invalid enseignant identity']);
	}

	if (!assertSeanceOwner($cnx, $seanceId, $enseignantId)) {
		respond(403, ['success' => false, 'message' => 'You are not allowed to update this seance']);
	}

	mysqli_begin_transaction($cnx);

	try {
		$stmt = mysqli_prepare(
			$cnx,
			'INSERT INTO absences (seance_id, etudiant_id, statut)
			 VALUES (?, ?, ?)
			 ON DUPLICATE KEY UPDATE statut = VALUES(statut)'
		);

		if (!$stmt) {
			throw new RuntimeException('Failed to prepare attendance query');
		}

		$affectedRows = 0;
		foreach ($absences as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			$etudiantId = isset($entry['etudiant_id']) ? (int) $entry['etudiant_id'] : 0;
			$statut = (string) ($entry['statut'] ?? 'present');

			if ($etudiantId <= 0 || !in_array($statut, ['present', 'absent'], true)) {
				throw new InvalidArgumentException('Invalid absences payload');
			}

			mysqli_stmt_bind_param($stmt, 'iis', $seanceId, $etudiantId, $statut);
			mysqli_stmt_execute($stmt);
			$affectedRows += mysqli_stmt_affected_rows($stmt);
		}

		mysqli_stmt_close($stmt);
		mysqli_commit($cnx);

		respond(200, [
			'success' => true,
			'message' => 'Attendance saved successfully',
			'affected_rows' => $affectedRows,
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respond(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

respond(405, ['success' => false, 'message' => 'Method not allowed']);

