import 'package:flutter_app/models/seance.dart';
import 'package:flutter_app/services/api_service.dart';

class MesSeancesController {
  final ApiService _api;

  MesSeancesController({ApiService? apiService})
    : _api = apiService ?? ApiService();

  Future<List<Seance>> fetchMesSeances() async {
    final enseignantId = await _api.getConnectedEnseignantId();
    if (enseignantId == null || enseignantId <= 0) {
      throw Exception('Enseignant connecte introuvable');
    }

    final rows = await _api.getMesSeances(enseignantId: enseignantId);
    return rows.whereType<Map<String, dynamic>>().map(Seance.fromJson).toList();
  }
}
