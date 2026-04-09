<?php
declare(strict_types=1);

// Run once locally after cloning to create schema + seed data.
// Example URL: http://localhost/backend/gest_absence_api/db_setup.php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'gest_absence';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password);
    $conn->set_charset('utf8mb4');

    $conn->query(
        "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $conn->select_db($dbname);

    $tableSql = "
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','enseignant','etudiant') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    niveau VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    classe_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id)
);

CREATE TABLE IF NOT EXISTS enseignants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    specialite VARCHAR(100),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS seances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id INT NOT NULL,
    classe_id INT NOT NULL,
    matiere_id INT NOT NULL,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id),
    FOREIGN KEY (classe_id) REFERENCES classes(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
);

CREATE TABLE IF NOT EXISTS absences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seance_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    statut ENUM('present','absent') NOT NULL DEFAULT 'present',
    UNIQUE KEY unique_appel (seance_id, etudiant_id),
    FOREIGN KEY (seance_id) REFERENCES seances(id),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
);
";

    $conn->multi_query($tableSql);
    while ($conn->more_results() && $conn->next_result()) {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    }

    // Seed base users (idempotent by unique email)
    $conn->query(
        "INSERT INTO utilisateurs (nom, prenom, email, password, role) VALUES
        ('Admin','Systeme','admin@school.tn','admin123','admin'),
        ('Ben Ali','Sami','sami@school.tn','prof123','enseignant'),
        ('Trabelsi','Amine','amine@school.tn','etu123','etudiant')
        ON DUPLICATE KEY UPDATE
        nom = VALUES(nom),
        prenom = VALUES(prenom),
        password = VALUES(password),
        role = VALUES(role)"
    );

    $conn->query(
        "INSERT INTO classes (nom, niveau)
        SELECT 'CI2-A', 'Cycle Ingenieur 2'
        FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM classes WHERE nom = 'CI2-A' AND niveau = 'Cycle Ingenieur 2'
        )"
    );

    $conn->query(
        "INSERT INTO matieres (nom)
        SELECT 'Developpement Mobile' FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM matieres WHERE nom = 'Developpement Mobile')"
    );
    $conn->query(
        "INSERT INTO matieres (nom)
        SELECT 'Reseaux' FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM matieres WHERE nom = 'Reseaux')"
    );
    $conn->query(
        "INSERT INTO matieres (nom)
        SELECT 'BDD' FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM matieres WHERE nom = 'BDD')"
    );

    $conn->query(
        "INSERT INTO enseignants (utilisateur_id, specialite)
        SELECT u.id, 'Informatique'
        FROM utilisateurs u
        WHERE u.email = 'sami@school.tn'
          AND NOT EXISTS (
              SELECT 1 FROM enseignants e WHERE e.utilisateur_id = u.id
          )"
    );

    $conn->query(
        "INSERT INTO etudiants (utilisateur_id, classe_id)
        SELECT u.id, c.id
        FROM utilisateurs u
        JOIN classes c ON c.nom = 'CI2-A'
        WHERE u.email = 'amine@school.tn'
          AND NOT EXISTS (
              SELECT 1 FROM etudiants e WHERE e.utilisateur_id = u.id
          )"
    );

    $conn->close();

    header('Content-Type: text/plain; charset=utf-8');
    echo "Setup complete. Database 'gest_absence' is ready.\n";
    echo "You can now use the API with this local database.\n";
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Setup failed: ' . $e->getMessage() . "\n";
}
