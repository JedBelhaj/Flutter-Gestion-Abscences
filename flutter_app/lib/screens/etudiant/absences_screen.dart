import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/absences_controller.dart';
import 'package:flutter_app/models/absence.dart';

class AbsencesScreen extends StatefulWidget {
  const AbsencesScreen({super.key});

  @override
  State<AbsencesScreen> createState() => _AbsencesScreenState();
}

class _AbsencesScreenState extends State<AbsencesScreen> {
  final AbsencesController _controller = AbsencesController();
  late Future<List<Absence>> _absencesFuture;

  @override
  void initState() {
    super.initState();
    _absencesFuture = _controller.fetchAbsencesEtudiant();
  }

  Future<void> _reload() async {
    setState(() {
      _absencesFuture = _controller.fetchAbsencesEtudiant();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Absence>>(
      future: _absencesFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(child: Text('Erreur: ${snapshot.error}'));
        }

        final absences = snapshot.data ?? [];
        final totalAbsences = _controller.totalAbsences(absences);

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.all(12),
            children: [
              Card(
                child: ListTile(
                  leading: const Icon(Icons.warning_amber_rounded),
                  title: const Text('Total absences'),
                  trailing: Text(
                    '$totalAbsences',
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              if (absences.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: Center(child: Text('Aucune absence trouvee')),
                )
              else
                ...absences.map((absence) {
                  final isPresent = absence.isPresent;
                  final statusColor = isPresent ? Colors.green : Colors.red;
                  return Card(
                    child: ListTile(
                      leading: Icon(
                        isPresent ? Icons.check_circle : Icons.cancel,
                        color: statusColor,
                      ),
                      title: Text(absence.matiereNom),
                      subtitle: Text(
                        '${absence.dateSeance} | ${absence.heureDebut} - ${absence.heureFin}',
                      ),
                      trailing: Text(
                        isPresent ? 'present' : 'absent',
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  );
                }),
            ],
          ),
        );
      },
    );
  }
}
