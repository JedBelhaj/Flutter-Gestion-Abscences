import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/enseignants_controller.dart';
import 'package:flutter_app/models/utilisateur.dart';

class EnseignantsScreen extends StatefulWidget {
  const EnseignantsScreen({super.key});

  @override
  State<EnseignantsScreen> createState() => _EnseignantsScreenState();
}

class _EnseignantsScreenState extends State<EnseignantsScreen> {
  final EnseignantsController _controller = EnseignantsController();
  late Future<List<Utilisateur>> _enseignantsFuture;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _enseignantsFuture = _controller.fetchEnseignants();
  }

  void _reload() {
    setState(() {
      _enseignantsFuture = _controller.fetchEnseignants();
    });
  }

  Future<void> _confirmDeleteTeacher(Utilisateur enseignant) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Supprimer cet enseignant ?'),
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
      await _controller.deleteEnseignant(enseignant.id);
      if (!mounted) {
        return;
      }
      _reload();
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Enseignant supprime')));
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Erreur: $e')));
    }
  }

  Future<void> _showTeacherForm({Utilisateur? enseignant}) async {
    final isEdit = enseignant != null;
    final nomController = TextEditingController(text: enseignant?.nom ?? '');
    final prenomController = TextEditingController(
      text: enseignant?.prenom ?? '',
    );
    final emailController = TextEditingController(
      text: enseignant?.email ?? '',
    );
    final passwordController = TextEditingController();
    final specialiteController = TextEditingController(
      text: enseignant?.specialite ?? '',
    );
    final formKey = GlobalKey<FormState>();
    bool isPasswordVisible = false;

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(isEdit ? 'Modifier enseignant' : 'Ajouter enseignant'),
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
                        key: ValueKey('teacher_password_$isPasswordVisible'),
                        controller: passwordController,
                        decoration: InputDecoration(
                          labelText: 'Mot de passe',
                          suffixIcon: IconButton(
                            onPressed: () {
                              setDialogState(() {
                                isPasswordVisible = !isPasswordVisible;
                              });
                            },
                            icon: Icon(
                              isPasswordVisible
                                  ? Icons.visibility_off
                                  : Icons.visibility,
                            ),
                          ),
                        ),
                        obscureText: !isPasswordVisible,
                        enableSuggestions: false,
                        autocorrect: false,
                        validator: (value) {
                          if (!isEdit &&
                              (value == null || value.trim().isEmpty)) {
                            return 'Mot de passe obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: specialiteController,
                        decoration: const InputDecoration(
                          labelText: 'Specialite',
                        ),
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
                    await _controller.updateEnseignant(
                      enseignantId: enseignant.id,
                      nom: nomController.text.trim(),
                      prenom: prenomController.text.trim(),
                      email: emailController.text.trim(),
                      password: passwordController.text.trim(),
                      specialite: specialiteController.text.trim(),
                    );
                  } else {
                    await _controller.addEnseignant(
                      nom: nomController.text.trim(),
                      prenom: prenomController.text.trim(),
                      email: emailController.text.trim(),
                      password: passwordController.text.trim(),
                      specialite: specialiteController.text.trim(),
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
      body: FutureBuilder<List<Utilisateur>>(
        future: _enseignantsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur: ${snapshot.error}'));
          }

          final enseignants = snapshot.data ?? [];
          if (enseignants.isEmpty) {
            return const Center(child: Text('Aucun enseignant trouve'));
          }

          final filteredEnseignants = _controller.filterEnseignants(
            enseignants,
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
                    labelText: 'Rechercher enseignant',
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              Expanded(
                child: filteredEnseignants.isEmpty
                    ? const Center(child: Text('Aucun resultat'))
                    : RefreshIndicator(
                        onRefresh: () async => _reload(),
                        child: ListView.builder(
                          itemCount: filteredEnseignants.length,
                          itemBuilder: (context, index) {
                            final enseignant = filteredEnseignants[index];
                            return ListTile(
                              leading: const Icon(Icons.person),
                              title: Text(
                                '${enseignant.nom} ${enseignant.prenom}',
                              ),
                              subtitle: Text(
                                'Specialite: ${enseignant.specialite}',
                              ),
                              trailing: IconButton(
                                icon: const Icon(
                                  Icons.delete,
                                  color: Colors.red,
                                ),
                                onPressed: () =>
                                    _confirmDeleteTeacher(enseignant),
                              ),
                              onTap: () =>
                                  _showTeacherForm(enseignant: enseignant),
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
        heroTag: 'fab_enseignants',
        onPressed: () => _showTeacherForm(),
        child: const Icon(Icons.add),
      ),
    );
  }
}
