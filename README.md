# Data Cleaning and Analytics System (DCAS)

A web-based system to upload raw datasets, profile them, clean them, generate insights, and visualize results.

**Stack:** HTML, CSS, Bootstrap 5, Vanilla JavaScript, Chart.js, PHP (plain), MySQL.

## Setup

1. **Install** XAMPP / WAMP / MAMP (PHP 7.4+ and MySQL).
2. **Copy** the `dcas` folder into `htdocs/` (XAMPP) or your web root.
3. **Create the database**:
   - Open phpMyAdmin → Import → choose `database.sql` → Go.
   - Or run it in MySQL CLI: `mysql -u root -p < database.sql`
4. **Configure DB credentials** in `includes/db.php` if needed (default: host `localhost`, user `root`, no password, db `dcas_db`).
5. **Make `uploads/` writable** (chmod 775 on Linux/macOS).
6. Open `http://localhost/dcas/` in your browser.

## Workflow

`Upload → Profile → Clean → Analyze → Visualize`

Use **Sample Dataset** on the Upload page if you don't have your own CSV.

## Files

| File | Purpose |
|---|---|
| `index.php` | Landing / dashboard home |
| `upload.php` | Upload CSV/Excel (CSV native; Excel → save as CSV) |
| `profile.php` | Auto profiling of uploaded dataset |
| `clean.php` | Apply cleaning operations |
| `analyze.php` | Compare original vs cleaned + insights |
| `dashboard.php` | Chart.js interactive visualizations |
| `documentation.php` | System docs |
| `logic.php` | Defense-friendly system logic explanation |
| `includes/` | DB, header, footer, helpers |
| `sample/sales_sample.csv` | Sample dataset |
| `database.sql` | MySQL schema |

## Notes

- For `.xlsx` upload support without dependencies, the system asks the user to save as CSV first (a friendly notice is shown). This keeps the project framework-free and beginner-friendly for defense.
- All cleaning operations are logged in the `cleaning_logs` table.
