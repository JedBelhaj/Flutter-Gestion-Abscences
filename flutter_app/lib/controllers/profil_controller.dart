import 'package:flutter_app/models/etudiant.dart';
import 'package:flutter_app/services/api_service.dart';

class ProfilController {
  final ApiService _api;

  ProfilController({ApiService? apiService})
    : _api = apiService ?? ApiService();

  Future<Etudiant> fetchProfilEtudiant() async {
    final etudiantId = await _api.getConnectedEtudiantId();
    if (etudiantId == null || etudiantId <= 0) {
      throw Exception('Etudiant connecte introuvable');
    }

    final row = await _api.getProfilEtudiant(etudiantId: etudiantId);
    return Etudiant.fromJson(row);
  }
}
