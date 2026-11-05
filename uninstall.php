<?php
/**
 * Nettoyage à la suppression du plugin (pas à la simple désactivation).
 *
 * @package GDPR_Cookie_Banner
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('gcb_options');
