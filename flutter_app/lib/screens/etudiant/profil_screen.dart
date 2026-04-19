import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/profil_controller.dart';
import 'package:flutter_app/models/etudiant.dart';

class ProfilScreen extends StatefulWidget {
  const ProfilScreen({super.key});

  @override
  State<ProfilScreen> createState() => _ProfilScreenState();
}

class _ProfilScreenState extends State<ProfilScreen> {
  final ProfilController _controller = ProfilController();
  late Future<Etudiant> _profilFuture;

  @override
  void initState() {
    super.initState();
    _profilFuture = _controller.fetchProfilEtudiant();
  }

  Future<void> _reload() async {
    setState(() {
      _profilFuture = _controller.fetchProfilEtudiant();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Etudiant>(
      future: _profilFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(child: Text('Erreur: ${snapshot.error}'));
        }

        final etudiant = snapshot.data;
        if (etudiant == null) {
          return const Center(child: Text('Profil introuvable'));
        }

        final initials =
            '${etudiant.prenom.isNotEmpty ? etudiant.prenom[0] : ''}${etudiant.nom.isNotEmpty ? etudiant.nom[0] : ''}';

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 36,
                        child: Text(initials.toUpperCase()),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        '${etudiant.nom} ${etudiant.prenom}',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 10),
                      ListTile(
                        leading: const Icon(Icons.email),
                        title: const Text('Email'),
                        subtitle: Text(etudiant.email),
                      ),
                      ListTile(
                        leading: const Icon(Icons.class_),
                        title: const Text('Classe'),
                        subtitle: Text(etudiant.classeNom),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
