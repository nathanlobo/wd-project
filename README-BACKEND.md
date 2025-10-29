Codegram — Backend setup

1) Install PHP + MySQL
- Windows: install XAMPP or WampServer. XAMPP includes Apache, PHP and MySQL/MariaDB.

2) Import database
- Open phpMyAdmin or mysql CLI and run/import `install.sql`.

3) Configure DB connection
- Edit `includes/db.php` and set DB_HOST, DB_USER, DB_PASS to match your environment.

4) Create Media folders
- Create `Media/uploads/` and `Media/profiles/` in the project root and make them writable by the web server.

5) Run site
- With XAMPP: put this project in `htdocs` and open http://localhost/your-folder/login.php
- Or run built-in PHP server for basic testing (from project root):

  php -S 127.0.0.1:8000

  Then open http://127.0.0.1:8000/login.php

6) Notes
- This is a starter prototype. For production you must harden session/cookie settings, file upload validation and permissions, implement CSRF tokens, proper error handling and rate limits.
