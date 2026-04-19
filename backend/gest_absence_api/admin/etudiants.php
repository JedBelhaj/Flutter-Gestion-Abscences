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

function fetchEtudiantById(mysqli $cnx, int $etudiantId): ?array
{
	$stmt = mysqli_prepare(
		$cnx,
		'SELECT e.id AS etudiant_id,
				e.utilisateur_id,
				u.nom,
				u.prenom,
				u.email,
				e.classe_id,
				c.nom AS classe_nom,
				c.niveau
		 FROM etudiants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 JOIN classes c ON c.id = e.classe_id
		 WHERE e.id = ?
		 LIMIT 1'
	);

	if (!$stmt) {
		return null;
	}

	mysqli_stmt_bind_param($stmt, 'i', $etudiantId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$row = $result ? mysqli_fetch_assoc($result) : null;
	mysqli_stmt_close($stmt);

	return $row ?: null;
}

function fetchClasseByIdForStudent(mysqli $cnx, int $classeId): ?array
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

function emailExistsForStudent(mysqli $cnx, string $email, int $excludeUserId = 0): bool
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

function publicEtudiantRow(array $row): array
{
	return [
		'etudiant_id' => (int) $row['etudiant_id'],
		'utilisateur_id' => (int) $row['utilisateur_id'],
		'nom' => $row['nom'],
		'prenom' => $row['prenom'],
		'email' => $row['email'],
		'classe_id' => (int) $row['classe_id'],
		'classe_nom' => $row['classe_nom'],
		'niveau' => $row['niveau'],
	];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
	$classeId = normalizeInt(requestInput('classe_id'));
	$etudiantId = normalizeInt(requestInput('etudiant_id'));

	if ($etudiantId !== null) {
		$row = fetchEtudiantById($cnx, $etudiantId);
		if ($row === null) {
			respondJson(404, ['success' => false, 'message' => 'Student not found']);
		}

		respondJson(200, ['success' => true, 'data' => publicEtudiantRow($row)]);
	}

	$sql = 'SELECT e.id AS etudiant_id,
				e.utilisateur_id,
				u.nom,
				u.prenom,
				u.email,
				e.classe_id,
				c.nom AS classe_nom,
				c.niveau
		 FROM etudiants e
		 JOIN utilisateurs u ON u.id = e.utilisateur_id
		 JOIN classes c ON c.id = e.classe_id';
	$params = [];
	$types = '';

	if ($classeId !== null) {
		$sql .= ' WHERE e.classe_id = ?';
		$types = 'i';
		$params[] = $classeId;
	}

	$sql .= ' ORDER BY u.nom ASC, u.prenom ASC';
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
		$data[] = publicEtudiantRow($row);
	}

	respondJson(200, ['success' => true, 'data' => $data]);
}

if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
	respondJson(405, ['success' => false, 'message' => 'Method not allowed']);
}

