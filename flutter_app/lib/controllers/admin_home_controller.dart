import 'package:flutter_app/services/api_service.dart';

class AdminHomeController {
  final ApiService _api;

  AdminHomeController({ApiService? apiService})
    : _api = apiService ?? ApiService();

  Future<void> logout() async {
    await _api.logout();
  }
}
