# Database Setup For New Clones

Use this once after cloning to create the same local MySQL database and seed data.

## 1) Put backend in XAMPP

- Option A (recommended): from project root, run the script that creates a symlink in htdocs:
  - `powershell -ExecutionPolicy Bypass -File .\create_backend_symlink.ps1`
- Option B: manually copy backend so Apache can access it, for example:
  - `C:\xampp\htdocs\backend\gest_absence_api\`

## 2) Start services

- Start `Apache` and `MySQL` from XAMPP Control Panel.

## 3) Run installer

- Open this URL in browser:
  - `http://localhost/backend/gest_absence_api/db_setup.php`

If everything is correct, you will see:

- `Setup complete. Database 'gest_absence' is ready.`

## 4) Verify quickly (optional)

- Open `http://localhost/phpmyadmin`
- Confirm database `gest_absence` exists.
- Check tables like `utilisateurs`, `classes`, `seances`, `absences`.

## Notes

- Default local credentials used by installer:
  - host: `localhost`
  - user: `root`
  - password: `` (empty)
- The script is idempotent for seed records (safe to re-run if needed).
- The symlink script expects XAMPP at `C:\xampp\htdocs`.
- If your XAMPP path is different, pass it explicitly:
  - `powershell -ExecutionPolicy Bypass -File .\create_backend_symlink.ps1 -XamppHtdocs "D:\xampp\htdocs"`
