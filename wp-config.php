<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', '123consulting_db');

/** MySQL database username */
define('DB_USER', '123consuluser');

/** MySQL database password */
define('DB_PASSWORD', '123consulPass');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'ekX&bqT]y[E+_#UQ>6q(<#nqBAhSxb?{fO?$3]G33fBNSBNUj2As.:8NZY*UgInj');
define('SECURE_AUTH_KEY',  '7PmA^[r #0y?+pie(=9HpfLYO08]D{X9LkJ)DG.jSjaK5cXV780++Wqe}Q=X!RdF');
define('LOGGED_IN_KEY',    '*Ry#WJ/hn#Imzpy*R|2|NrR.}4IgjjUkxr X%)o;ilcf8>9!bq-|m!!gFf+mGP{Y');
define('NONCE_KEY',        'R2]FX8!v@6,(YKWIajfpC=zk88(fJl7#(c2@mBCZTcPaWnJGe>i>T#prFz4{dTw_');
define('AUTH_SALT',        '79v><JU%5[!Id`yyuEe|ToQKJ~by.PoPZz(Q(sv[F>7cso&|~F;<q]|{+O*eT[v~');
define('SECURE_AUTH_SALT', '+RkJt;I4v->LGrquU5MxfFgeX~8,TdZ=7!g3S?4OBQvWw6+.)T{QY96>uR#+on!<');
define('LOGGED_IN_SALT',   '~KeToL6Lb%vR TAW!<?6CbjA(|o]vLb}|,,<DS-c>W89:|~<_QJUXz!?{Yvt2%hw');
define('NONCE_SALT',       'm?|q&M|C=qCQl1q(K.z|TOE+qSVunlYc5-e+]z5N_@U),pIg02+ay>L.hnR{m8yN');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
