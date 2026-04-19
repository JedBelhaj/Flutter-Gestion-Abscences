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

function fetchSeanceById(mysqli $cnx, int $seanceId): ?array
{
	$stmt = mysqli_prepare(
		$cnx,
		'SELECT s.id,
				s.enseignant_id,
				e.utilisateur_id AS enseignant_utilisateur_id,
				u.nom AS enseignant_nom,
				u.prenom AS enseignant_prenom,
				s.classe_id,
				c.nom AS classe_nom,
				s.matiere_id,
				m.nom AS matiere_nom,
				s.date_seance,
				s.heure_debut,
				s.heure_fin
		 FROM seances s
		 JOIN enseignants e ON e.id = s.enseignant_id
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 JOIN classes c ON c.id = s.classe_id
		 JOIN matieres m ON m.id = s.matiere_id
		 WHERE s.id = ?
		 LIMIT 1'
	);

	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $seanceId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

function publicSeanceRow(array $row): array
{
	return [
		'id' => (int) $row['id'],
		'enseignant_id' => (int) $row['enseignant_id'],
		'enseignant_utilisateur_id' => (int) $row['enseignant_utilisateur_id'],
		'enseignant_nom' => $row['enseignant_nom'],
		'enseignant_prenom' => $row['enseignant_prenom'],
		'classe_id' => (int) $row['classe_id'],
		'classe_nom' => $row['classe_nom'],
		'matiere_id' => (int) $row['matiere_id'],
		'matiere_nom' => $row['matiere_nom'],
		'date_seance' => $row['date_seance'],
		'heure_debut' => $row['heure_debut'],
		'heure_fin' => $row['heure_fin'],
	];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
	$enseignantId = normalizeInt(requestInput('enseignant_id'));
	$classeId = normalizeInt(requestInput('classe_id'));
	$date = validateDateOrNull(requestInput('date'));
	if ($date === null) {
		$date = validateDateOrNull(requestInput('date_seance'));
	}

	if ((requestInput('date') !== null && $date === null) || (requestInput('date_seance') !== null && $date === null)) {
		respondJson(400, ['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
	}

	$sql = 'SELECT s.id,
				s.enseignant_id,
				e.utilisateur_id AS enseignant_utilisateur_id,
				u.nom AS enseignant_nom,
				u.prenom AS enseignant_prenom,
				s.classe_id,
				c.nom AS classe_nom,
				s.matiere_id,
				m.nom AS matiere_nom,
				s.date_seance,
				s.heure_debut,
				s.heure_fin
		 FROM seances s
		 JOIN enseignants e ON e.id = s.enseignant_id
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 JOIN classes c ON c.id = s.classe_id
		 JOIN matieres m ON m.id = s.matiere_id
		 WHERE 1 = 1';
	$params = [];
	$types = '';

	if ($enseignantId !== null) {
		$sql .= ' AND s.enseignant_id = ?';
		$types .= 'i';
		$params[] = $enseignantId;
	}

	if ($classeId !== null) {
		$sql .= ' AND s.classe_id = ?';
		$types .= 'i';
		$params[] = $classeId;
	}

	if ($date !== null) {
		$sql .= ' AND s.date_seance = ?';
		$types .= 's';
		$params[] = $date;
	}

	$sql .= ' ORDER BY s.date_seance DESC, s.heure_debut ASC';

	$stmt = mysqli_prepare($cnx, $sql);
	if (!$stmt) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	if ($types !== '') {
		bindStatementParams($stmt, $types, $params);
	}

	mysqli_stmt_execute($stmt);
	$rows = fetchAllAssoc($stmt);
	mysqli_stmt_close($stmt);

	$data = [];
	foreach ($rows as $row) {
		$data[] = publicSeanceRow($row);
	}

	respondJson(200, ['success' => true, 'data' => $data]);
}

if (!in_array($method, ['POST', 'DELETE'], true)) {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

if ($method === 'DELETE') {
	$seanceId = normalizeInt(requestInput('seance_id'));
	if ($seanceId === null) {
		respondJson(400, ['success' => false, 'message' => 'seance_id is required']);
	}

	$existing = fetchSeanceById($cnx, $seanceId);
	if ($existing === null) {
		respondJson(404, ['success' => false, 'message' => 'Session not found']);
	}

	mysqli_begin_transaction($cnx);
	try {
		$stmtAbsences = mysqli_prepare($cnx, 'DELETE FROM absences WHERE seance_id = ?');
		if (!$stmtAbsences) {
			throw new RuntimeException('Failed to prepare absences delete');
		}
		mysqli_stmt_bind_param($stmtAbsences, 'i', $seanceId);
		if (!mysqli_stmt_execute($stmtAbsences)) {
			throw new RuntimeException('Failed to delete session absences');
		}
		mysqli_stmt_close($stmtAbsences);

		$stmtSeance = mysqli_prepare($cnx, 'DELETE FROM seances WHERE id = ?');
		if (!$stmtSeance) {
			throw new RuntimeException('Failed to prepare session delete');
		}
		mysqli_stmt_bind_param($stmtSeance, 'i', $seanceId);
		if (!mysqli_stmt_execute($stmtSeance)) {
			throw new RuntimeException('Failed to delete session');
		}
		mysqli_stmt_close($stmtSeance);

		mysqli_commit($cnx);
		respondJson(200, [
			'success' => true,
			'message' => 'Session deleted successfully',
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

$seanceId = normalizeInt(requestInput('seance_id'));
$enseignantId = normalizeInt(requestInput('enseignant_id'));
$classeId = normalizeInt(requestInput('classe_id'));
$matiereId = normalizeInt(requestInput('matiere_id'));
$dateSeance = validateDateOrNull(requestInput('date_seance'));
$heureDebut = validateTimeOrNull(requestInput('heure_debut'));
$heureFin = validateTimeOrNull(requestInput('heure_fin'));

if ($seanceId === null) {
	if ($enseignantId === null || $classeId === null || $matiereId === null || $dateSeance === null || $heureDebut === null || $heureFin === null) {
		respondJson(400, ['success' => false, 'message' => 'enseignant_id, classe_id, matiere_id, date_seance, heure_debut and heure_fin are required']);
	}

	if (!recordExists($cnx, 'enseignants', $enseignantId)) {
		respondJson(404, ['success' => false, 'message' => 'Teacher not found']);
	}
	if (!recordExists($cnx, 'classes', $classeId)) {
		respondJson(404, ['success' => false, 'message' => 'Class not found']);
	}
	if (!recordExists($cnx, 'matieres', $matiereId)) {
		respondJson(404, ['success' => false, 'message' => 'Subject not found']);
	}

	$stmt = mysqli_prepare(
		$cnx,
		'INSERT INTO seances (enseignant_id, classe_id, matiere_id, date_seance, heure_debut, heure_fin)
		 VALUES (?, ?, ?, ?, ?, ?)'
	);
	if (!$stmt) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	mysqli_stmt_bind_param($stmt, 'iiisss', $enseignantId, $classeId, $matiereId, $dateSeance, $heureDebut, $heureFin);
	mysqli_stmt_execute($stmt);
	$newId = mysqli_insert_id($cnx);
	mysqli_stmt_close($stmt);

	respondJson(201, [
		'success' => true,
		'message' => 'Session created successfully',
		'data' => ['seance_id' => $newId],
	]);
}

$existing = fetchSeanceById($cnx, $seanceId);
if ($existing === null) {
	respondJson(404, ['success' => false, 'message' => 'Session not found']);
}

$updates = [];
$types = '';
$params = [];

if ($enseignantId !== null) {
	if (!recordExists($cnx, 'enseignants', $enseignantId)) {
		respondJson(404, ['success' => false, 'message' => 'Teacher not found']);
	}
	$updates[] = 'enseignant_id = ?';
	$types .= 'i';
	$params[] = $enseignantId;
}

if ($classeId !== null) {
	if (!recordExists($cnx, 'classes', $classeId)) {
		respondJson(404, ['success' => false, 'message' => 'Class not found']);
	}
	$updates[] = 'classe_id = ?';
	$types .= 'i';
	$params[] = $classeId;
}

if ($matiereId !== null) {
	if (!recordExists($cnx, 'matieres', $matiereId)) {
		respondJson(404, ['success' => false, 'message' => 'Subject not found']);
	}
	$updates[] = 'matiere_id = ?';
	$types .= 'i';
	$params[] = $matiereId;
}

if ($dateSeance !== null) {
	$updates[] = 'date_seance = ?';
	$types .= 's';
	$params[] = $dateSeance;
}

if ($heureDebut !== null) {
	$updates[] = 'heure_debut = ?';
	$types .= 's';
	$params[] = $heureDebut;
}

if ($heureFin !== null) {
	$updates[] = 'heure_fin = ?';
	$types .= 's';
	$params[] = $heureFin;
}

if ($updates === []) {
	respondJson(400, ['success' => false, 'message' => 'No fields to update']);
}

$types .= 'i';
$params[] = $seanceId;

$sql = 'UPDATE seances SET ' . implode(', ', $updates) . ' WHERE id = ?';
$stmt = mysqli_prepare($cnx, $sql);
if (!$stmt) {
	respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
}

bindStatementParams($stmt, $types, $params);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

respondJson(200, [
	'success' => true,
	'message' => 'Session updated successfully',
]);