if ($method === 'DELETE') {
	$etudiantId = normalizeInt(requestInput('etudiant_id'));
	if ($etudiantId === null) {
		respondJson(400, ['success' => false, 'message' => 'etudiant_id is required']);
	}

	$existing = fetchEtudiantById($cnx, $etudiantId);
	if ($existing === null) {
		respondJson(404, ['success' => false, 'message' => 'Student not found']);
	}

	$userId = (int) $existing['utilisateur_id'];

	mysqli_begin_transaction($cnx);
	try {
		$stmtAbsences = mysqli_prepare($cnx, 'DELETE FROM absences WHERE etudiant_id = ?');
		if (!$stmtAbsences) {
			throw new RuntimeException('Failed to prepare absences delete');
		}
		mysqli_stmt_bind_param($stmtAbsences, 'i', $etudiantId);
		if (!mysqli_stmt_execute($stmtAbsences)) {
			throw new RuntimeException('Failed to delete student absences');
		}
		mysqli_stmt_close($stmtAbsences);

		$stmtStudent = mysqli_prepare($cnx, 'DELETE FROM etudiants WHERE id = ?');
		if (!$stmtStudent) {
			throw new RuntimeException('Failed to prepare student delete');
		}
		mysqli_stmt_bind_param($stmtStudent, 'i', $etudiantId);
		if (!mysqli_stmt_execute($stmtStudent)) {
			throw new RuntimeException('Failed to delete student');
		}
		mysqli_stmt_close($stmtStudent);

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
			'message' => 'Student deleted successfully',
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

$etudiantId = normalizeInt(requestInput('etudiant_id'));
$nom = normalizeString(requestInput('nom'));
$prenom = normalizeString(requestInput('prenom'));
$email = normalizeString(requestInput('email'));
$password = normalizeString(requestInput('password'));
$classeId = normalizeInt(requestInput('classe_id'));

if ($etudiantId === null) {
	if ($nom === null || $prenom === null || $email === null || $password === null || $classeId === null) {
		respondJson(400, ['success' => false, 'message' => 'nom, prenom, email, password and classe_id are required']);
	}

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		respondJson(400, ['success' => false, 'message' => 'Invalid email address']);
	}

	if (emailExistsForStudent($cnx, $email)) {
		respondJson(409, ['success' => false, 'message' => 'Email already exists']);
	}

	if (!recordExists($cnx, 'classes', $classeId)) {
		respondJson(404, ['success' => false, 'message' => 'Class not found']);
	}

	mysqli_begin_transaction($cnx);
	try {
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
		$stmtUser = mysqli_prepare($cnx, 'INSERT INTO utilisateurs (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, "etudiant")');
		if (!$stmtUser) {
			throw new RuntimeException('Failed to prepare user insert');
		}

		mysqli_stmt_bind_param($stmtUser, 'ssss', $nom, $prenom, $email, $hashedPassword);
		mysqli_stmt_execute($stmtUser);
		$utilisateurId = mysqli_insert_id($cnx);
		mysqli_stmt_close($stmtUser);

		$stmtStudent = mysqli_prepare($cnx, 'INSERT INTO etudiants (utilisateur_id, classe_id) VALUES (?, ?)');
		if (!$stmtStudent) {
			throw new RuntimeException('Failed to prepare student insert');
		}

		mysqli_stmt_bind_param($stmtStudent, 'ii', $utilisateurId, $classeId);
		mysqli_stmt_execute($stmtStudent);
		$etudiantRowId = mysqli_insert_id($cnx);
		mysqli_stmt_close($stmtStudent);

		mysqli_commit($cnx);
		respondJson(201, [
			'success' => true,
			'message' => 'Student created successfully',
			'data' => [
				'etudiant_id' => $etudiantRowId,
				'utilisateur_id' => $utilisateurId,
			],
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}
}

$existing = fetchEtudiantById($cnx, $etudiantId);
if ($existing === null) {
	respondJson(404, ['success' => false, 'message' => 'Student not found']);
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

	if (emailExistsForStudent($cnx, $email, $userId)) {
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

$studentUpdates = [];
$studentTypes = '';
$studentParams = [];

if ($classeId !== null) {
	if (!recordExists($cnx, 'classes', $classeId)) {
		respondJson(404, ['success' => false, 'message' => 'Class not found']);
	}

	$studentUpdates[] = 'classe_id = ?';
	$studentTypes .= 'i';
	$studentParams[] = $classeId;
}

if ($userUpdates === [] && $studentUpdates === []) {
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

		if ($studentUpdates !== []) {
			$studentTypes .= 'i';
			$studentParams[] = $etudiantId;
			$sql = 'UPDATE etudiants SET ' . implode(', ', $studentUpdates) . ' WHERE id = ?';
			$stmtStudent = mysqli_prepare($cnx, $sql);
			if (!$stmtStudent) {
				throw new RuntimeException('Failed to prepare student update');
			}
			bindStatementParams($stmtStudent, $studentTypes, $studentParams);
			mysqli_stmt_execute($stmtStudent);
			mysqli_stmt_close($stmtStudent);
		}

		mysqli_commit($cnx);
		respondJson(200, [
			'success' => true,
			'message' => 'Student updated successfully',
		]);
	} catch (Throwable $e) {
		mysqli_rollback($cnx);
		respondJson(400, ['success' => false, 'message' => $e->getMessage()]);
	}

