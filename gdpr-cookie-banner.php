<?php
/**
 * Plugin Name: GDPR Cookie Banner
 * Description: Bandeau de consentement aux cookies conforme aux recommandations de la CNIL, avec Google Consent Mode v2 (Google Analytics 4 et Google Ads), avec route REST pour les fronts headless.
 * Author:      Romain Fourel
 * Author URI:  https://www.web-byrom.com/
 * Version:     2.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gdpr-cookie-banner
 *
 * @package GDPR_Cookie_Banner
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier
}

define('GCB_VERSION', '2.1.0');
define('GCB_FILE', __FILE__);
define('GCB_DIR', plugin_dir_path(__FILE__));
define('GCB_URL', plugin_dir_url(__FILE__));

require_once GCB_DIR . 'includes/class-gdpr-cookie-manager.php';
require_once GCB_DIR . 'includes/class-gdpr-cookie-rest.php';

if (is_admin()) {
    require_once GCB_DIR . 'includes/class-gdpr-cookie-settings.php';
}

add_action('plugins_loaded', function () {
    new GDPR_Cookie_Manager();
    new GDPR_Cookie_REST();

    if (is_admin()) {
        new GDPR_Cookie_Settings();
    }
});
