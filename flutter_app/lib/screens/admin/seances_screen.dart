import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/seances_controller.dart';
import 'package:flutter_app/models/seance.dart';

class SeancesScreen extends StatefulWidget {
  const SeancesScreen({super.key});

  @override
  State<SeancesScreen> createState() => _SeancesScreenState();
}

class _SeancesScreenState extends State<SeancesScreen> {
  final SeancesController _controller = SeancesController();
  late Future<List<Seance>> _seancesFuture;
  late Future<List<Map<String, dynamic>>> _enseignantsFuture;
  late Future<List<Map<String, dynamic>>> _classesFuture;
  late Future<List<Map<String, dynamic>>> _matieresFuture;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _seancesFuture = _controller.fetchSeances();
    _enseignantsFuture = _controller.fetchEnseignants();
    _classesFuture = _controller.fetchClasses();
    _matieresFuture = _controller.fetchMatieres();
  }

  void _reload() {
    setState(() {
      _seancesFuture = _controller.fetchSeances();
      _enseignantsFuture = _controller.fetchEnseignants();
      _classesFuture = _controller.fetchClasses();
      _matieresFuture = _controller.fetchMatieres();
    });
  }

  Future<void> _confirmDeleteSeance(Seance seance) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Confirmation'),
          content: const Text('Supprimer cette seance ?'),
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
      await _controller.deleteSeance(seance.id);
      if (!mounted) {
        return;
      }
      _reload();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Seance supprimee')),
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

  String _formatDate(DateTime value) {
    final month = value.month.toString().padLeft(2, '0');
    final day = value.day.toString().padLeft(2, '0');
    return '${value.year}-$month-$day';
  }

  String _formatTime(TimeOfDay value) {
    final hour = value.hour.toString().padLeft(2, '0');
    final minute = value.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }

  Future<void> _showAssignForm() async {
    final enseignants = await _enseignantsFuture;
    final classes = await _classesFuture;
    final matieres = await _matieresFuture;

    if (!mounted) {
      return;
    }

    int? selectedEnseignantId;
    int? selectedClasseId;
    int? selectedMatiereId;
    final dateController = TextEditingController();
    final heureDebutController = TextEditingController();
    final heureFinController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Affecter une seance'),
          content: SingleChildScrollView(
            child: Form(
              key: formKey,
              child: StatefulBuilder(
                builder: (context, setDialogState) {
                  return Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      DropdownButtonFormField<int>(
                        initialValue: selectedEnseignantId,
                        decoration: const InputDecoration(
                          labelText: 'Enseignant',
                          border: OutlineInputBorder(),
                        ),
                        items: enseignants.map((item) {
                          final enseignant = item as Map<String, dynamic>;
                          return DropdownMenuItem<int>(
                            value: (enseignant['enseignant_id'] as num).toInt(),
                            child: Text(
                              '${(enseignant['nom'] ?? '').toString()} ${(enseignant['prenom'] ?? '').toString()}',
                            ),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setDialogState(() {
                            selectedEnseignantId = value;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Choisir enseignant';
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
                          if (value == null) {
                            return 'Choisir classe';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<int>(
                        initialValue: selectedMatiereId,
                        decoration: const InputDecoration(
                          labelText: 'Matiere',
                          border: OutlineInputBorder(),
                        ),
                        items: matieres.map((item) {
                          final matiere = item as Map<String, dynamic>;
                          return DropdownMenuItem<int>(
                            value: (matiere['id'] as num).toInt(),
                            child: Text((matiere['nom'] ?? '').toString()),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setDialogState(() {
                            selectedMatiereId = value;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Choisir matiere';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: dateController,
                        readOnly: true,
                        decoration: const InputDecoration(
                          labelText: 'Date (YYYY-MM-DD)',
                          suffixIcon: Icon(Icons.calendar_today),
                        ),
                        onTap: () async {
                          final selectedDate = await showDatePicker(
                            context: context,
                            initialDate: DateTime.now(),
                            firstDate: DateTime(2000),
                            lastDate: DateTime(2100),
                          );
                          if (selectedDate == null) {
                            return;
                          }
                          setDialogState(() {
                            dateController.text = _formatDate(selectedDate);
                          });
                        },
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Date obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: heureDebutController,
                        readOnly: true,
                        decoration: const InputDecoration(
                          labelText: 'Heure debut (HH:MM)',
                          suffixIcon: Icon(Icons.access_time),
                        ),
                        onTap: () async {
                          final selectedTime = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                          );
                          if (selectedTime == null) {
                            return;
                          }
                          setDialogState(() {
                            heureDebutController.text = _formatTime(selectedTime);
                          });
                        },
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Heure debut obligatoire';
                          }
                          return null;
                        },
                      ),
                      TextFormField(
                        controller: heureFinController,
                        readOnly: true,
                        decoration: const InputDecoration(
                          labelText: 'Heure fin (HH:MM)',
                          suffixIcon: Icon(Icons.access_time),
                        ),
                        onTap: () async {
                          final selectedTime = await showTimePicker(
                            context: context,
                            initialTime: TimeOfDay.now(),
                          );
                          if (selectedTime == null) {
                            return;
                          }
                          setDialogState(() {
                            heureFinController.text = _formatTime(selectedTime);
                          });
                        },
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Heure fin obligatoire';
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
                  await _controller.addSeance(
                    enseignantId: selectedEnseignantId!,
                    classeId: selectedClasseId!,
                    matiereId: selectedMatiereId!,
                    dateSeance: dateController.text.trim(),
                    heureDebut: _controller.dateTimeToApiTime(
                      heureDebutController.text.trim(),
                    ),
                    heureFin: _controller.dateTimeToApiTime(
                      heureFinController.text.trim(),
                    ),
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
              child: const Text('Affecter'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<List<Seance>>(
        future: _seancesFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Erreur: ${snapshot.error}'));
          }

          final seances = snapshot.data ?? [];
          if (seances.isEmpty) {
            return const Center(child: Text('Aucune seance trouvee'));
          }

          final filteredSeances = _controller.filterSeances(seances, _searchQuery);

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
                    labelText: 'Rechercher seance',
                    prefixIcon: Icon(Icons.search),
                    border: OutlineInputBorder(),
                  ),
                ),
              ),
              Expanded(
                child: filteredSeances.isEmpty
                    ? const Center(child: Text('Aucun resultat'))
                    : RefreshIndicator(
                        onRefresh: () async => _reload(),
                        child: ListView.builder(
                          itemCount: filteredSeances.length,
                          itemBuilder: (context, index) {
                            final seance = filteredSeances[index];
                            return ListTile(
                              leading: const Icon(Icons.schedule),
                              title: Text(
                                '${seance.matiereNom} - ${seance.classeNom}',
                              ),
                              subtitle: Text(
                                '${seance.enseignantNom} ${seance.enseignantPrenom} | ${seance.dateSeance} ${seance.heureDebut}',
                              ),
                              trailing: IconButton(
                                icon: const Icon(Icons.delete, color: Colors.red),
                                onPressed: () => _confirmDeleteSeance(seance),
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
        onPressed: _showAssignForm,
        child: const Icon(Icons.add),
      ),
    );
  }
}
