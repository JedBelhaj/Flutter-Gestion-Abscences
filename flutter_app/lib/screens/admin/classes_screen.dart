import 'package:flutter/material.dart';
import 'package:flutter_app/services/api_service.dart';

class ClassesScreen extends StatefulWidget {
  const ClassesScreen({super.key});

  @override
  State<ClassesScreen> createState() => _ClassesScreenState();
}

class _ClassesScreenState extends State<ClassesScreen> {
  final ApiService _api = ApiService();
  late Future<List<dynamic>> _classesFuture;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _classesFuture = _api.getClasses();
  }

  void _reload() {
    setState(() {
      _classesFuture = _api.getClasses();
    });
  }

  Future<void> _confirmDeleteClasse(Map<String, dynamic> classe) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Supprimer cette classe ?'),
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
      await _api.deleteClasse(classeId: (classe['id'] as num).toInt());
      if (!mounted) {
        return;
      }
      _reload();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Classe supprimee')),
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

  Future<void> _showAddClassForm() async {
    final nomController = TextEditingController();
    final niveauController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Ajouter une classe'),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: nomController,
                  decoration: const InputDecoration(labelText: 'Nom'),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return 'Nom obligatoire';
                    }
                    return null;
                  },
                ),
                TextFormField(
                  controller: niveauController,
                  decoration: const InputDecoration(labelText: 'Niveau'),
                ),
              ],
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
                  await _api.addClasse(
                    nom: nomController.text.trim(),
                    niveau: niveauController.text.trim(),
                  );
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
              child: const Text('Ajouter'),
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
        future: _classesFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur: ${snapshot.error}'));
          }

          final classes = snapshot.data ?? [];
          if (classes.isEmpty) {
            return const Center(child: Text('Aucune classe trouvee'));
          }

          final normalizedQuery = _searchQuery.trim().toLowerCase();
          final filteredClasses = classes.where((item) {
            final classe = item as Map<String, dynamic>;
            final nom = (classe['nom'] ?? '').toString().toLowerCase();
            final niveau = (classe['niveau'] ?? '').toString().toLowerCase();
            return nom.contains(normalizedQuery) || niveau.contains(normalizedQuery);
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
                    labelText: 'Rechercher classe',
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              Expanded(
                child: filteredClasses.isEmpty
                    ? const Center(child: Text('Aucun resultat'))
                    : RefreshIndicator(
                        onRefresh: () async => _reload(),
                        child: ListView.builder(
                          itemCount: filteredClasses.length,
                          itemBuilder: (context, index) {
                            final classe =
                                filteredClasses[index] as Map<String, dynamic>;
                            return ListTile(
                              leading: const Icon(Icons.class_),
                              title: Text((classe['nom'] ?? '').toString()),
                              subtitle: Text(
                                'Niveau: ${(classe['niveau'] ?? '').toString()}',
                              ),
                              trailing: IconButton(
                                icon: const Icon(Icons.delete, color: Colors.red),
                                onPressed: () => _confirmDeleteClasse(classe),
                              ),
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
        onPressed: _showAddClassForm,
        child: const Icon(Icons.add),
      ),
    );
  }
}
