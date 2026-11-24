# Verification

- Run the test suite with `APP_URL=http://localhost php artisan test`. The local application URL contains an XAMPP subdirectory; overriding it for tests prevents requests from incorrectly including that subdirectory.
- `phpunit.xml` configures SQLite in memory, the array mail transport and the synchronous queue. Do not run tests against the local `toofe` database or a live SMTP transport.
- Verify Blade compilation with `php artisan view:cache`.
- To verify route serialization, run `php artisan route:cache`, then `php artisan route:clear` so local route changes remain immediately visible.

# cPanel deployment

- The cPanel clone is `/home3/troofeak/repositories/troofe`; the primary domain document root is `/home3/troofeak/public_html`. The domain uses EasyApache PHP 8.3. Interactive SSH is disabled; CLI PHP and Composer availability for deployment tasks must still be verified on the host.
- `.cpanel.yml` runs `deployment/cpanel.sh`. It builds isolated private releases, retains the source repository's private `.env` and persistent storage, and publishes only the public files with a generated entry point. Do not copy the whole repository into `public_html`.
- Deployment checks require an initialized schema and production plans. Migrations, demo seeding, mail workers and scheduled tasks are deliberately not run by the deployment recipe; obtain explicit approval for those operations separately.
- Run `APP_URL=http://localhost php artisan test --filter=CpanelDeploymentTest` for publication safety checks and `bash -n deployment/cpanel.sh` for shell syntax.
- Before a traffic cutover, verify the domain's current DNS, any proxy, and the target certificate. A DNS lookup during preparation returned `64.247.178.103`, whereas the cPanel shared IP supplied by the user was `199.79.62.121`; do not assume they are interchangeable or change DNS automatically.
