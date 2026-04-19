import 'package:flutter_app/models/utilisateur.dart';

class Enseignant extends Utilisateur {
	final String specialite;

	const Enseignant({
		required super.id,
		required super.nom,
		required super.prenom,
		required super.email,
		super.role = 'enseignant',
		this.specialite = '',
	});

	factory Enseignant.fromJson(Map<String, dynamic> json) {
		return Enseignant(
			id: ((json['enseignant_id'] ?? json['id'] ?? 0) as num).toInt(),
			nom: (json['nom'] ?? '').toString(),
			prenom: (json['prenom'] ?? '').toString(),
			email: (json['email'] ?? '').toString(),
			specialite: (json['specialite'] ?? '').toString(),
		);
	}
}
