<?php
/**
 * Page de réglages : Réglages > Cookies & Google.
 *
 * @package GDPR_Cookie_Banner
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDPR_Cookie_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_page']);
        add_action('admin_init', [$this, 'register']);
        add_filter('plugin_action_links_' . plugin_basename(GCB_FILE), [$this, 'action_link']);
    }

    public function add_page(): void
    {
        add_options_page(
            __('Cookies & Google', 'gdpr-cookie-banner'),
            __('Cookies & Google', 'gdpr-cookie-banner'),
            'manage_options',
            'gcb-settings',
            [$this, 'render']
        );
    }

    public function action_link(array $links): array
    {
        array_unshift(
            $links,
            '<a href="' . esc_url(admin_url('options-general.php?page=gcb-settings')) . '">' . esc_html__('Réglages', 'gdpr-cookie-banner') . '</a>'
        );
        return $links;
    }

    public function register(): void
    {
        register_setting('gcb_settings', GDPR_Cookie_Manager::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => GDPR_Cookie_Manager::defaults(),
        ]);
    }

    public function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $out   = GDPR_Cookie_Manager::defaults();

        $ga4 = strtoupper(trim(sanitize_text_field($input['ga4_id'] ?? '')));
        if ($ga4 === '' || preg_match('/^G-[A-Z0-9]{4,}$/', $ga4)) {
            $out['ga4_id'] = $ga4;
        } else {
            add_settings_error(GDPR_Cookie_Manager::OPTION, 'ga4', __('Identifiant GA4 invalide : il doit commencer par « G- ».', 'gdpr-cookie-banner'));
        }

        $ads = strtoupper(trim(sanitize_text_field($input['ads_id'] ?? '')));
        if ($ads === '' || preg_match('/^AW-\d{6,}$/', $ads)) {
            $out['ads_id'] = $ads;
        } else {
            add_settings_error(GDPR_Cookie_Manager::OPTION, 'ads', __('Identifiant Google Ads invalide : il doit ressembler à « AW-123456789 ».', 'gdpr-cookie-banner'));
        }

        $label = trim(sanitize_text_field($input['ads_phone_label'] ?? ''));
        if ($label === '' || preg_match('/^[A-Za-z0-9_\-]{6,}$/', $label)) {
            $out['ads_phone_label'] = $label;
        } else {
            add_settings_error(GDPR_Cookie_Manager::OPTION, 'label', __('Libellé de conversion invalide.', 'gdpr-cookie-banner'));
        }

        // La CNIL recommande de conserver le choix 6 mois, 13 mois au maximum.
        $out['consent_months'] = min(13, max(1, absint($input['consent_months'] ?? 6)));

        return $out;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $o    = GDPR_Cookie_Manager::options();
        $name = GDPR_Cookie_Manager::OPTION;
?>
    <div class="wrap">
        <h1><?php esc_html_e('Cookies & Google', 'gdpr-cookie-banner'); ?></h1>
        <p><?php esc_html_e('Les balises Google ne sont chargées qu’après l’accord du visiteur (Google Consent Mode v2). Laissez un champ vide pour désactiver la balise correspondante.', 'gdpr-cookie-banner'); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields('gcb_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="gcb-ga4"><?php esc_html_e('ID Google Analytics 4', 'gdpr-cookie-banner'); ?></label></th>
                    <td>
                        <input type="text" id="gcb-ga4" class="regular-text" name="<?php echo esc_attr($name); ?>[ga4_id]" value="<?php echo esc_attr($o['ga4_id']); ?>" placeholder="G-XXXXXXXXXX">
                        <p class="description"><?php esc_html_e('Analytics > Administration > Flux de données.', 'gdpr-cookie-banner'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gcb-ads"><?php esc_html_e('ID Google Ads', 'gdpr-cookie-banner'); ?></label></th>
                    <td>
                        <input type="text" id="gcb-ads" class="regular-text" name="<?php echo esc_attr($name); ?>[ads_id]" value="<?php echo esc_attr($o['ads_id']); ?>" placeholder="AW-123456789">
                        <p class="description"><?php esc_html_e('Google Ads > Objectifs > Conversions > balise Google.', 'gdpr-cookie-banner'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gcb-phone"><?php esc_html_e('Libellé de conversion « Clic sur le téléphone »', 'gdpr-cookie-banner'); ?></label></th>
                    <td>
                        <input type="text" id="gcb-phone" class="regular-text" name="<?php echo esc_attr($name); ?>[ads_phone_label]" value="<?php echo esc_attr($o['ads_phone_label']); ?>" placeholder="AbCdEfGhIjKlMnOp">
                        <p class="description"><?php esc_html_e('Partie après le « / » dans send_to (AW-123456789/AbCdEf…). Chaque clic sur un lien tel: est alors compté comme conversion, si le visiteur a accepté la publicité.', 'gdpr-cookie-banner'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="gcb-months"><?php esc_html_e('Durée de conservation du choix', 'gdpr-cookie-banner'); ?></label></th>
                    <td>
                        <input type="number" id="gcb-months" class="small-text" min="1" max="13" name="<?php echo esc_attr($name); ?>[consent_months]" value="<?php echo esc_attr($o['consent_months']); ?>">
                        <?php esc_html_e('mois (6 recommandés par la CNIL, 13 au maximum)', 'gdpr-cookie-banner'); ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <h2><?php esc_html_e('Lien « Gérer les cookies »', 'gdpr-cookie-banner'); ?></h2>
        <p><?php esc_html_e('Le visiteur doit pouvoir modifier son choix à tout moment. Ajoutez dans le pied de page le shortcode [gestion_cookies], ou un lien personnalisé de menu pointant vers #gestion-cookies.', 'gdpr-cookie-banner'); ?></p>
    </div>
<?php
    }
}
