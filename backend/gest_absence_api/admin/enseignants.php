<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

apiCors(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
	respondJson(204, []);
}

if (!isset($cnx) || !$cnx) {
	respondJson(500, ['success' => false, 'message' => 'Database connection failed']);
}

requireRole($cnx, ['admin']);

function fetchEnseignantById(mysqli $cnx, int $enseignantId): ?array
{
	$stmt = mysqli_prepare(
		$cnx,
		'SELECT e.id AS enseignant_id,
				e.utilisateur_id,
				u.nom,
				u.prenom,
				u.email,
				e.specialite
		 FROM enseignants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 WHERE e.id = ?
		 LIMIT 1'
	);

	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $enseignantId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

function emailExistsForUser(mysqli $cnx, string $email, int $excludeUserId = 0): bool
{
	$sql = 'SELECT id FROM utilisateurs WHERE email = ?';
	$params = [$email];
	$types = 's';
	if ($excludeUserId > 0) {
		$sql .= ' AND id <> ?';
		$types .= 'i';
		$params[] = $excludeUserId;
	}
	$sql .= ' LIMIT 1';

	$stmt = mysqli_prepare($cnx, $sql);
	if (!$stmt) {
		return false;
	}

	bindStatementParams($stmt, $types, $params);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$exists = $result instanceof mysqli_result && mysqli_num_rows($result) > 0;
	if ($result instanceof mysqli_result) {
		mysqli_free_result($result);
	}
	mysqli_stmt_close($stmt);

	return $exists;
}

function publicEnseignantRow(array $row): array
{
	return [
		'enseignant_id' => (int) $row['enseignant_id'],
		'utilisateur_id' => (int) $row['utilisateur_id'],
		'nom' => $row['nom'],
		'prenom' => $row['prenom'],
		'email' => $row['email'],
		'specialite' => $row['specialite'],
	];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
	$enseignantId = normalizeInt(requestInput('enseignant_id'));

	if ($enseignantId !== null) {
		$row = fetchEnseignantById($cnx, $enseignantId);
		if ($row === null) {
			respondJson(404, ['success' => false, 'message' => 'Teacher not found']);
		}

		respondJson(200, ['success' => true, 'data' => publicEnseignantRow($row)]);
	}

	$stmt = mysqli_prepare(
		$cnx,
		'SELECT e.id AS enseignant_id,
				e.utilisateur_id,
				u.nom,
				u.prenom,
				u.email,
				e.specialite
		 FROM enseignants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 ORDER BY u.nom ASC, u.prenom ASC'
	);
	if (!$stmt) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}

	mysqli_stmt_execute($stmt);
	$rows = fetchAllAssoc($stmt);
	mysqli_stmt_close($stmt);

	$data = [];
	foreach ($rows as $row) {
		$data[] = publicEnseignantRow($row);
	}

	respondJson(200, ['success' => true, 'data' => $data]);
}

if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

