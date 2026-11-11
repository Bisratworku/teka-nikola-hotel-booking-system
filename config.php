<?php
// Local config for hotel-management-system

define('DB_HOST', 'localhost');
define('DB_NAME', 'TekaNikola');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', '/hotel-management-system-main');

// Branding (optional)
if (!defined('HOTEL_NAME'))  define('HOTEL_NAME', 'Teka Nikola');
if (!defined('HOTEL_TAGLINE')) define('HOTEL_TAGLINE', 'Boutique stays by the river.');

// Mailer (optional)
define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
define('MAIL_PORT', 2525);
define('MAIL_USER', 'be4c62483da3dd');
define('MAIL_PASS', 'f77fe1e12b6937');
define('MAIL_FROM_ADDR', 'no-reply@yourhotel.test');
define('MAIL_FROM_NAME', 'Teka Nikola Reservations');

$HOTEL_AMENITIES = [
  ['icon'=>'🛏️','title'=>'Plush Bedding','text'=>'Down duvets, premium linens'],
  ['icon'=>'📶','title'=>'Fast Wi-Fi','text'=>'Reliable, hotel-wide'],
  ['icon'=>'☕','title'=>'Coffee & Tea','text'=>'In-room Nespresso'],
];
