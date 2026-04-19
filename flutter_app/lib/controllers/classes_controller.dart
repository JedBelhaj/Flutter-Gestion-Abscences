import 'package:flutter_app/services/api_service.dart';

class ClassesController {
  final ApiService _api;

  ClassesController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<List<Map<String, dynamic>>> fetchClasses() async {
    final rows = await _api.getClasses();
    return rows.whereType<Map<String, dynamic>>().toList();
  }

  Future<void> addClasse({required String nom, required String niveau}) async {
    await _api.addClasse(nom: nom, niveau: niveau);
  }

  Future<void> deleteClasse(int classeId) async {
    await _api.deleteClasse(classeId: classeId);
  }

  List<Map<String, dynamic>> filterClasses(
    List<Map<String, dynamic>> items,
    String query,
  ) {
    final normalizedQuery = query.trim().toLowerCase();
    if (normalizedQuery.isEmpty) {
      return items;
    }

    return items.where((classe) {
      final nom = (classe['nom'] ?? '').toString().toLowerCase();
      final niveau = (classe['niveau'] ?? '').toString().toLowerCase();
      return nom.contains(normalizedQuery) || niveau.contains(normalizedQuery);
    }).toList();
  }
}
