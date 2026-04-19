// URL de base de l'API
// Remplacez l'IP par celle de votre machine sur le réseau local
// ou utilisez 10.0.2.2 si vous testez sur émulateur Android
// L'application essaie ces URLs dans l'ordre jusqu'a trouver une reponse JSON.
const List<String> apiBaseUrls = [
  'http://10.0.2.2/backend/gest_absence_api',
  'http://10.0.2.2/gest_absence_api',
  'http://localhost/backend/gest_absence_api',
  'http://127.0.0.1/backend/gest_absence_api',
  'http://localhost/gest_absence_api',
  'http://127.0.0.1/gest_absence_api',
];

const String baseUrl = 'http://localhost/backend/gest_absence_api';
// Exemples d'URLs complètes :
// $baseUrl/auth/login.php
// $baseUrl/admin/etudiants.php
// $baseUrl/enseignant/seances.php?id=2
