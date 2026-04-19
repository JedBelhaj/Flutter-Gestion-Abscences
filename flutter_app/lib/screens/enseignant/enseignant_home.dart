import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/admin_home_controller.dart';
import 'package:flutter_app/screens/enseignant/mes_seances_screen.dart';
import 'package:flutter_app/screens/login_screen.dart';
import 'package:flutter_app/services/theme_service.dart';

class EnseignantHome extends StatefulWidget {
  const EnseignantHome({super.key});

  @override
  State<EnseignantHome> createState() => _EnseignantHomeState();
}

class _EnseignantHomeState extends State<EnseignantHome> {
  final AdminHomeController _controller = AdminHomeController();

  Future<void> _confirmLogout() async {
    final shouldLogout = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Voulez-vous vraiment vous deconnecter ?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Logout'),
            ),
          ],
        );
      },
    );

    if (shouldLogout != true) {
      return;
    }

    try {
      await _controller.logout();
      if (!mounted) {
        return;
      }
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Erreur logout: $e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Enseignant - Mes seances'),
        actions: [
          ValueListenableBuilder<ThemeMode>(
            valueListenable: ThemeService.instance.modeNotifier,
            builder: (context, mode, _) {
              return IconButton(
                onPressed: ThemeService.instance.toggleThemeMode,
                icon: Icon(
                  mode == ThemeMode.dark ? Icons.light_mode : Icons.dark_mode,
                ),
                tooltip: 'Theme',
              );
            },
          ),
          IconButton(
            onPressed: _confirmLogout,
            icon: const Icon(Icons.logout),
            tooltip: 'Logout',
          ),
        ],
      ),
      body: const MesSeancesScreen(),
    );
  }
}
