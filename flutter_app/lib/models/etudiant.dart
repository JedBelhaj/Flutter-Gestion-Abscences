import 'package:flutter_app/models/utilisateur.dart';

class Etudiant extends Utilisateur {
	final int? classeId;
	final String classeNom;

	const Etudiant({
		required super.id,
		required super.nom,
		required super.prenom,
		required super.email,
		super.role = 'etudiant',
		this.classeId,
		this.classeNom = '',
	});

	factory Etudiant.fromJson(Map<String, dynamic> json) {
		final rawClasseId = json['classe_id'];
		return Etudiant(
			id: ((json['etudiant_id'] ?? json['id'] ?? 0) as num).toInt(),
			nom: (json['nom'] ?? '').toString(),
			prenom: (json['prenom'] ?? '').toString(),
			email: (json['email'] ?? '').toString(),
			classeId: rawClasseId is num ? rawClasseId.toInt() : null,
			classeNom: (json['classe_nom'] ?? '').toString(),
		);
	}
}
