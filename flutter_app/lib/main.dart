import 'package:flutter/material.dart';
import 'package:flutter_app/screens/login_screen.dart';

void main() {
  runApp(const MainApp());
}

class MainApp extends StatelessWidget {
  const MainApp({super.key});

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
      home: const Scaffold(body: LoginScreen()),
    );
  }
}
