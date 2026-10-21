# InfinityFree deployment

1. Create a hosting account and a domain/subdomain in InfinityFree.
2. In the hosting Control Panel, create a MySQL database and note the database
   host, database name, username, and password shown there.
3. Copy `includes/config.example.php` to `includes/config.local.php`. Replace
   the placeholder values with the MySQL details from the Control Panel. Keep
   `create_database` set to `false`; the hosting account must create the
   database before the application connects.
4. Upload the project files to the domain's `htdocs` directory. Do not upload
   `.gitignore`; do upload `includes/config.local.php` over FTP so the app can
   connect to its database. Never commit or publish that private config file.
5. Open the hosted site and register the first user. The application creates
   its tables automatically when it connects to the configured database.
6. Test sign in, equipment management, and the borrow/return transaction page.

The public URL is the domain assigned in the hosting account. This project
cannot be deployed or assigned a public URL until that domain and its hosting
account are configured.
