class Absence {
  final int id;
  final int etudiantId;
  final int seanceId;
  final String statut;
  final String datePointage;
  final String matiereNom;
  final String dateSeance;
  final String heureDebut;
  final String heureFin;

  const Absence({
    required this.id,
    required this.etudiantId,
    required this.seanceId,
    required this.statut,
    required this.datePointage,
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

  bool get isPresent => statut.toLowerCase() == 'present';

  factory Absence.fromJson(Map<String, dynamic> json) {
    return Absence(
      id: _toInt(json['absence_id'] ?? json['id']),
      etudiantId: _toInt(json['etudiant_id']),
      seanceId: _toInt(json['seance_id']),
      statut: (json['statut'] ?? '').toString(),
      datePointage: (json['date_pointage'] ?? '').toString(),
      matiereNom: (json['matiere_nom'] ?? '').toString(),
      dateSeance: (json['date_seance'] ?? '').toString(),
      heureDebut: (json['heure_debut'] ?? '').toString(),
      heureFin: (json['heure_fin'] ?? '').toString(),
    );
  }
}
