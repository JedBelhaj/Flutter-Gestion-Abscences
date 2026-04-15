# Gest Absence Backend Documentation

This document describes how to run and use the PHP backend API in:

- backend/gest_absence_api

## 1) Overview

The backend provides APIs for three areas:

1. Authentication and token creation
2. Admin management of classes, teachers, students, and sessions
3. Teacher and student attendance flows

Main folders:

- auth: login and token-based identity endpoint
- admin: protected CRUD-like endpoints for admin role
- enseignant: teacher endpoints (sessions and attendance)
- etudiant: student endpoints (profile and absences)
- config: shared DB and helper functions

Base URL (local default):

- http://localhost/backend/gest_absence_api

## 2) Requirements

- PHP with mysqli extension
- MySQL (XAMPP is expected by current setup scripts)
- Apache serving the backend path above

## 3) Quick Start

1. Place backend under Apache htdocs (symlink script recommended):
   - powershell -ExecutionPolicy Bypass -File .\create_backend_symlink.ps1
2. Start Apache and MySQL in XAMPP.
3. Run installer once:
   - http://localhost/backend/gest_absence_api/db_setup.php
4. Optional manual API page:
   - http://localhost/backend/gest_absence_api/index.php
5. Interactive OpenAPI page:
   - http://localhost/backend/gest_absence_api/docs/

## 4) Database and Seed Data

Database name:

- gest_absence

Core tables:

- utilisateurs
- classes
- etudiants
- enseignants
- matieres
- seances
- absences

Default seeded users:

- admin@school.tn / admin123 (role: admin)
- sami@school.tn / prof123 (role: enseignant)
- amine@school.tn / etu123 (role: etudiant)

## 5) Authentication Model

### 5.1 Token generation

POST /auth/login.php with email and password returns:

- success
- token
- user

Token format is custom signed payload (HMAC SHA-256), not JWT.

### 5.2 Protected endpoints

Admin endpoints call shared requireRole(...) and accept token by:

1. Authorization header: Bearer <token>
2. Query/body key token=<token>

Note: In some local Apache setups, Authorization header is not forwarded. In that case use token query parameter.

### 5.3 Current behavior by area

- auth/\*: token-aware
- admin/\*: requires admin token
- enseignant/_ and etudiant/_: currently identity is passed using ids (enseignant_id, etudiant_id, utilisateur_id/token fallback), not role token checks

This is functional for local testing but weaker from a security perspective.

## 6) Common Response Shape

Most endpoints return JSON:

- success: boolean
- message: optional string
- data: object or array

Error responses use appropriate HTTP codes such as 400, 401, 403, 404, 405, 500.

## 7) Endpoint Reference

## 7.1 Auth

### POST /auth/login.php

Purpose:

- Login with credentials and return token

Body:

- email: string (required)
- password: string (required)

Success:

- 200 with token and user

Failure:

- 400 invalid/missing fields
- 401 invalid credentials

Example:

- POST /auth/login.php
- {"email":"admin@school.tn","password":"admin123"}

### GET /auth/login.php

Purpose:

- Validate current token and return current user

Auth:

- Bearer token or token query parameter

Success:

- 200

Failure:

- 401 invalid or missing token

## 7.2 Admin (requires admin token)

All endpoints below require admin token.

### GET /admin/classes.php

Query:

- classe_id: int (optional)

Behavior:

- no classe_id: list all classes
- with classe_id: fetch one class

### POST /admin/classes.php

Create mode (no classe_id):

- nom: string (required)
- niveau: string (optional)

Update mode (with classe_id):

- classe_id: int (required)
- nom: string (optional)
- niveau: string (optional)

### GET /admin/enseignants.php

Query:

- enseignant_id: int (optional)

Behavior:

- no enseignant_id: list all teachers
- with enseignant_id: fetch one teacher

### POST /admin/enseignants.php

Create mode (no enseignant_id):

- nom: string (required)
- prenom: string (required)
- email: valid email (required)
- password: string (required)
- specialite: string (optional)

Update mode (with enseignant_id):

- enseignant_id: int (required)
- nom: string (optional)
- prenom: string (optional)
- email: valid email (optional)
- password: string (optional)
- specialite: string (optional)

### GET /admin/etudiants.php

Query:

- etudiant_id: int (optional)
- classe_id: int (optional)

Behavior:

- etudiant_id: fetch one student
- else list students, optionally filtered by classe_id

### POST /admin/etudiants.php

Create mode (no etudiant_id):

