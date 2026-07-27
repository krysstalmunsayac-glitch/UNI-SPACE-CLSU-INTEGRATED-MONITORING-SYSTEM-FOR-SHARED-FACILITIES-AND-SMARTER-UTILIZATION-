# CLSU Project Structure Review

## Changes made
- Moved dashboard query logic from `routes/web.php` into `DashboardController`.
- Moved public home/calendar query logic into `PublicSite/HomeController`.
- Grouped Blade views into `pages`, `dashboards`, and `requests` folders.
- Simplified route middleware groups and removed the duplicate event-request POST route.
- Corrected the nonexistent `App\Models\Amenity` reference to `Amenities`.
- Rebuilt the request/amenity pivot migration and added the matching Eloquent relationship.
- Removed the legacy `Facility_ID` column from the amenities create migration.
- Removed obsolete corrective migrations that conflicted with the final schema.

## Migration alignment
- `facilities` ↔ `amenities`: many-to-many through `facility_amenity`.
- `requests` ↔ `amenities`: many-to-many through `request_facility_amenities`.
- `requests` → `facilities`: nullable `Facility_ID` foreign key.
- `requests` → `events`: nullable `Event_ID` foreign key.
- `schedules` → `requests`: one-to-one using unique `Request_ID`.
- `facility_images` → `facilities`: one-to-many.
- `facility_user`: many-to-many assignment between facilities and users.

## Important environment note
PHP syntax validation passed for all application, route, and migration files. `php artisan route:list` could not complete in this container because the PHP `mbstring` extension is unavailable (`mb_split` missing). Install/enable `mbstring` locally before running Artisan checks.

## Recommended commands
```bash
composer install
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
```
