import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/etudiants_controller.dart';
import 'package:flutter_app/models/etudiant.dart';

class EtudiantsScreen extends StatefulWidget {
  const EtudiantsScreen({super.key});

  @override
  State<EtudiantsScreen> createState() => _EtudiantsScreenState();
}

class _EtudiantsScreenState extends State<EtudiantsScreen> {
  final EtudiantsController _controller = EtudiantsController();
  late Future<List<Etudiant>> _etudiantsFuture;
  late Future<List<Map<String, dynamic>>> _classesFuture;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _etudiantsFuture = _controller.fetchEtudiants();
    _classesFuture = _controller.fetchClasses();
  }

  void _reload() {
    setState(() {
      _etudiantsFuture = _controller.fetchEtudiants();
      _classesFuture = _controller.fetchClasses();
    });
  }

  Future<void> _confirmDeleteStudent(Etudiant etudiant) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Supprimer cet etudiant ?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Supprimer'),
            ),
          ],
        );
      },
    );

    if (shouldDelete != true) {
      return;
    }

    try {
      await _controller.deleteEtudiant(etudiant.id);
      if (!mounted) {
        return;
      }
      _reload();
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Etudiant supprime')));
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Erreur: $e')));
    }
  }

  Future<void> _showStudentForm({Etudiant? etudiant}) async {
    final isEdit = etudiant != null;
    final nomController = TextEditingController(text: etudiant?.nom ?? '');
    final prenomController = TextEditingController(
      text: etudiant?.prenom ?? '',
    );
    final emailController = TextEditingController(text: etudiant?.email ?? '');
    final passwordController = TextEditingController();
    int? selectedClasseId = etudiant?.classeId;
    final formKey = GlobalKey<FormState>();

    final classes = await _classesFuture;

    if (!mounted) {
      return;
    }

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(isEdit ? 'Modifier etudiant' : 'Ajouter etudiant'),
          content: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: StatefulBuilder(
                builder: (context, setDialogState) {
                  return Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      TextFormField(
                        controller: nomController,
                        decoration: const InputDecoration(labelText: 'Nom'),
                        validator: (value) {
                          if (!isEdit &&
                              (value == null || value.trim().isEmpty)) {
                            return 'Nom obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: prenomController,
                        decoration: const InputDecoration(labelText: 'Prenom'),
                        validator: (value) {
                          if (!isEdit &&
                              (value == null || value.trim().isEmpty)) {
                            return 'Prenom obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: emailController,
                        decoration: const InputDecoration(labelText: 'Email'),
                        validator: (value) {
                          if (!isEdit &&
                              (value == null || value.trim().isEmpty)) {
                            return 'Email obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: passwordController,
                        decoration: const InputDecoration(
                          labelText: 'Mot de passe',
                        ),
                        obscureText: true,
                        validator: (value) {
                          if (!isEdit &&
                              (value == null || value.trim().isEmpty)) {
                            return 'Mot de passe obligatoire';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<int>(
                        initialValue: selectedClasseId,
                        decoration: const InputDecoration(
                          labelText: 'Classe',
                          border: OutlineInputBorder(),
                        ),
                        items: classes.map((item) {
                          final classe = item;
                          return DropdownMenuItem<int>(
                            value: (classe['id'] as num).toInt(),
                            child: Text((classe['nom'] ?? '').toString()),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setDialogState(() {
                            selectedClasseId = value;
                          });
                        },
                        validator: (value) {
                          if (!isEdit && value == null) {
                            return 'Classe obligatoire';
                          }
                          return null;
                        },
                      ),
                    ],
                  );
                },
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () async {
                if (!formKey.currentState!.validate()) {
                  return;
                }

                try {
                  if (isEdit) {
                    await _controller.updateEtudiant(
                      etudiantId: etudiant.id,
                      nom: nomController.text.trim(),
                      prenom: prenomController.text.trim(),
                      email: emailController.text.trim(),
                      password: passwordController.text.trim(),
                      classeId: selectedClasseId,
                    );
                  } else {
                    await _controller.addEtudiant(
                      nom: nomController.text.trim(),
                      prenom: prenomController.text.trim(),
                      email: emailController.text.trim(),
                      password: passwordController.text.trim(),
                      classeId: selectedClasseId!,
                    );
                  }

                  if (!mounted) {
                    return;
                  }
                  Navigator.of(this.context).pop();
                  _reload();
                } catch (e) {
                  if (!mounted) {
                    return;
                  }
                  ScaffoldMessenger.of(
                    this.context,
                  ).showSnackBar(SnackBar(content: Text('Erreur: $e')));
                }
              },
              child: Text(isEdit ? 'Modifier' : 'Ajouter'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
            return const Center(child: Text('Aucun etudiant trouve'));
          }

          final filteredEtudiants = _controller.filterEtudiants(
            etudiants,
            _searchQuery,
          );

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
                child: TextField(
                  onChanged: (value) {
                    setState(() {
                      _searchQuery = value;
                    });
                  },
                  decoration: const InputDecoration(
                    labelText: 'Rechercher etudiant',
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              Expanded(
                child: filteredEtudiants.isEmpty
                    ? const Center(child: Text('Aucun resultat'))
                    : RefreshIndicator(
                        onRefresh: () async => _reload(),
                        child: ListView.builder(
                          itemCount: filteredEtudiants.length,
                          itemBuilder: (context, index) {
                            final etudiant = filteredEtudiants[index];
                            return ListTile(
                              leading: const Icon(Icons.school),
                              title: Text('${etudiant.nom} ${etudiant.prenom}'),
                              subtitle: Text('Classe: ${etudiant.classeNom}'),
                              trailing: IconButton(
                                icon: const Icon(
                                  Icons.delete,
                                  color: Colors.red,
                                ),
                                onPressed: () =>
                                    _confirmDeleteStudent(etudiant),
                              ),
                              onTap: () => _showStudentForm(etudiant: etudiant),
                            );
                          },
                        ),
                      ),
              ),
            ],
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'fab_etudiants',
        onPressed: () => _showStudentForm(),
        child: const Icon(Icons.add),
      ),
    );
  }
}
