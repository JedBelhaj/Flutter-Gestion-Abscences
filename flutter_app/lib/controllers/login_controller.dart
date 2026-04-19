import 'package:flutter_app/services/api_service.dart';

class LoginController {
  final ApiService _api;

  LoginController({ApiService? apiService}) : _api = apiService ?? ApiService();

  Future<void> login({required String email, required String password}) async {
    await _api.login(email: email, password: password);
  }

  Future<String?> currentUserRole() async {
    return _api.getCurrentUserRole();
  }
}
