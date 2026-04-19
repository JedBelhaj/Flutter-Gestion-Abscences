import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/appel_controller.dart';
import 'package:flutter_app/models/etudiant.dart';
import 'package:flutter_app/models/seance.dart';

class AppelScreen extends StatefulWidget {
  final Seance seance;

  const AppelScreen({super.key, required this.seance});

  @override
  State<AppelScreen> createState() => _AppelScreenState();
}

class _AppelScreenState extends State<AppelScreen> {
  final AppelController _controller = AppelController();
  late Future<List<Etudiant>> _etudiantsFuture;
  final Map<int, bool> _presenceByEtudiantId = {};
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _etudiantsFuture = _controller.fetchEtudiantsPourSeance(widget.seance);
  }

  Future<void> _reload() async {
    setState(() {
      _etudiantsFuture = _controller.fetchEtudiantsPourSeance(widget.seance);
      _presenceByEtudiantId.clear();
    });
  }

  Future<void> _validerAppel(List<Etudiant> etudiants) async {
    setState(() {
      _saving = true;
    });

    try {
      final payload = etudiants
          .map(
            (item) => item.copyWith(
              statut: (_presenceByEtudiantId[item.etudiantId] ?? item.isPresent)
                  ? 'present'
                  : 'absent',
            ),
          )
          .toList();

      await _controller.validerAppel(seance: widget.seance, etudiants: payload);

      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Appel valide avec succes')));
      Navigator.of(context).pop();
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
          _saving = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Appel - ${widget.seance.matiereNom}')),
      body: FutureBuilder<List<Etudiant>>(
        future: _etudiantsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur: ${snapshot.error}'));
          }

          final etudiants = snapshot.data ?? [];
          if (etudiants.isEmpty) {
            return const Center(
              child: Text('Aucun etudiant pour cette classe'),
            );
          }

          return Column(
            children: [
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _reload,
                  child: ListView.builder(
                    itemCount: etudiants.length,
                    itemBuilder: (context, index) {
                      final etudiant = etudiants[index];
                      final checked =
                          _presenceByEtudiantId[etudiant.etudiantId] ??
                          etudiant.isPresent;

                      return CheckboxListTile(
                        title: Text(etudiant.fullName),
                        subtitle: Text('ID: ${etudiant.etudiantId}'),
                        value: checked,
                        onChanged: (value) {
                          if (value == null) {
                            return;
                          }
                          setState(() {
                            _presenceByEtudiantId[etudiant.etudiantId] = value;
                          });
                        },
                      );
                    },
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(12),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _saving ? null : () => _validerAppel(etudiants),
                    icon: const Icon(Icons.save),
                    label: Text(_saving ? 'Validation...' : 'Valider l\'appel'),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}
