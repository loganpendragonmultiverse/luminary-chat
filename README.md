# Luminary Chat

**Gold · v2.1.1 · released as-is.** Future features and maintenance are optional.

Luminary Chat is a character chat app you can host yourself. It uses your own Venice or OpenRouter keys and includes separate characters, conversations, editable memory, rooms, import and export, and a 3D companion home.

Luminary Chat began as a Forge lab. I tried to market it, but it wasn't gaining traction. With so many character chat options already out there, I couldn't justify the time it would take to keep pushing it, so I decided to open-source it.

## Install

Requires PHP 8.3+ (PDO SQLite, mbstring, OpenSSL, fileinfo, DOM/XML, curl), Composer 2 and Node.js 22.12+ with npm. Linux, macOS and Windows with those runtimes are supported.

```sh
git clone https://github.com/loganpendragonmultiverse/luminary-chat.git
cd luminary-chat
php install.php
php artisan app:create-admin
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://localhost:8000. Shell and PowerShell launchers are supplied. Installation creates a new encryption key and empty SQLite database. No default account is shipped. An existing .env and key are preserved; review configuration before rerunning migrations.

## Deploy

Serve public/ only. Set APP_ENV=production, APP_DEBUG=false, APP_URL to your HTTPS origin, SESSION_SECURE_COOKIE=true, CONTACT_EMAIL and Laravel SMTP settings in private .env. Grant the service user write access to storage/ and bootstrap/cache. Keep database/ private and backed up. Run php artisan storage:link for uploads. Build with npm ci && npm run build. Customize instance policies and contact details before public hosting.

MAIL_MAILER=log is a local default: reset/verification messages appear in private storage/logs. Configure SMTP before accepting public users. No email is delivered by default.

Enter your own provider keys in Account settings. They are encrypted using APP_KEY. External chat/image calls may cost money and need an available model. Luminary, Mira and Aurora are generic fictional adult defaults with no private history or pre-existing relationship. Companion Mode requires WebGL and a modern browser.

## Development

```sh
php artisan test
npm run build
```

See DEVELOPMENT.md, TESTING.md, PRIVACY.md, SECURITY.md, CHANGELOG.md and THIRD_PARTY_NOTICES.md.
Application code is MIT; attributed assets retain their own licenses.
