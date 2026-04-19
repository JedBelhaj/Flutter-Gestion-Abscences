class Utilisateur {
  final int id;
  final String nom;
  final String prenom;
  final String email;
  final String role;

  const Utilisateur({
    required this.id,
    required this.nom,
    required this.prenom,
    required this.email,
    required this.role,
  });

  factory Utilisateur.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'] ?? json['etudiant_id'] ?? json['enseignant_id'] ?? 0;
    return Utilisateur(
      id: (rawId as num).toInt(),
      nom: (json['nom'] ?? '').toString(),
      prenom: (json['prenom'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      role: (json['role'] ?? '').toString(),
    );
  }
}
