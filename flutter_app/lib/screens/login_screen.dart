import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/login_controller.dart';
import 'package:flutter_app/screens/admin/admin_home.dart';
import 'package:flutter_app/screens/enseignant/enseignant_home.dart';
import 'package:flutter_app/screens/etudiant/etudiant_home.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final LoginController _controller = LoginController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Email et mot de passe sont obligatoires'),
        ),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      await _controller.login(email: email, password: password);
      final role = await _controller.currentUserRole();

      if (!mounted) {
        return;
      }

      final Widget destination = switch (role) {
        'admin' => const AdminHome(),
        'enseignant' => const EnseignantHome(),
        'etudiant' => const EtudiantHome(),
        _ => const AdminHome(),
      };

      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => destination),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Erreur: $e')));
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(40.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Login Screen'),
              TextField(
                controller: _emailController,
                decoration: const InputDecoration(labelText: 'Email'),
              ),
              TextField(
                controller: _passwordController,
                decoration: const InputDecoration(labelText: 'Password'),
                obscureText: true,
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _isLoading ? null : _handleLogin,
                child: Text(_isLoading ? 'Connexion...' : 'Login'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
