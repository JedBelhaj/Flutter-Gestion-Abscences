import 'package:flutter_app/models/absence.dart';
import 'package:flutter_app/services/api_service.dart';

class AbsencesController {
  final ApiService _api;

  AbsencesController({ApiService? apiService})
    : _api = apiService ?? ApiService();

  Future<List<Absence>> fetchAbsencesEtudiant() async {
    final etudiantId = await _api.getConnectedEtudiantId();
    if (etudiantId == null || etudiantId <= 0) {
      throw Exception('Etudiant connecte introuvable');
    }

    final rows = await _api.getAbsencesEtudiant(etudiantId: etudiantId);
    return rows
        .whereType<Map<String, dynamic>>()
        .map(Absence.fromJson)
        .toList();
  }

  int totalAbsences(List<Absence> absences) {
    return absences.where((item) => !item.isPresent).length;
  }
}
