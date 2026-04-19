import 'package:flutter_app/models/etudiant.dart';
import 'package:flutter_app/services/api_service.dart';

class EtudiantsController {
  final ApiService _api;

  EtudiantsController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<List<Etudiant>> fetchEtudiants() async {
    final rows = await _api.getEtudiants();
    return rows
        .whereType<Map<String, dynamic>>()
        .map(Etudiant.fromJson)
        .toList();
  }

  Future<List<Map<String, dynamic>>> fetchClasses() async {
    final rows = await _api.getClasses();
    return rows.whereType<Map<String, dynamic>>().toList();
  }

  Future<void> addEtudiant({
    required String nom,
    required String prenom,
    required String email,
    required String password,
    required int classeId,
  }) async {
    await _api.addEtudiant(
      nom: nom,
      prenom: prenom,
      email: email,
      password: password,
      classeId: classeId,
    );
  }

  Future<void> updateEtudiant({
    required int etudiantId,
    String? nom,
    String? prenom,
    String? email,
    String? password,
    int? classeId,
  }) async {
    await _api.updateEtudiant(
      etudiantId: etudiantId,
      nom: nom,
      prenom: prenom,
      email: email,
      password: password,
      classeId: classeId,
    );
  }

  Future<void> deleteEtudiant(int etudiantId) async {
    await _api.deleteEtudiant(etudiantId: etudiantId);
  }

  List<Etudiant> filterEtudiants(List<Etudiant> items, String query) {
    final normalizedQuery = query.trim().toLowerCase();
    if (normalizedQuery.isEmpty) {
      return items;
    }

    return items.where((etudiant) {
      final fullName = '${etudiant.nom} ${etudiant.prenom}'.toLowerCase();
      final classe = etudiant.classeNom.toLowerCase();
      final email = etudiant.email.toLowerCase();
      return fullName.contains(normalizedQuery) ||
          classe.contains(normalizedQuery) ||
          email.contains(normalizedQuery);
    }).toList();
  }
}
