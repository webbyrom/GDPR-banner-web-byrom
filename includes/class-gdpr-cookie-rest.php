<?php
/**
 * Route REST pour les fronts headless :
 *   GET /wp-json/gcb/v1/config
 *
 * Ne renvoie que des identifiants publics (ils sont de toute façon
 * visibles dans le code des pages qui chargent les balises Google).
 *
 * @package GDPR_Cookie_Banner
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDPR_Cookie_REST
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('gcb/v1', '/config', [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => [$this, 'get_config'],
        ]);
    }

    public function get_config(): WP_REST_Response
    {
        $o = GDPR_Cookie_Manager::options();

        $response = new WP_REST_Response([
            'ga4'        => $o['ga4_id'],
            'ads'        => $o['ads_id'],
            'phoneLabel' => $o['ads_phone_label'],
            'days'       => (int) round($o['consent_months'] * 30.4),
            'privacyUrl' => get_privacy_policy_url(),
            'version'    => GCB_VERSION,
        ]);

        // Mise en cache 1 h par le navigateur et les CDN.
        $response->header('Cache-Control', 'public, max-age=3600');

        return $response;
    }
}
