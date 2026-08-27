# Campus Digital Hub

A procedural PHP/MySQL digital asset store for XAMPP. The first milestone includes a responsive MDBootstrap storefront, search/filter catalog, product detail, session cart, checkout order creation, registration/login, account history, support tickets, CSRF protection, password hashing, prepared statements, and an RBAC-gated admin dashboard.

Account management includes a personalized dashboard at `account.php`, profile editing and deletion at `profile.php`, authenticated password changes at `change_password.php`, and one-hour token-based password recovery through `forgot_password.php`.

## Run locally

1. Start Apache and MySQL in XAMPP.
2. Open phpMyAdmin and import `database.sql`.
3. Visit `http://localhost/Campus_Hub/`.
4. Create a user account. To grant admin access, run this SQL after registration:

```sql
UPDATE users SET role = 'super_admin' WHERE email = 'your-email@example.com';
```

If the database was imported before account recovery was added, run the `password_resets` table statement from `database.sql` before using password reset.

## Configuration

Database credentials, `APP_URL`, and session helpers live in `config.php`. Set them for your environment before deployment. Bangladesh checkout uses SSLCOMMERZ with BDT and supports cards, bKash, Nagad, Rocket, and supported banking channels. Add `SSLCOMMERZ_STORE_ID` and `SSLCOMMERZ_STORE_PASSWORD` from your merchant account, set `SSLCOMMERZ_SANDBOX` to `false` for live payments, and set `APP_URL` to your public HTTPS domain. Bank transfer orders remain pending for admin verification. Digital files should live outside the public web root or be served through an authenticated download endpoint.

## Production hardening checklist

- Move database secrets to environment variables.
- Add HTTPS, secure/HttpOnly/SameSite session cookie settings, rate limiting, and email-based password reset tokens.
- Implement gateway webhooks with signature verification and idempotency keys.
- Add authenticated download streaming with ownership checks and expiring links.
- Store uploaded assets outside the public directory and validate MIME, size, and extension.
- Add automated tests and a deployment-specific error log policy.
