import 'package:flutter_app/models/enseignant.dart';
import 'package:flutter_app/services/api_service.dart';

class EnseignantsController {
  final ApiService _api;

  EnseignantsController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<List<Enseignant>> fetchEnseignants() async {
    final rows = await _api.getEnseignants();
    return rows
        .whereType<Map<String, dynamic>>()
        .map(Enseignant.fromJson)
        .toList();
  }

  Future<void> addEnseignant({
    required String nom,
    required String prenom,
    required String email,
    required String password,
    required String specialite,
  }) async {
    await _api.addEnseignant(
      nom: nom,
      prenom: prenom,
      email: email,
      password: password,
      specialite: specialite,
    );
  }

  Future<void> updateEnseignant({
    required int enseignantId,
    String? nom,
    String? prenom,
    String? email,
    String? password,
    String? specialite,
  }) async {
    await _api.updateEnseignant(
      enseignantId: enseignantId,
      nom: nom,
      prenom: prenom,
      email: email,
      password: password,
      specialite: specialite,
    );
  }

  Future<void> deleteEnseignant(int enseignantId) async {
    await _api.deleteEnseignant(enseignantId: enseignantId);
  }

  List<Enseignant> filterEnseignants(List<Enseignant> items, String query) {
    final normalizedQuery = query.trim().toLowerCase();
    if (normalizedQuery.isEmpty) {
      return items;
    }

    return items.where((enseignant) {
      final fullName = '${enseignant.nom} ${enseignant.prenom}'.toLowerCase();
      final specialite = enseignant.specialite.toLowerCase();
      final email = enseignant.email.toLowerCase();
      return fullName.contains(normalizedQuery) ||
          specialite.contains(normalizedQuery) ||
          email.contains(normalizedQuery);
    }).toList();
  }
}
