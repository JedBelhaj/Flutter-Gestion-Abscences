import 'dart:typed_data';

import 'package:flutter_app/models/absence.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

class AbsencePdfService {
  const AbsencePdfService();

  int _totalAbsences(List<Absence> absences) {
    return absences.where((item) => !item.isPresent).length;
  }

  Future<Uint8List> buildAbsencesPdf(List<Absence> absences) async {
    final document = pw.Document();
    final generatedAt = DateTime.now();
    final totalAbsences = _totalAbsences(absences);

    document.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(24),
        build: (context) {
          return [
            pw.Text(
              'Rapport des absences etudiant',
              style: pw.TextStyle(fontSize: 20, fontWeight: pw.FontWeight.bold),
            ),
            pw.SizedBox(height: 8),
            pw.Text(
              'Genere le ${generatedAt.year}-${generatedAt.month.toString().padLeft(2, '0')}-${generatedAt.day.toString().padLeft(2, '0')} ${generatedAt.hour.toString().padLeft(2, '0')}:${generatedAt.minute.toString().padLeft(2, '0')}',
            ),
            pw.SizedBox(height: 4),
            pw.Text('Total absences: $totalAbsences'),
            pw.SizedBox(height: 16),
            if (absences.isEmpty)
              pw.Text('Aucune absence trouvee')
            else
              pw.TableHelper.fromTextArray(
                headers: const ['Matiere', 'Date', 'Heure', 'Statut'],
                data: absences
                    .map(
                      (absence) => [
                        absence.matiereNom,
                        absence.dateSeance,
                        '${absence.heureDebut} - ${absence.heureFin}',
                        absence.isPresent ? 'present' : 'absent',
                      ],
                    )
                    .toList(),
                headerStyle: pw.TextStyle(
                  fontWeight: pw.FontWeight.bold,
                  color: PdfColors.white,
                ),
                headerDecoration: const pw.BoxDecoration(
                  color: PdfColors.blueGrey700,
                ),
                cellAlignment: pw.Alignment.centerLeft,
                cellPadding: const pw.EdgeInsets.symmetric(
                  horizontal: 6,
                  vertical: 4,
                ),
              ),
          ];
        },
      ),
    );

    return document.save();
  }

  Future<void> printAbsencesPdf(List<Absence> absences) async {
    final bytes = await buildAbsencesPdf(absences);
    await Printing.layoutPdf(
      name: 'absences_etudiant.pdf',
      onLayout: (_) async => bytes,
    );
  }

  // Compatibility aliases for callers using generic method names.
  Future<void> printPdf(List<Absence> absences) async {
    await printAbsencesPdf(absences);
  }

  Future<void> printPDF(List<Absence> absences) async {
    await printAbsencesPdf(absences);
  }
}
