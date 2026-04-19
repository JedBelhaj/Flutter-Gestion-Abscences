class Utilisateur {
  final int id;
  final String nom;
  final String prenom;
  final String email;
  final String role;
  final String specialite;

  const Utilisateur({
    required this.id,
    required this.nom,
    required this.prenom,
    required this.email,
    required this.role,
    this.specialite = '',
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

  factory Utilisateur.fromJson(Map<String, dynamic> json) {
    final rawId =
        json['id'] ?? json['etudiant_id'] ?? json['enseignant_id'] ?? 0;
    return Utilisateur(
      id: _toInt(rawId),
      nom: (json['nom'] ?? '').toString(),
      prenom: (json['prenom'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      role: (json['role'] ?? '').toString(),
      specialite: (json['specialite'] ?? '').toString(),
    );
  }
}