if ($method === 'DELETE') {
	$enseignantId = normalizeInt(requestInput('enseignant_id'));
	if ($enseignantId === null) {
		respondJson(400, ['success' => false, 'message' => 'enseignant_id is required']);
	}

	$existing = fetchEnseignantById($cnx, $enseignantId);
	if ($existing === null) {
		respondJson(404, ['success' => false, 'message' => 'Teacher not found']);
	}

	$stmtSeanceCheck = mysqli_prepare($cnx, 'SELECT id FROM seances WHERE enseignant_id = ? LIMIT 1');
	if (!$stmtSeanceCheck) {
		respondJson(500, ['success' => false, 'message' => 'Failed to prepare query']);
	}
	mysqli_stmt_bind_param($stmtSeanceCheck, 'i', $enseignantId);
	mysqli_stmt_execute($stmtSeanceCheck);
	$seanceResult = mysqli_stmt_get_result($stmtSeanceCheck);
	$hasSeances = $seanceResult instanceof mysqli_result && mysqli_num_rows($seanceResult) > 0;
	if ($seanceResult instanceof mysqli_result) {
		mysqli_free_result($seanceResult);
	}
	mysqli_stmt_close($stmtSeanceCheck);

	if ($hasSeances) {
		respondJson(409, [
			'success' => false,
			'message' => 'Cannot delete teacher assigned to sessions',
		]);
	}

	$userId = (int) $existing['utilisateur_id'];

	mysqli_begin_transaction($cnx);
	try {
		$stmtTeacher = mysqli_prepare($cnx, 'DELETE FROM enseignants WHERE id = ?');
		if (!$stmtTeacher) {
			throw new RuntimeException('Failed to prepare teacher delete');
		}
		mysqli_stmt_bind_param($stmtTeacher, 'i', $enseignantId);
		if (!mysqli_stmt_execute($stmtTeacher)) {
			throw new RuntimeException('Failed to delete teacher');
		}
		mysqli_stmt_close($stmtTeacher);

		$stmtUser = mysqli_prepare($cnx, 'DELETE FROM utilisateurs WHERE id = ?');
		if (!$stmtUser) {
			throw new RuntimeException('Failed to prepare user delete');
		}
		mysqli_stmt_bind_param($stmtUser, 'i', $userId);
		if (!mysqli_stmt_execute($stmtUser)) {
			throw new RuntimeException('Failed to delete user');
		}
		mysqli_stmt_close($stmtUser);

		mysqli_commit($cnx);
		respondJson(200, [
			'success' => true,
			'message' => 'Teacher deleted successfully',
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

$enseignantId = normalizeInt(requestInput('enseignant_id'));
$nom = normalizeString(requestInput('nom'));
$prenom = normalizeString(requestInput('prenom'));
$email = normalizeString(requestInput('email'));
$password = normalizeString(requestInput('password'));
$specialite = normalizeString(requestInput('specialite'));

if ($enseignantId === null) {
	if ($nom === null || $prenom === null || $email === null || $password === null) {
		respondJson(400, ['success' => false, 'message' => 'nom, prenom, email and password are required']);
	}

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		respondJson(400, ['success' => false, 'message' => 'Invalid email address']);
	}

	if (emailExistsForUser($cnx, $email)) {
		respondJson(409, ['success' => false, 'message' => 'Email already exists']);
	}

	mysqli_begin_transaction($cnx);
	try {
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
		$stmtUser = mysqli_prepare($cnx, 'INSERT INTO utilisateurs (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, "enseignant")');
		if (!$stmtUser) {
			throw new RuntimeException('Failed to prepare user insert');
		}

		mysqli_stmt_bind_param($stmtUser, 'ssss', $nom, $prenom, $email, $hashedPassword);
		mysqli_stmt_execute($stmtUser);
		$utilisateurId = mysqli_insert_id($cnx);
		mysqli_stmt_close($stmtUser);

		$stmtTeacher = mysqli_prepare($cnx, 'INSERT INTO enseignants (utilisateur_id, specialite) VALUES (?, ?)');
		if (!$stmtTeacher) {
			throw new RuntimeException('Failed to prepare teacher insert');
		}

		mysqli_stmt_bind_param($stmtTeacher, 'is', $utilisateurId, $specialite);
		mysqli_stmt_execute($stmtTeacher);
		$enseignantRowId = mysqli_insert_id($cnx);
		mysqli_stmt_close($stmtTeacher);

		mysqli_commit($cnx);
		respondJson(201, [
			'success' => true,
			'message' => 'Teacher created successfully',
			'data' => [
				'enseignant_id' => $enseignantRowId,
				'utilisateur_id' => $utilisateurId,
			],
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

$existing = fetchEnseignantById($cnx, $enseignantId);
if ($existing === null) {
	respondJson(404, ['success' => false, 'message' => 'Teacher not found']);
}

$userId = (int) $existing['utilisateur_id'];
$userUpdates = [];
$userTypes = '';
$userParams = [];

if ($nom !== null) {
	$userUpdates[] = 'nom = ?';
	$userTypes .= 's';
	$userParams[] = $nom;
}

if ($prenom !== null) {
	$userUpdates[] = 'prenom = ?';
	$userTypes .= 's';
	$userParams[] = $prenom;
}

if ($email !== null) {
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		respondJson(400, ['success' => false, 'message' => 'Invalid email address']);
	}

	if (emailExistsForUser($cnx, $email, $userId)) {
		respondJson(409, ['success' => false, 'message' => 'Email already exists']);
	}

	$userUpdates[] = 'email = ?';
	$userTypes .= 's';
	$userParams[] = $email;
}

if ($password !== null) {
	$userUpdates[] = 'password = ?';
	$userTypes .= 's';
	$userParams[] = password_hash($password, PASSWORD_DEFAULT);
}

$teacherUpdates = [];
$teacherTypes = '';
$teacherParams = [];

if ($specialite !== null) {
	$teacherUpdates[] = 'specialite = ?';
	$teacherTypes .= 's';
	$teacherParams[] = $specialite;
}

if ($userUpdates === [] && $teacherUpdates === []) {
	respondJson(400, ['success' => false, 'message' => 'No fields to update']);
}

mysqli_begin_transaction($cnx);
	try {
		if ($userUpdates !== []) {
			$userTypes .= 'i';
			$userParams[] = $userId;
			$sql = 'UPDATE utilisateurs SET ' . implode(', ', $userUpdates) . ' WHERE id = ?';
			$stmtUser = mysqli_prepare($cnx, $sql);
			if (!$stmtUser) {
				throw new RuntimeException('Failed to prepare user update');
			}
			bindStatementParams($stmtUser, $userTypes, $userParams);
			mysqli_stmt_execute($stmtUser);
			mysqli_stmt_close($stmtUser);
		}

		if ($teacherUpdates !== []) {
			$teacherTypes .= 'i';
			$teacherParams[] = $enseignantId;
			$sql = 'UPDATE enseignants SET ' . implode(', ', $teacherUpdates) . ' WHERE id = ?';
			$stmtTeacher = mysqli_prepare($cnx, $sql);
			if (!$stmtTeacher) {
				throw new RuntimeException('Failed to prepare teacher update');
			}
			bindStatementParams($stmtTeacher, $teacherTypes, $teacherParams);
			mysqli_stmt_execute($stmtTeacher);
			mysqli_stmt_close($stmtTeacher);
		}

		mysqli_commit($cnx);
		respondJson(200, [
			'success' => true,
			'message' => 'Teacher updated successfully',
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}

