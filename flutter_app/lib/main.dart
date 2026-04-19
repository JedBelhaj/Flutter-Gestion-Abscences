import 'package:flutter/material.dart';
import 'package:flutter_app/screens/admin/admin_home.dart';
import 'package:flutter_app/screens/enseignant/enseignant_home.dart';
import 'package:flutter_app/screens/etudiant/etudiant_home.dart';
import 'package:flutter_app/screens/login_screen.dart';
import 'package:flutter_app/services/api_service.dart';

void main() {
  runApp(const MainApp());
}

class MainApp extends StatefulWidget {
  const MainApp({super.key});

  @override
  State<MainApp> createState() => _MainAppState();
}

class _MainAppState extends State<MainApp> {
  final ApiService _apiService = ApiService();
  late Future<Widget> _initialScreenFuture;

  @override
  void initState() {
    super.initState();
    _initialScreenFuture = _resolveInitialScreen();
  }

  Future<Widget> _resolveInitialScreen() async {
    final role = await _apiService.getCurrentUserRole();
    final userId = await _apiService.getCurrentUserId();

    if (role == null || role.isEmpty || userId == null || userId <= 0) {
      return const LoginScreen();
    }

    return switch (role) {
      'admin' => const AdminHome(),
      'enseignant' => const EnseignantHome(),
      'etudiant' => const EtudiantHome(),
      _ => const LoginScreen(),
    };
  }

  @override
  Widget build(BuildContext context) {
    final largeButtonStyle = ButtonStyle(
      minimumSize: WidgetStateProperty.all(const Size(140, 56)),
      padding: WidgetStateProperty.all(
        const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      ),
      textStyle: WidgetStateProperty.all(
        const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
    );

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: Colors.indigo,
        elevatedButtonTheme: ElevatedButtonThemeData(style: largeButtonStyle),
        filledButtonTheme: FilledButtonThemeData(style: largeButtonStyle),
        outlinedButtonTheme: OutlinedButtonThemeData(style: largeButtonStyle),
        textButtonTheme: TextButtonThemeData(style: largeButtonStyle),
      ),
      home: FutureBuilder<Widget>(
        future: _initialScreenFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }

          if (snapshot.hasError) {
            return const Scaffold(body: LoginScreen());
          }

          return Scaffold(body: snapshot.data ?? const LoginScreen());
        },
      ),
    );
  }
}
