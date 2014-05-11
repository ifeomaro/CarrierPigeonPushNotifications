#CarrierPigeonPushNotifications

This push notification source code was used with the [CarrierPigeon iOS project] (https://github.com/mychrisdangelo/CarrierPigeon).

CarrierPigeon push notifications source code.

- `src/mod_push_notifications` - Erlang source code for the push notifications module.
- `src/php_src` - PHP source code for push notifications.
  - `src/php_src/apns.php` - CarrierPigeon PHP push notifications script 
  - `src/php_src/classes/` - [Easy APNS] (https://github.com/manifestinteractive/easyapns) classes.
- `src/sql` - [SQL script for push notifications database setup] (https://github.com/manifestinteractive/easyapns).



###Setting Up

- Install [XAMPP] (https://www.apachefriends.org/index.html)
- Copy your sandbox/production certificate to `src/php_src/cert`
- Copy the contents of `src/php_src` to /path/to/xampp/htdocs/
- Run the SQL script in `src/sql`
- Update `src/php_src/apns.php` with your database credentials 
- Update `src/php_src/classes/class_APNS.php` with the passphrase for your sandbox/production certificate
- Install [erlang] (https://github.com/erlang/otp)
- Install ejabberd
  - git clone https://github.com/processone/ejabberd.git
  - git checkout -b 2.0.x origin/2.0.x
  - export EJABBERD_PATH=$HOME/ejabberd/src
- Add `{mod_push_notifications, []}` to the modules section of `/etc/ejabberd/ejabberd.cfg`.
- Update `src/mod_push_notifications/mod_push_notifications.erl` with the URL for apns.php.
- Run `make` from `src/mod_push_notifications/`.
