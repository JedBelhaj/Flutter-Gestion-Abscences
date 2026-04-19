class Absence {
	final int id;
	final int etudiantId;
	final int seanceId;
	final String statut;
	final String datePointage;

	const Absence({
		required this.id,
		required this.etudiantId,
		required this.seanceId,
		required this.statut,
		required this.datePointage,
	});

	factory Absence.fromJson(Map<String, dynamic> json) {
		return Absence(
			id: ((json['id'] ?? 0) as num).toInt(),
			etudiantId: ((json['etudiant_id'] ?? 0) as num).toInt(),
			seanceId: ((json['seance_id'] ?? 0) as num).toInt(),
			statut: (json['statut'] ?? '').toString(),
			datePointage: (json['date_pointage'] ?? '').toString(),
		);
	}
}
