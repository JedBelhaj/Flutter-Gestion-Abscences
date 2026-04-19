import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/mes_seances_controller.dart';
import 'package:flutter_app/models/seance.dart';
import 'package:flutter_app/screens/enseignant/appel_screen.dart';

class MesSeancesScreen extends StatefulWidget {
  const MesSeancesScreen({super.key});

  @override
  State<MesSeancesScreen> createState() => _MesSeancesScreenState();
}

class _MesSeancesScreenState extends State<MesSeancesScreen> {
  final MesSeancesController _controller = MesSeancesController();
  late Future<List<Seance>> _seancesFuture;

  @override
  void initState() {
    super.initState();
    _seancesFuture = _controller.fetchMesSeances();
  }

  Future<void> _reload() async {
    setState(() {
      _seancesFuture = _controller.fetchMesSeances();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Seance>>(
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

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView.builder(
            padding: const EdgeInsets.all(12),
            itemCount: seances.length,
            itemBuilder: (context, index) {
              final seance = seances[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        seance.matiereNom,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 6),
                      Text('Classe: ${seance.classeNom}'),
                      Text('Date: ${seance.dateSeance}'),
                      Text('Heure: ${seance.heureDebut} - ${seance.heureFin}'),
                      const SizedBox(height: 10),
                      Align(
                        alignment: Alignment.centerRight,
                        child: ElevatedButton.icon(
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => AppelScreen(seance: seance),
                              ),
                            );
                          },
                          icon: const Icon(Icons.fact_check),
                          label: const Text('Faire l\'appel'),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }
}
