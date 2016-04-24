<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'clear');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'VG(-oK^9n<7w81.0j+)|S7Cq4|}&~rGpY<La]|6+:-05#-ux%=ov+*Tn%mt.V{sT');
define('SECURE_AUTH_KEY',  'P07Lnp2NU?fm}jMo^a8sukj[-D Mx3m+1-HI9-D;TmB=sU-e|m&ScH9_l+|DjID_');
define('LOGGED_IN_KEY',    'Md(qaB#YhT$+{;GJx5Q&nM~)_wlQR~.qY:Jw0*IP&a[hndIuE!hb7HS/6)^{b13[');
define('NONCE_KEY',        'WQSrS#{YQGg#yB-F/xm#;hX`0FN9f+2; eve@];y=ypZ[qa(b%!3C^Y:|U<p(8f&');
define('AUTH_SALT',        '#-wZ2^c:@`A3tAM,}:#P&-Z/QK/gB.*#[Mpii9Jzfd)ZQV]@=MEwFwwZL/q}!k9M');
define('SECURE_AUTH_SALT', 'd-ea&m?7a]dL6}<n~^LS5-l5KAOB>[D*O9ulxQA]!ALkVM&l#!0`2lP:j`7)i+=g');
define('LOGGED_IN_SALT',   'Y[@wWr}(^T }eUpN|{l3vwRW=+jArR4T+,Cnx_@Q-~fJNW:tM+f#$gOxTXwq}=Xc');
define('NONCE_SALT',       'qgkcgh0Lx:to}V2qV]>PX*J=%<qfx]s}I`!K3Cw&|<Su]}nsE}[-o14+TR8k)(>A');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