- nom: string (required)
- prenom: string (required)
- email: valid email (required)
- password: string (required)
- classe_id: int (required)

Update mode (with etudiant_id):

- etudiant_id: int (required)
- nom: string (optional)
- prenom: string (optional)
- email: valid email (optional)
- password: string (optional)
- classe_id: int (optional)

### GET /admin/seances.php

Query (all optional):

- enseignant_id: int
- classe_id: int
- date or date_seance: YYYY-MM-DD

Behavior:

- list sessions with optional filters

### POST /admin/seances.php

Create mode (no seance_id):

- enseignant_id: int (required)
- classe_id: int (required)
- matiere_id: int (required)
- date_seance: YYYY-MM-DD (required)
- heure_debut: HH:MM or HH:MM:SS (required)
- heure_fin: HH:MM or HH:MM:SS (required)

Update mode (with seance_id):

- seance_id: int (required)
- enseignant_id: int (optional)
- classe_id: int (optional)
- matiere_id: int (optional)
- date_seance: YYYY-MM-DD (optional)
- heure_debut: HH:MM or HH:MM:SS (optional)
- heure_fin: HH:MM or HH:MM:SS (optional)

## 7.3 Teacher endpoints

### GET /enseignant/seances.php

Query:

- enseignant_id: int (recommended)
- or utilisateur_id/token fallback
- date_from: YYYY-MM-DD (optional)
- date_to: YYYY-MM-DD (optional)

Returns teacher sessions.

### GET /enseignant/absences.php

Query:

- seance_id: int (required)
- enseignant_id: int (required unless utilisateur fallback)

Returns attendance list for students of session class.

### POST /enseignant/absences.php

Body:

- enseignant_id: int (required unless utilisateur fallback)
- seance_id: int (required)
- absences: array (required)

Each absences item:

- etudiant_id: int
- statut: present | absent

Uses upsert behavior on (seance_id, etudiant_id).

## 7.4 Student endpoints

### GET /etudiant/profil.php

Query:

- etudiant_id: int (recommended)
- or utilisateur_id/token fallback

Returns student profile with class info.

### GET /etudiant/absences.php

Query:

- etudiant_id: int (recommended)
- or utilisateur_id/token fallback
- date_from: YYYY-MM-DD (optional)
- date_to: YYYY-MM-DD (optional)
- matiere_id: int (optional)

Returns student absences with session and subject details.

## 8) Practical Examples

Swagger/OpenAPI UI for direct API testing:

- http://localhost/backend/gest_absence_api/docs/
- Spec URL: http://localhost/backend/gest_absence_api/docs/openapi.json

### 8.1 Login as admin

POST http://localhost/backend/gest_absence_api/auth/login.php

Body:

{"email":"admin@school.tn","password":"admin123"}

### 8.2 List classes using token query fallback

GET http://localhost/backend/gest_absence_api/admin/classes.php?token=<ADMIN_TOKEN>

### 8.3 Create a class

POST http://localhost/backend/gest_absence_api/admin/classes.php?token=<ADMIN_TOKEN>

Body:

{"nom":"CI2-B","niveau":"Cycle Ingenieur 2"}

## 9) Automated Full Backend Test

A full integration test script is available:

- backend/gest_absence_api/run_full_backend_tests.ps1

Run from repository root:

- powershell -ExecutionPolicy Bypass -File .\backend\gest_absence_api\run_full_backend_tests.ps1

Custom base URL:

- powershell -ExecutionPolicy Bypass -File .\backend\gest_absence_api\run_full_backend_tests.ps1 -BaseUrl "http://localhost/backend/gest_absence_api"

What it validates:

- DB setup endpoint
- Auth invalid and valid login
- Admin CRUD flows (classes, teachers, students, sessions)
- Teacher attendance write/read
- Student profile and absences visibility

## 10) Troubleshooting

### Unauthorized on admin endpoints

- Ensure token is sent.
- If Bearer header is ignored by Apache, pass token in query parameter.

### Database connection failed

- Confirm MySQL is running.
- Check credentials in config/database.php environment values or defaults.

### 404 endpoints

- Verify Apache path maps to /backend/gest_absence_api.
- Re-run symlink script if needed.

## 11) Recommended Next Improvements

1. Enforce token auth on enseignant and etudiant endpoints (not id-only).
2. Move to strict JWT standard if needed for interoperability.
3. Add delete endpoints where required by product needs.
4. Add OpenAPI spec for client generation and contract validation.
