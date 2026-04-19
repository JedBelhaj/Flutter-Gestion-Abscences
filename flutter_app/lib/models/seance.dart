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

  static int _toInt(dynamic value) {
    if (value is num) {
      return value.toInt();
    }
    if (value is String) {
      return int.tryParse(value) ?? 0;
    }
    return 0;
  }

  factory Seance.fromJson(Map<String, dynamic> json) {
    final rawEnseignantId = json['enseignant_id'];
    final rawClasseId = json['classe_id'];
    final rawMatiereId = json['matiere_id'];
    final enseignantId = _toInt(rawEnseignantId);
    final classeId = _toInt(rawClasseId);
    final matiereId = _toInt(rawMatiereId);

    return Seance(
      id: _toInt(json['id'] ?? json['seance_id']),
      enseignantId: enseignantId > 0 ? enseignantId : null,
      classeId: classeId > 0 ? classeId : null,
      matiereId: matiereId > 0 ? matiereId : null,
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
