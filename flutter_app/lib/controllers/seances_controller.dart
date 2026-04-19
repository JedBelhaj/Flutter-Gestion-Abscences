import 'package:flutter_app/models/seance.dart';
import 'package:flutter_app/services/api_service.dart';

class SeancesController {
  final ApiService _api;

  SeancesController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<List<Seance>> fetchSeances() async {
    final rows = await _api.getSeances();
    return rows.whereType<Map<String, dynamic>>().map(Seance.fromJson).toList();
  }

  Future<List<Map<String, dynamic>>> fetchEnseignants() async {
    final rows = await _api.getEnseignants();
    return rows.whereType<Map<String, dynamic>>().toList();
  }

  Future<List<Map<String, dynamic>>> fetchClasses() async {
    final rows = await _api.getClasses();
    return rows.whereType<Map<String, dynamic>>().toList();
  }

  Future<List<Map<String, dynamic>>> fetchMatieres() async {
    final rows = await _api.getMatieres();
    return rows.whereType<Map<String, dynamic>>().toList();
  }

  Future<void> addSeance({
    required int enseignantId,
    required int classeId,
    required int matiereId,
    required String dateSeance,
    required String heureDebut,
    required String heureFin,
  }) async {
    await _api.addSeance(
      enseignantId: enseignantId,
      classeId: classeId,
      matiereId: matiereId,
      dateSeance: dateSeance,
      heureDebut: heureDebut,
      heureFin: heureFin,
    );
  }

  Future<void> deleteSeance(int seanceId) async {
    await _api.deleteSeance(seanceId: seanceId);
  }

  List<Seance> filterSeances(List<Seance> items, String query) {
    final normalizedQuery = query.trim().toLowerCase();
    if (normalizedQuery.isEmpty) {
      return items;
    }

    return items.where((seance) {
      final matiere = seance.matiereNom.toLowerCase();
      final classe = seance.classeNom.toLowerCase();
      final enseignant =
          '${seance.enseignantNom} ${seance.enseignantPrenom}'.toLowerCase();
      final date = seance.dateSeance.toLowerCase();
      return matiere.contains(normalizedQuery) ||
          classe.contains(normalizedQuery) ||
          enseignant.contains(normalizedQuery) ||
          date.contains(normalizedQuery);
    }).toList();
  }

  String dateTimeToApiTime(String value) {
    return value.length == 5 ? '$value:00' : value;
  }
}
