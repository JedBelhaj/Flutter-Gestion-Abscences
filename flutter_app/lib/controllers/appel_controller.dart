import 'package:flutter_app/models/etudiant.dart';
import 'package:flutter_app/models/seance.dart';
import 'package:flutter_app/services/api_service.dart';

class AppelController {
  final ApiService _api;

  AppelController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<List<Etudiant>> fetchEtudiantsPourSeance(Seance seance) async {
    final enseignantId = await _api.getConnectedEnseignantId();
    if (enseignantId == null || enseignantId <= 0) {
      throw Exception('Enseignant connecte introuvable');
    }

    final rows = await _api.getAppelEtudiants(
      seanceId: seance.id,
      enseignantId: enseignantId,
    );

    return rows
        .whereType<Map<String, dynamic>>()
        .map(Etudiant.fromJson)
        .toList();
  }

  Future<void> validerAppel({
    required Seance seance,
    required List<Etudiant> etudiants,
  }) async {
    final enseignantId = await _api.getConnectedEnseignantId();
    if (enseignantId == null || enseignantId <= 0) {
      throw Exception('Enseignant connecte introuvable');
    }

    final payload = etudiants
        .map(
          (item) => {
            'etudiant_id': item.etudiantId,
            'statut': item.isPresent ? 'present' : 'absent',
          },
        )
        .toList();

    await _api.submitAppel(
      seanceId: seance.id,
      enseignantId: enseignantId,
      absences: payload,
    );
  }
}
