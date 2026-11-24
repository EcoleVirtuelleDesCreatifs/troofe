# Verification

- Run the test suite with `APP_URL=http://localhost php artisan test`. The local application URL contains an XAMPP subdirectory; overriding it for tests prevents requests from incorrectly including that subdirectory.
- `phpunit.xml` configures SQLite in memory, the array mail transport and the synchronous queue. Do not run tests against the local `toofe` database or a live SMTP transport.
- Verify Blade compilation with `php artisan view:cache`.
- To verify route serialization, run `php artisan route:cache`, then `php artisan route:clear` so local route changes remain immediately visible.
