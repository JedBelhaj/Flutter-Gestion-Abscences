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
	</script>
</body>
</html>

