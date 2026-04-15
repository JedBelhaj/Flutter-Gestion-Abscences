<?php
declare(strict_types=1);

$base = '/backend/gest_absence_api';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>gest_absence_api test page</title>
	<style>
		body { font-family: Segoe UI, Tahoma, sans-serif; margin: 24px; background: #f6f8fb; color: #1f2937; }
		h1 { margin-top: 0; }
		.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
		.card { background: #fff; border: 1px solid #d1d5db; border-radius: 10px; padding: 14px; }
		label { display: block; font-size: 13px; margin: 8px 0 4px; color: #4b5563; }
		input, textarea, button { width: 100%; box-sizing: border-box; }
		input, textarea { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; font-size: 14px; }
		button { margin-top: 10px; border: 0; border-radius: 8px; padding: 10px; background: #1d4ed8; color: #fff; cursor: pointer; }
		button:hover { background: #1e40af; }
		.row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
		pre { background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: 8px; overflow: auto; min-height: 100px; }
		code { font-family: Consolas, monospace; }
		.hint { font-size: 12px; color: #64748b; }
	</style>
</head>
<body>
	<h1>gest_absence_api - quick test page</h1>
	<p class="hint">No auth mode: use enseignant_id and etudiant_id directly.</p>
	<p class="hint">
		Interactive docs: <a href="./docs/" target="_blank" rel="noopener">Swagger UI</a>
		| Spec file: <a href="./docs/openapi.json" target="_blank" rel="noopener">openapi.json</a>
	</p>

	<div class="grid">
		<section class="card">
			<h3>Enseignant - seances (GET)</h3>
			<label>enseignant_id</label>
			<input id="es_enseignant" type="number" value="1">
			<div class="row">
				<div>
					<label>date_from (optional)</label>
					<input id="es_from" type="date">
				</div>
				<div>
					<label>date_to (optional)</label>
					<input id="es_to" type="date">
				</div>
			</div>
			<button onclick="testEnseignantSeances()">Run</button>
			<pre id="out_es"></pre>
		</section>

		<section class="card">
			<h3>Enseignant - absences list (GET)</h3>
			<label>enseignant_id</label>
			<input id="ea_enseignant" type="number" value="1">
			<label>seance_id</label>
			<input id="ea_seance" type="number" value="1">
			<button onclick="testEnseignantAbsencesGet()">Run</button>
			<pre id="out_ea_get"></pre>
		</section>

		<section class="card">
			<h3>Enseignant - save attendance (POST)</h3>
			<label>enseignant_id</label>
			<input id="ep_enseignant" type="number" value="1">
			<label>seance_id</label>
			<input id="ep_seance" type="number" value="1">
			<label>absences JSON array</label>
			<textarea id="ep_absences" rows="6">[{"etudiant_id":1,"statut":"absent"}]</textarea>
			<button onclick="testEnseignantAbsencesPost()">Run</button>
			<pre id="out_ea_post"></pre>
		</section>

		<section class="card">
			<h3>Etudiant - profil (GET)</h3>
			<label>etudiant_id</label>
			<input id="sp_etudiant" type="number" value="1">
			<button onclick="testEtudiantProfil()">Run</button>
			<pre id="out_sp"></pre>
		</section>

		<section class="card">
			<h3>Etudiant - absences (GET)</h3>
			<label>etudiant_id</label>
			<input id="sa_etudiant" type="number" value="1">
			<div class="row">
				<div>
					<label>date_from (optional)</label>
					<input id="sa_from" type="date">
				</div>
				<div>
					<label>date_to (optional)</label>
					<input id="sa_to" type="date">
				</div>
			</div>
			<label>matiere_id (optional)</label>
			<input id="sa_matiere" type="number">
			<button onclick="testEtudiantAbsences()">Run</button>
			<pre id="out_sa"></pre>
		</section>

		<section class="card">
			<h3>Auth - Login (POST)</h3>
			<label>Email</label>
			<input id="login_email" type="email" value="admin@school.tn">
			<label>Password</label>
			<input id="login_password" type="password" value="admin123">
			<button onclick="testLogin()">Login</button>
			<label>Token (paste here if you already logged in)</label>
			<input id="token_input" type="text" placeholder="Paste token here">
			<button onclick="useTokenFromInput()">Use Token</button>
			<div style="margin-top: 10px; padding: 10px; background: #f3f4f6; border-radius: 6px; word-wrap: break-word;">
				<span style="font-size: 12px; color: #666;">Token: <span id="token_display">None</span></span>
			</div>
			<pre id="out_login"></pre>
		</section>

		<section class="card">
			<h3>Admin - Classes List (GET)</h3>
			<button onclick="testAdminListClasses()">List All Classes</button>
			<pre id="out_classes_list"></pre>
		</section>

		<section class="card">
			<h3>Admin - Create Class (POST)</h3>
			<label>Class Name</label>
			<input id="create_class_nom" type="text" placeholder="e.g., CI2-B">
			<label>Level</label>
			<input id="create_class_niveau" type="text" placeholder="e.g., Cycle Ingenieur 2">
			<button onclick="testAdminCreateClass()">Create Class</button>
			<pre id="out_create_class"></pre>
		</section>

		<section class="card">
			<h3>Admin - Update Class (POST)</h3>
			<label>Class ID</label>
			<input id="update_class_id" type="number" value="1">
			<label>New Name (optional)</label>
			<input id="update_class_nom" type="text">
			<label>New Level (optional)</label>
			<input id="update_class_niveau" type="text">
			<button onclick="testAdminUpdateClass()">Update Class</button>
			<pre id="out_update_class"></pre>
		</section>

		<section class="card">
			<h3>Admin - Teachers List (GET)</h3>
			<button onclick="testAdminListTeachers()">List All Teachers</button>
			<pre id="out_teachers_list"></pre>
		</section>

		<section class="card">
			<h3>Admin - Create Teacher (POST)</h3>
			<label>First Name</label>
			<input id="create_teacher_nom" type="text" placeholder="e.g., Ben Ali">
			<label>Last Name</label>
			<input id="create_teacher_prenom" type="text" placeholder="e.g., Sami">
			<label>Email</label>
			<input id="create_teacher_email" type="email" placeholder="e.g., teacher@school.tn">
			<label>Password</label>
			<input id="create_teacher_password" type="password" value="prof123">
			<label>Speciality (optional)</label>
			<input id="create_teacher_specialite" type="text" placeholder="e.g., Informatique">
			<button onclick="testAdminCreateTeacher()">Create Teacher</button>
			<pre id="out_create_teacher"></pre>
		</section>

		<section class="card">
			<h3>Admin - Update Teacher (POST)</h3>
			<label>Teacher ID</label>
			<input id="update_teacher_id" type="number" value="1">
			<label>First Name (optional)</label>
			<input id="update_teacher_nom" type="text">
			<label>Last Name (optional)</label>
			<input id="update_teacher_prenom" type="text">
			<label>Email (optional)</label>
			<input id="update_teacher_email" type="email">
			<label>Password (optional)</label>
			<input id="update_teacher_password" type="password">
			<label>Speciality (optional)</label>
			<input id="update_teacher_specialite" type="text">
			<button onclick="testAdminUpdateTeacher()">Update Teacher</button>
			<pre id="out_update_teacher"></pre>
		</section>

		<section class="card">
			<h3>Admin - Students List (GET)</h3>
			<label>Class ID (optional)</label>
			<input id="list_students_class" type="number">
			<button onclick="testAdminListStudents()">List Students</button>
			<pre id="out_students_list"></pre>
		</section>

		<section class="card">
			<h3>Admin - Create Student (POST)</h3>
			<label>First Name</label>
			<input id="create_student_nom" type="text" placeholder="e.g., Trabelsi">
			<label>Last Name</label>
			<input id="create_student_prenom" type="text" placeholder="e.g., Amine">
			<label>Email</label>
			<input id="create_student_email" type="email" placeholder="e.g., student@school.tn">
			<label>Password</label>
			<input id="create_student_password" type="password" value="etu123">
			<label>Class ID</label>
			<input id="create_student_classe_id" type="number" value="1">
			<button onclick="testAdminCreateStudent()">Create Student</button>
			<pre id="out_create_student"></pre>
		</section>

		<section class="card">
			<h3>Admin - Update Student (POST)</h3>
			<label>Student ID</label>
			<input id="update_student_id" type="number" value="1">
			<label>First Name (optional)</label>
			<input id="update_student_nom" type="text">
			<label>Last Name (optional)</label>
			<input id="update_student_prenom" type="text">
			<label>Email (optional)</label>
			<input id="update_student_email" type="email">
			<label>Password (optional)</label>
			<input id="update_student_password" type="password">
			<label>Class ID (optional)</label>
			<input id="update_student_classe_id" type="number">
			<button onclick="testAdminUpdateStudent()">Update Student</button>
			<pre id="out_update_student"></pre>
		</section>

		<section class="card">
			<h3>Admin - Sessions List (GET)</h3>
			<label>Teacher ID (optional)</label>
			<input id="list_sessions_teacher" type="number">
			<label>Class ID (optional)</label>
			<input id="list_sessions_class" type="number">
			<label>Date (optional)</label>
			<input id="list_sessions_date" type="date">
			<button onclick="testAdminListSessions()">List Sessions</button>
			<pre id="out_sessions_list"></pre>
		</section>

		<section class="card">
			<h3>Admin - Create Session (POST)</h3>
			<label>Teacher ID</label>
			<input id="create_session_teacher" type="number" value="1">
			<label>Class ID</label>
			<input id="create_session_class" type="number" value="1">
			<label>Subject ID</label>
			<input id="create_session_matiere" type="number" value="1">
			<label>Date</label>
			<input id="create_session_date" type="date" value="2026-04-12">
			<label>Start Time (HH:MM)</label>
			<input id="create_session_start" type="text" placeholder="09:00" value="09:00">
			<label>End Time (HH:MM)</label>
			<input id="create_session_end" type="text" placeholder="10:30" value="10:30">
			<button onclick="testAdminCreateSession()">Create Session</button>
			<pre id="out_create_session"></pre>
		</section>

		<section class="card">
			<h3>Admin - Update Session (POST)</h3>
			<label>Session ID</label>
			<input id="update_session_id" type="number" value="1">
			<label>Teacher ID (optional)</label>
			<input id="update_session_teacher" type="number">
			<label>Class ID (optional)</label>
			<input id="update_session_class" type="number">
			<label>Subject ID (optional)</label>
			<input id="update_session_matiere" type="number">
			<label>Date (optional)</label>
			<input id="update_session_date" type="date">
			<label>Start Time (optional)</label>
			<input id="update_session_start" type="text" placeholder="HH:MM">
			<label>End Time (optional)</label>
			<input id="update_session_end" type="text" placeholder="HH:MM">
			<button onclick="testAdminUpdateSession()">Update Session</button>
			<pre id="out_update_session"></pre>
		</section>
	</div>

	<script>
		const base = <?php echo json_encode($base, JSON_UNESCAPED_SLASHES); ?>;

		function out(id, data) {
			document.getElementById(id).textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
		}

		function buildUrl(path, params) {
			const url = new URL(base + path, window.location.origin);
			Object.entries(params).forEach(([k, v]) => {
				if (v !== '' && v !== null && v !== undefined) {
					url.searchParams.set(k, v);
				}
			});
			return url.toString();
		}

		async function fetchJson(url, options = {}) {
			const res = await fetch(url, options);
			let body;
			try {
				body = await res.json();
			} catch (e) {
				body = await res.text();
			}
			return { status: res.status, body };
		}

		async function testEnseignantSeances() {
			const url = buildUrl('/enseignant/seances.php', {
				enseignant_id: document.getElementById('es_enseignant').value,
				date_from: document.getElementById('es_from').value,
				date_to: document.getElementById('es_to').value,
			});
			out('out_es', await fetchJson(url));
		}

		async function testEnseignantAbsencesGet() {
			const url = buildUrl('/enseignant/absences.php', {
				enseignant_id: document.getElementById('ea_enseignant').value,
				seance_id: document.getElementById('ea_seance').value,
			});
			out('out_ea_get', await fetchJson(url));
		}

		async function testEnseignantAbsencesPost() {
			let absences;
			try {
				absences = JSON.parse(document.getElementById('ep_absences').value);
			} catch (e) {
				out('out_ea_post', 'Invalid absences JSON');
				return;
			}

			const payload = {
				enseignant_id: Number(document.getElementById('ep_enseignant').value),
				seance_id: Number(document.getElementById('ep_seance').value),
				absences,
			};

			const url = buildUrl('/enseignant/absences.php', {});
			out('out_ea_post', await fetchJson(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(payload),
			}));
		}

		async function testEtudiantProfil() {
			const url = buildUrl('/etudiant/profil.php', {
				etudiant_id: document.getElementById('sp_etudiant').value,
			});
			out('out_sp', await fetchJson(url));
		}

		async function testEtudiantAbsences() {
			const url = buildUrl('/etudiant/absences.php', {
				etudiant_id: document.getElementById('sa_etudiant').value,
				date_from: document.getElementById('sa_from').value,
				date_to: document.getElementById('sa_to').value,
				matiere_id: document.getElementById('sa_matiere').value,
			});
			out('out_sa', await fetchJson(url));
		}

		let globalToken = null;

		async function testLogin() {
			const email = document.getElementById('login_email').value;
			const password = document.getElementById('login_password').value;

			if (!email || !password) {
				out('out_login', 'Email and password are required');
				return;
			}

			const url = buildUrl('/auth/login.php', {});
			const result = await fetchJson(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ email, password }),
			});

			out('out_login', result);

			if (result.body && result.body.token) {
				globalToken = result.body.token;
				document.getElementById('token_input').value = globalToken;
				document.getElementById('token_display').textContent = globalToken.substring(0, 50) + '...';
			}
		}

		function useTokenFromInput() {
			const token = document.getElementById('token_input').value.trim();
			if (!token) {
				out('out_login', 'Paste a token first');
				return;
			}

			globalToken = token;
			document.getElementById('token_display').textContent = globalToken.substring(0, 50) + '...';
			out('out_login', 'Token loaded into the test page');
		}

		function getHeaders() {
			const pastedToken = document.getElementById('token_input').value.trim();
			const headers = { 'Content-Type': 'application/json' };
			if (pastedToken) {
				headers['Authorization'] = 'Bearer ' + pastedToken;
			} else if (globalToken) {
				headers['Authorization'] = 'Bearer ' + globalToken;
			}
			return headers;
		}

		async function testAdminListClasses() {
			const url = buildUrl('/admin/classes.php', {});
			out('out_classes_list', await fetchJson(url, {
				method: 'GET',
				headers: getHeaders(),
			}));
		}

		async function testAdminCreateClass() {
			const nom = document.getElementById('create_class_nom').value;
			const niveau = document.getElementById('create_class_niveau').value;

			if (!nom) {
				out('out_create_class', 'Class name is required');
				return;
			}

			const url = buildUrl('/admin/classes.php', {});
			out('out_create_class', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify({ nom, niveau }),
			}));
		}

		async function testAdminUpdateClass() {
			const classe_id = document.getElementById('update_class_id').value;
			const nom = document.getElementById('update_class_nom').value;
			const niveau = document.getElementById('update_class_niveau').value;

			if (!classe_id) {
				out('out_update_class', 'Class ID is required');
				return;
			}

			const payload = { classe_id };
			if (nom) payload.nom = nom;
			if (niveau) payload.niveau = niveau;

			const url = buildUrl('/admin/classes.php', {});
			out('out_update_class', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminListTeachers() {
			const url = buildUrl('/admin/enseignants.php', {});
			out('out_teachers_list', await fetchJson(url, {
				method: 'GET',
				headers: getHeaders(),
			}));
		}

		async function testAdminCreateTeacher() {
			const nom = document.getElementById('create_teacher_nom').value;
			const prenom = document.getElementById('create_teacher_prenom').value;
			const email = document.getElementById('create_teacher_email').value;
			const password = document.getElementById('create_teacher_password').value;
			const specialite = document.getElementById('create_teacher_specialite').value;

			if (!nom || !prenom || !email || !password) {
				out('out_create_teacher', 'Name, email and password are required');
				return;
			}

			const payload = { nom, prenom, email, password };
			if (specialite) payload.specialite = specialite;

			const url = buildUrl('/admin/enseignants.php', {});
			out('out_create_teacher', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminUpdateTeacher() {
			const enseignant_id = document.getElementById('update_teacher_id').value;
			const nom = document.getElementById('update_teacher_nom').value;
			const prenom = document.getElementById('update_teacher_prenom').value;
			const email = document.getElementById('update_teacher_email').value;
			const password = document.getElementById('update_teacher_password').value;
			const specialite = document.getElementById('update_teacher_specialite').value;

			if (!enseignant_id) {
				out('out_update_teacher', 'Teacher ID is required');
				return;
			}

			const payload = { enseignant_id };
			if (nom) payload.nom = nom;
			if (prenom) payload.prenom = prenom;
			if (email) payload.email = email;
			if (password) payload.password = password;
			if (specialite) payload.specialite = specialite;

			const url = buildUrl('/admin/enseignants.php', {});
			out('out_update_teacher', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminListStudents() {
			const classe_id = document.getElementById('list_students_class').value;
			const params = {};
			if (classe_id) params.classe_id = classe_id;

			const url = buildUrl('/admin/etudiants.php', params);
			out('out_students_list', await fetchJson(url, {
				method: 'GET',
				headers: getHeaders(),
			}));
		}

		async function testAdminCreateStudent() {
			const nom = document.getElementById('create_student_nom').value;
			const prenom = document.getElementById('create_student_prenom').value;
			const email = document.getElementById('create_student_email').value;
			const password = document.getElementById('create_student_password').value;
			const classe_id = document.getElementById('create_student_classe_id').value;

			if (!nom || !prenom || !email || !password || !classe_id) {
				out('out_create_student', 'All fields are required');
				return;
			}

			const payload = { nom, prenom, email, password, classe_id: Number(classe_id) };

			const url = buildUrl('/admin/etudiants.php', {});
			out('out_create_student', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminUpdateStudent() {
			const etudiant_id = document.getElementById('update_student_id').value;
			const nom = document.getElementById('update_student_nom').value;
			const prenom = document.getElementById('update_student_prenom').value;
			const email = document.getElementById('update_student_email').value;
			const password = document.getElementById('update_student_password').value;
			const classe_id = document.getElementById('update_student_classe_id').value;

			if (!etudiant_id) {
				out('out_update_student', 'Student ID is required');
				return;
			}

			const payload = { etudiant_id };
			if (nom) payload.nom = nom;
			if (prenom) payload.prenom = prenom;
			if (email) payload.email = email;
			if (password) payload.password = password;
			if (classe_id) payload.classe_id = Number(classe_id);

			const url = buildUrl('/admin/etudiants.php', {});
			out('out_update_student', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminListSessions() {
			const enseignant_id = document.getElementById('list_sessions_teacher').value;
			const classe_id = document.getElementById('list_sessions_class').value;
			const date = document.getElementById('list_sessions_date').value;

			const params = {};
			if (enseignant_id) params.enseignant_id = enseignant_id;
			if (classe_id) params.classe_id = classe_id;
			if (date) params.date_seance = date;

			const url = buildUrl('/admin/seances.php', params);
			out('out_sessions_list', await fetchJson(url, {
				method: 'GET',
				headers: getHeaders(),
			}));
		}

		async function testAdminCreateSession() {
			const enseignant_id = document.getElementById('create_session_teacher').value;
			const classe_id = document.getElementById('create_session_class').value;
			const matiere_id = document.getElementById('create_session_matiere').value;
			const date_seance = document.getElementById('create_session_date').value;
			const heure_debut = document.getElementById('create_session_start').value;
			const heure_fin = document.getElementById('create_session_end').value;

			if (!enseignant_id || !classe_id || !matiere_id || !date_seance || !heure_debut || !heure_fin) {
				out('out_create_session', 'All fields are required');
				return;
			}

			const payload = {
				enseignant_id: Number(enseignant_id),
				classe_id: Number(classe_id),
				matiere_id: Number(matiere_id),
				date_seance,
				heure_debut,
				heure_fin,
			};

			const url = buildUrl('/admin/seances.php', {});
			out('out_create_session', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}

		async function testAdminUpdateSession() {
			const seance_id = document.getElementById('update_session_id').value;
			const enseignant_id = document.getElementById('update_session_teacher').value;
			const classe_id = document.getElementById('update_session_class').value;
			const matiere_id = document.getElementById('update_session_matiere').value;
			const date_seance = document.getElementById('update_session_date').value;
			const heure_debut = document.getElementById('update_session_start').value;
			const heure_fin = document.getElementById('update_session_end').value;

			if (!seance_id) {
				out('out_update_session', 'Session ID is required');
				return;
			}

			const payload = { seance_id };
			if (enseignant_id) payload.enseignant_id = Number(enseignant_id);
			if (classe_id) payload.classe_id = Number(classe_id);
			if (matiere_id) payload.matiere_id = Number(matiere_id);
			if (date_seance) payload.date_seance = date_seance;
			if (heure_debut) payload.heure_debut = heure_debut;
			if (heure_fin) payload.heure_fin = heure_fin;

			const url = buildUrl('/admin/seances.php', {});
			out('out_update_session', await fetchJson(url, {
				method: 'POST',
				headers: getHeaders(),
				body: JSON.stringify(payload),
			}));
		}
	</script>
</body>
</html>

