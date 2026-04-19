import 'package:flutter_app/models/utilisateur.dart';

class Etudiant extends Utilisateur {
  final int? classeId;
  final String classeNom;
  final String statut;

  const Etudiant({
    required super.id,
    required super.nom,
    required super.prenom,
    required super.email,
    super.role = 'etudiant',
    this.classeId,
    this.classeNom = '',
    this.statut = 'present',
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

  factory Etudiant.fromJson(Map<String, dynamic> json) {
    final rawClasseId = json['classe_id'];
    final classeId = _toInt(rawClasseId);
    return Etudiant(
      id: _toInt(json['etudiant_id'] ?? json['id']),
      nom: (json['nom'] ?? '').toString(),
      prenom: (json['prenom'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      classeId: classeId > 0 ? classeId : null,
      classeNom: (json['classe_nom'] ?? '').toString(),
      statut: (json['statut'] ?? 'present').toString(),
    );
  }

  int get etudiantId => id;

  bool get isPresent => statut.toLowerCase() == 'present';

  String get fullName => '$nom $prenom'.trim();

  Etudiant copyWith({String? statut}) {
    return Etudiant(
      id: id,
      nom: nom,
      prenom: prenom,
      email: email,
      role: role,
      classeId: classeId,
      classeNom: classeNom,
      statut: statut ?? this.statut,
    );
  }
}
