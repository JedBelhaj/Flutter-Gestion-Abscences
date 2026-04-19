class Seance {
	final int id;
	final int? enseignantId;
	final int? classeId;
	final int? matiereId;
	final String enseignantNom;
	final String enseignantPrenom;
	final String classeNom;
	final String matiereNom;
	final String dateSeance;
	final String heureDebut;
	final String heureFin;

	const Seance({
		required this.id,
		this.enseignantId,
		this.classeId,
		this.matiereId,
		this.enseignantNom = '',
		this.enseignantPrenom = '',
		this.classeNom = '',
		this.matiereNom = '',
		this.dateSeance = '',
		this.heureDebut = '',
		this.heureFin = '',
	});

	factory Seance.fromJson(Map<String, dynamic> json) {
		final rawEnseignantId = json['enseignant_id'];
		final rawClasseId = json['classe_id'];
		final rawMatiereId = json['matiere_id'];

		return Seance(
			id: ((json['id'] ?? json['seance_id'] ?? 0) as num).toInt(),
			enseignantId: rawEnseignantId is num ? rawEnseignantId.toInt() : null,
			classeId: rawClasseId is num ? rawClasseId.toInt() : null,
			matiereId: rawMatiereId is num ? rawMatiereId.toInt() : null,
			enseignantNom: (json['enseignant_nom'] ?? '').toString(),
			enseignantPrenom: (json['enseignant_prenom'] ?? '').toString(),
			classeNom: (json['classe_nom'] ?? '').toString(),
			matiereNom: (json['matiere_nom'] ?? '').toString(),
			dateSeance: (json['date_seance'] ?? '').toString(),
			heureDebut: (json['heure_debut'] ?? '').toString(),
			heureFin: (json['heure_fin'] ?? '').toString(),
		);
	}
}
