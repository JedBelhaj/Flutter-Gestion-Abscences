import 'package:flutter/material.dart';
import 'package:flutter_app/controllers/absences_controller.dart';
import 'package:flutter_app/models/absence.dart';
import 'package:flutter_app/services/absence_pdf_service.dart';

class AbsencesScreen extends StatefulWidget {
  const AbsencesScreen({super.key});

  @override
  State<AbsencesScreen> createState() => _AbsencesScreenState();
}

class _AbsencesScreenState extends State<AbsencesScreen> {
  final AbsencesController _controller = AbsencesController();
  final AbsencePdfService _pdfService = const AbsencePdfService();
  late Future<List<Absence>> _absencesFuture;
  bool _isExportingPdf = false;

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

  Future<void> _exportPdf(List<Absence> absences) async {
    if (absences.isEmpty || _isExportingPdf) {
      return;
    }

    setState(() {
      _isExportingPdf = true;
    });

    try {
      await _pdfService.printAbsencesPdf(absences);
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Erreur export PDF: $e')));
    } finally {
      if (mounted) {
        setState(() {
          _isExportingPdf = false;
        });
      }
    }
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
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.warning_amber_rounded),
                        title: const Text('Total absences'),
                        trailing: Text(
                          '$totalAbsences',
                          style: Theme.of(context).textTheme.headlineSmall,
                        ),
                      ),
                      const SizedBox(height: 8),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: absences.isEmpty || _isExportingPdf
                              ? null
                              : () => _exportPdf(absences),
                          icon: _isExportingPdf
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.picture_as_pdf),
                          label: Text(
                            _isExportingPdf ? 'Export...' : 'Exporter PDF',
                          ),
                        ),
                      ),
                    ],
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
