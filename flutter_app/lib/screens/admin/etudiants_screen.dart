import 'package:flutter/material.dart';
import 'package:flutter_app/services/api_service.dart';

class EtudiantsScreen extends StatefulWidget {
  const EtudiantsScreen({super.key});

  @override
  State<EtudiantsScreen> createState() => _EtudiantsScreenState();
}

class _EtudiantsScreenState extends State<EtudiantsScreen> {
  final ApiService _api = ApiService();
  late Future<List<dynamic>> _etudiantsFuture;
  late Future<List<dynamic>> _classesFuture;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _etudiantsFuture = _api.getEtudiants();
    _classesFuture = _api.getClasses();
  }

  void _reload() {
    setState(() {
      _etudiantsFuture = _api.getEtudiants();
      _classesFuture = _api.getClasses();
    });
  }

  Future<void> _confirmDeleteStudent(Map<String, dynamic> etudiant) async {
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
      await _api.deleteEtudiant(
        etudiantId: (etudiant['etudiant_id'] as num).toInt(),
      );
      if (!mounted) {
        return;
      }
      _reload();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Etudiant supprime')),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    }
  }

  Future<void> _showStudentForm({Map<String, dynamic>? etudiant}) async {
    final isEdit = etudiant != null;
    final nomController = TextEditingController(text: (etudiant?['nom'] ?? '').toString());
    final prenomController = TextEditingController(text: (etudiant?['prenom'] ?? '').toString());
    final emailController = TextEditingController(text: (etudiant?['email'] ?? '').toString());
    final passwordController = TextEditingController();
    int? selectedClasseId = etudiant != null
        ? (etudiant['classe_id'] as num).toInt()
        : null;
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
                          if (!isEdit && (value == null || value.trim().isEmpty)) {
                            return 'Nom obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: prenomController,
                        decoration: const InputDecoration(labelText: 'Prenom'),
                        validator: (value) {
                          if (!isEdit && (value == null || value.trim().isEmpty)) {
                            return 'Prenom obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: emailController,
                        decoration: const InputDecoration(labelText: 'Email'),
                        validator: (value) {
                          if (!isEdit && (value == null || value.trim().isEmpty)) {
                            return 'Email obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: passwordController,
                        decoration: const InputDecoration(labelText: 'Mot de passe'),
                        obscureText: true,
                        validator: (value) {
                          if (!isEdit && (value == null || value.trim().isEmpty)) {
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
                          final classe = item as Map<String, dynamic>;
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
                    await _api.updateEtudiant(
                      etudiantId: (etudiant['etudiant_id'] as num).toInt(),
                      nom: nomController.text.trim(),
                      prenom: prenomController.text.trim(),
                      email: emailController.text.trim(),
                      password: passwordController.text.trim(),
                      classeId: selectedClasseId,
                    );
                  } else {
                    await _api.addEtudiant(
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
                  ScaffoldMessenger.of(this.context).showSnackBar(
                    SnackBar(content: Text('Erreur: $e')),
                  );
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
      body: FutureBuilder<List<dynamic>>(
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

          final normalizedQuery = _searchQuery.trim().toLowerCase();
          final filteredEtudiants = etudiants.where((item) {
            final etudiant = item as Map<String, dynamic>;
            final fullName =
                '${(etudiant['nom'] ?? '').toString()} ${(etudiant['prenom'] ?? '').toString()}'
                    .toLowerCase();
            final classe = (etudiant['classe_nom'] ?? '').toString().toLowerCase();
            final email = (etudiant['email'] ?? '').toString().toLowerCase();
            return fullName.contains(normalizedQuery) ||
                classe.contains(normalizedQuery) ||
                email.contains(normalizedQuery);
          }).toList();

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
                            final etudiant =
                                filteredEtudiants[index] as Map<String, dynamic>;
                            return ListTile(
                              leading: const Icon(Icons.school),
                              title: Text(
                                '${(etudiant['nom'] ?? '').toString()} ${(etudiant['prenom'] ?? '').toString()}',
                              ),
                              subtitle: Text(
                                'Classe: ${(etudiant['classe_nom'] ?? '').toString()}',
                              ),
                              trailing: IconButton(
                                icon: const Icon(Icons.delete, color: Colors.red),
                                onPressed: () => _confirmDeleteStudent(etudiant),
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
        onPressed: () => _showStudentForm(),
        child: const Icon(Icons.add),
      ),
    );
  }
}
