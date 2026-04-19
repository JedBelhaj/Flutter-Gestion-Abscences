import 'dart:convert';

import 'package:flutter_app/config/api_config.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'auth_user';
  static const String _userIdKey = 'user_id';
  static const String _roleKey = 'user_role';
  static String? _workingBaseUrl;

  Future<void> setToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    await prefs.remove(_userIdKey);
    await prefs.remove(_roleKey);
  }

  Future<void> setCurrentUserId(int id) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_userIdKey, id);
  }

  Future<int?> getCurrentUserId() async {
    final prefs = await SharedPreferences.getInstance();
    final value = prefs.getInt(_userIdKey);
    if (value != null) {
      return value;
    }

    final user = await getCurrentUser();
    final raw = user?['id'];
    if (raw is num) {
      return raw.toInt();
    }
    if (raw is String) {
      return int.tryParse(raw);
    }
    return null;
  }

  Future<void> setCurrentUserRole(String role) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_roleKey, role);
  }

  Future<void> setCurrentUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  Future<Map<String, dynamic>?> getCurrentUser() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_userKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    final decoded = jsonDecode(raw);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }
    return null;
  }

  Future<String?> getCurrentUserRole() async {
    final prefs = await SharedPreferences.getInstance();
    final storedRole = prefs.getString(_roleKey);
    if (storedRole != null && storedRole.isNotEmpty) {
      return storedRole;
    }

    final user = await getCurrentUser();
    return user?['role']?.toString();
  }

  Future<int?> getConnectedEnseignantId() async {
    final user = await getCurrentUser();
    final raw = user?['enseignant_id'] ?? user?['id'];
    if (raw is num) {
      return raw.toInt();
    }
    if (raw is String) {
      return int.tryParse(raw);
    }
    return null;
  }

  Future<int?> getConnectedEtudiantId() async {
    final user = await getCurrentUser();
    final raw = user?['etudiant_id'] ?? user?['id'];
    if (raw is num) {
      return raw.toInt();
    }
    if (raw is String) {
      return int.tryParse(raw);
    }
    return null;
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _post('/auth/login.php', {
      'email': email,
      'password': password,
    }, includeAuth: false);

    final token = response['token']?.toString();
    final user = response['user'];
    if (token == null || token.isEmpty) {
      throw Exception('Token manquant dans la reponse de login');
    }
    if (user is! Map<String, dynamic>) {
      throw Exception('Utilisateur manquant dans la reponse de login');
    }

    await setToken(token);
    await setCurrentUser(user);
    final rawId = user['id'];
    final userId = rawId is num
        ? rawId.toInt()
        : rawId is String
        ? int.tryParse(rawId)
        : null;
    final role = user['role']?.toString();

    if (userId != null && userId > 0) {
      await setCurrentUserId(userId);
    }
    if (role != null && role.isNotEmpty) {
      await setCurrentUserRole(role);
    }

    return response;
  }

  Future<void> logout() async {
    try {
      await _post('/auth/login.php', {'action': 'logout'});
    } finally {
      await clearToken();
    }
  }

  Future<List<dynamic>> getEtudiants() async {
    final response = await _get('/admin/etudiants.php');
    return _readDataList(response);
  }

  Future<void> addEtudiant({
    required String nom,
    required String prenom,
    required String email,
    required String password,
    required int classeId,
  }) async {
    await _post('/admin/etudiants.php', {
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'password': password,
      'classe_id': classeId,
    });
  }

  Future<void> updateEtudiant({
    required int etudiantId,
    String? nom,
    String? prenom,
    String? email,
    String? password,
    int? classeId,
  }) async {
    final body = <String, dynamic>{
      'etudiant_id': etudiantId,
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'password': password,
      'classe_id': classeId,
    };

    body.removeWhere((key, value) {
      if (value == null) {
        return true;
      }
      if (value is String && value.isEmpty) {
        return true;
      }
      return false;
    });

    await _put('/admin/etudiants.php', body);
  }

  Future<void> deleteEtudiant({required int etudiantId}) async {
    await _delete('/admin/etudiants.php', {'etudiant_id': etudiantId});
  }

  Future<List<dynamic>> getEnseignants() async {
    final response = await _get('/admin/enseignants.php');
    return _readDataList(response);
  }

  Future<void> addEnseignant({
    required String nom,
    required String prenom,
    required String email,
    required String password,
    required String specialite,
  }) async {
    await _post('/admin/enseignants.php', {
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'password': password,
      'specialite': specialite,
    });
  }

  Future<void> updateEnseignant({
    required int enseignantId,
    String? nom,
    String? prenom,
    String? email,
    String? password,
    String? specialite,
  }) async {
    final body = <String, dynamic>{
      'enseignant_id': enseignantId,
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'password': password,
      'specialite': specialite,
    };

    body.removeWhere((key, value) {
      if (value == null) {
        return true;
      }
      if (value is String && value.isEmpty) {
        return true;
      }
      return false;
    });

    await _put('/admin/enseignants.php', body);
  }

  Future<void> deleteEnseignant({required int enseignantId}) async {
    await _delete('/admin/enseignants.php', {'enseignant_id': enseignantId});
  }

  Future<List<dynamic>> getMatieres() async {
    final response = await _get('/admin/matieres.php');
    return _readDataList(response);
  }

  Future<List<dynamic>> getClasses() async {
    final response = await _get('/admin/classes.php');
    return _readDataList(response);
  }

  Future<void> addClasse({required String nom, required String niveau}) async {
    await _post('/admin/classes.php', {'nom': nom, 'niveau': niveau});
  }

  Future<void> deleteClasse({required int classeId}) async {
    await _delete('/admin/classes.php', {'classe_id': classeId});
  }

  Future<List<dynamic>> getSeances() async {
    final response = await _get('/admin/seances.php');
    return _readDataList(response);
  }

  Future<void> addSeance({
    required int enseignantId,
    required int classeId,
    required int matiereId,
    required String dateSeance,
    required String heureDebut,
    required String heureFin,
  }) async {
    await _post('/admin/seances.php', {
      'enseignant_id': enseignantId,
      'classe_id': classeId,
      'matiere_id': matiereId,
      'date_seance': dateSeance,
      'heure_debut': heureDebut,
      'heure_fin': heureFin,
    });
  }

  Future<void> deleteSeance({required int seanceId}) async {
    await _delete('/admin/seances.php', {'seance_id': seanceId});
  }

  Future<List<dynamic>> getMesSeances({required int enseignantId}) async {
    final response = await _get(
      '/enseignant/seances.php?enseignant_id=$enseignantId',
    );
    return _readDataList(response);
  }

  Future<List<dynamic>> getAppelEtudiants({
    required int seanceId,
    required int enseignantId,
  }) async {
    final response = await _get(
      '/enseignant/absences.php?seance_id=$seanceId&enseignant_id=$enseignantId',
    );
    return _readDataList(response);
  }

  Future<void> submitAppel({
    required int seanceId,
    required int enseignantId,
    required List<Map<String, dynamic>> absences,
  }) async {
    await _post('/enseignant/absences.php', {
      'seance_id': seanceId,
      'id': enseignantId,
      'enseignant_id': enseignantId,
      'absences': absences,
    });
  }

  Future<Map<String, dynamic>> getProfilEtudiant({
    required int etudiantId,
  }) async {
    final response = await _get('/etudiant/profil.php?etudiant_id=$etudiantId');
    final data = response['data'];
    if (data is Map<String, dynamic>) {
      return data;
    }
    throw Exception('Profil etudiant invalide');
  }

  Future<List<dynamic>> getAbsencesEtudiant({required int etudiantId}) async {
    final response = await _get(
      '/etudiant/absences.php?etudiant_id=$etudiantId',
    );
    return _readDataList(response);
  }

  Future<Map<String, dynamic>> _get(String path) async {
    final response = await _requestWithFallback(
      method: 'GET',
      path: path,
      includeAuth: true,
    );
    return _decodeAndValidate(response);
  }

  Future<Map<String, dynamic>> _post(
    String path,
    Map<String, dynamic> body, {
    bool includeAuth = true,
  }) async {
    final response = await _requestWithFallback(
      method: 'POST',
      path: path,
      body: body,
      includeAuth: includeAuth,
    );
    return _decodeAndValidate(response);
  }

  Future<Map<String, dynamic>> _put(
    String path,
    Map<String, dynamic> body, {
    bool includeAuth = true,
  }) async {
    final response = await _requestWithFallback(
      method: 'PUT',
      path: path,
      body: body,
      includeAuth: includeAuth,
    );
    return _decodeAndValidate(response);
  }

  Future<Map<String, dynamic>> _delete(
    String path,
    Map<String, dynamic> body, {
    bool includeAuth = true,
  }) async {
    final response = await _requestWithFallback(
      method: 'DELETE',
      path: path,
      body: body,
      includeAuth: includeAuth,
    );
    return _decodeAndValidate(response);
  }

  Future<http.Response> _requestWithFallback({
    required String method,
    required String path,
    Map<String, dynamic>? body,
    required bool includeAuth,
  }) async {
    final token = includeAuth ? await getToken() : null;
    final candidates = <String>[
      if (_workingBaseUrl case final value?) value,
      ...apiBaseUrls.where((url) => url != _workingBaseUrl),
    ];

    Object? lastError;

    for (final base in candidates) {
      try {
        final baseUri = Uri.parse('$base$path');
        final queryParameters = <String, String>{
          ...baseUri.queryParameters,
          if (token case final value?) 'token': value,
        };
        final uri = baseUri.replace(
          queryParameters: queryParameters.isEmpty ? null : queryParameters,
        );
        final headers = <String, String>{
          'Accept': 'application/json',
          if (method == 'POST' || method == 'PUT' || method == 'DELETE')
            'Content-Type': 'application/json',
          if (token case final value?) 'Authorization': 'Bearer $value',
        };

        final response = switch (method) {
          'GET' => await http.get(uri, headers: headers),
          'POST' => await http.post(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          ),
          'PUT' => await http.put(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          ),
          'DELETE' => await http.delete(
            uri,
            headers: headers,
            body: jsonEncode(body ?? {}),
          ),
          _ => throw UnsupportedError('Unsupported HTTP method: $method'),
        };

        if (_looksLikeHtml(response.body) && !_looksLikeJson(response.body)) {
          lastError = Exception(
            'Reponse HTML pour $uri (HTTP ${response.statusCode})',
          );
          continue;
        }

        _workingBaseUrl = base;
        return response;
      } catch (e) {
        lastError = e;
      }
    }

    throw Exception(
      'Impossible de joindre une URL API valide. Derniere erreur: $lastError',
    );
  }

  bool _looksLikeHtml(String body) {
    final text = body.trimLeft().toLowerCase();
    return text.startsWith('<!doctype html') || text.startsWith('<html');
  }

  bool _looksLikeJson(String body) {
    final text = body.trimLeft();
    return text.startsWith('{') || text.startsWith('[');
  }

  Map<String, dynamic> _decodeAndValidate(http.Response response) {
    dynamic decoded;
    try {
      decoded = jsonDecode(response.body);
    } on FormatException {
      final bodyPreview = response.body.length > 180
          ? '${response.body.substring(0, 180)}...'
          : response.body;
      throw Exception(
        'Reponse non JSON (HTTP ${response.statusCode}). Verifiez baseUrl et le serveur backend. Debut reponse: $bodyPreview',
      );
    }

    if (decoded is! Map<String, dynamic>) {
      throw Exception('Reponse API invalide');
    }

    if (response.statusCode >= 400 || decoded['success'] != true) {
      throw Exception(decoded['message']?.toString() ?? 'Erreur API');
    }

    return decoded;
  }

  List<dynamic> _readDataList(Map<String, dynamic> response) {
    final data = response['data'];
    if (data is List<dynamic>) {
      return data;
    }
    return [];
  }
}
