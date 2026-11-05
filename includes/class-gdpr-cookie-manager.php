<?php
/**
 * Gestion du consentement :
 * - Google Consent Mode v2 (tout refusé par défaut)
 * - Chargement des balises Google uniquement après accord
 * - Bandeau et panneau de préférences
 * - Shortcode [gestion_cookies]
 *
 * Le choix du visiteur est lu et écrit côté navigateur : le bandeau
 * fonctionne donc aussi avec un plugin de cache de pages.
 *
 * @package GDPR_Cookie_Banner
 */

if (!defined('ABSPATH')) {
    exit;
}

class GDPR_Cookie_Manager
{
    const OPTION = 'gcb_options';
    const COOKIE = 'gcb_consent';

    public static function defaults(): array
    {
        return [
            'ga4_id'          => '',
            'ads_id'          => '',
            'ads_phone_label' => '',
            'consent_months'  => 6,
        ];
    }

    public static function options(): array
    {
        return wp_parse_args((array) get_option(self::OPTION, []), self::defaults());
    }

    public function __construct()
    {
        // Priorité 1 : le consentement par défaut doit précéder toute balise Google.
        add_action('wp_head', [$this, 'print_consent_script'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_banner']);
        add_shortcode('gestion_cookies', [$this, 'shortcode_manage_link']);
    }

    /**
     * Consent Mode v2 + lecture du choix déjà enregistré + chargement des balises.
     */
    public function print_consent_script(): void
    {
        $o = self::options();

        $config = [
            'ga4'        => $o['ga4_id'],
            'ads'        => $o['ads_id'],
            'phoneLabel' => $o['ads_phone_label'],
            'cookie'     => self::COOKIE,
            'days'       => (int) round($o['consent_months'] * 30.4),
        ];
?>
<script id="gcb-consent-mode">
(function () {
    var cfg = <?php echo wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

    // Par défaut : tout est refusé tant que le visiteur n'a pas fait de choix.
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        functionality_storage: 'granted',
        security_storage: 'granted',
        wait_for_update: 500
    });
    gtag('set', 'ads_data_redaction', true);

    var state = { loaded: false, ga4: false, ads: false };

    // Charge gtag.js et configure uniquement les balises autorisées.
    function loadTags(c) {
        var wantGa4 = c.analytics && cfg.ga4 && !state.ga4;
        var wantAds = c.marketing && cfg.ads && !state.ads;
        if (!wantGa4 && !wantAds) { return; }

        if (!state.loaded) {
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(wantGa4 ? cfg.ga4 : cfg.ads);
            document.head.appendChild(s);
            gtag('js', new Date());
            state.loaded = true;
        }
        if (wantGa4) { gtag('config', cfg.ga4); state.ga4 = true; }
        if (wantAds) { gtag('config', cfg.ads); state.ads = true; }
    }

    function read() {
        var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + cfg.cookie + '=([^;]*)'));
        if (!m) { return null; }
        try {
            var c = JSON.parse(decodeURIComponent(m[1]));
            if (!c || c.v !== 1) { return null; }
            return { analytics: c.a === 1, marketing: c.m === 1 };
        } catch (e) {
            return null;
        }
    }

    function apply(c) {
        gtag('consent', 'update', {
            analytics_storage: c.analytics ? 'granted' : 'denied',
            ad_storage: c.marketing ? 'granted' : 'denied',
            ad_user_data: c.marketing ? 'granted' : 'denied',
            ad_personalization: c.marketing ? 'granted' : 'denied'
        });
        loadTags(c);
    }

    window.gcb = { cfg: cfg, read: read, apply: apply, consent: read() };
    if (window.gcb.consent) { apply(window.gcb.consent); }
})();
</script>
<?php
    }

    public function enqueue_assets(): void
    {
        wp_enqueue_style(
            'gdpr-cookie-banner',
            GCB_URL . 'assets/css/cookie-banner.css',
            [],
            GCB_VERSION
        );

        wp_enqueue_script(
            'gdpr-cookie-banner',
            GCB_URL . 'assets/js/cookie-banner.js',
            [],
            GCB_VERSION,
            true
        );
    }

    /**
     * Bandeau toujours présent dans le HTML (masqué) : le script décide de l'afficher.
     */
    public function render_banner(): void
    {
        $o       = self::options();
        $privacy = get_privacy_policy_url();
?>
<div id="gcb-banner" class="gcb" role="dialog" aria-modal="false" aria-labelledby="gcb-title" aria-describedby="gcb-desc" hidden>
    <div class="gcb__inner">
        <p id="gcb-title" class="gcb__title"><?php esc_html_e('Vos choix concernant les cookies', 'gdpr-cookie-banner'); ?></p>

        <p id="gcb-desc" class="gcb__text">
            <?php esc_html_e('Avec votre accord, nous utilisons des cookies pour mesurer la fréquentation du site et l’efficacité de nos publicités (Google Analytics, Google Ads). Vous pouvez accepter, refuser ou personnaliser vos choix, et les modifier à tout moment via le lien « Gérer les cookies ».', 'gdpr-cookie-banner'); ?>
            <?php if ($privacy) : ?>
                <a href="<?php echo esc_url($privacy); ?>"><?php esc_html_e('En savoir plus', 'gdpr-cookie-banner'); ?></a>
            <?php endif; ?>
        </p>

        <div id="gcb-preferences" class="gcb__prefs" hidden>
            <div class="gcb__pref">
                <label>
                    <input type="checkbox" checked disabled>
                    <?php esc_html_e('Cookies nécessaires (toujours actifs)', 'gdpr-cookie-banner'); ?>
                </label>
                <p>
                    <?php
                    printf(
                        /* translators: %d : durée en mois */
                        esc_html__('Indispensables au fonctionnement du site et à la mémorisation de vos choix pendant %d mois.', 'gdpr-cookie-banner'),
                        (int) $o['consent_months']
                    );
                    ?>
                </p>
            </div>

            <div class="gcb__pref">
                <label for="gcb-analytics">
                    <input type="checkbox" id="gcb-analytics">
                    <?php esc_html_e('Mesure d’audience (Google Analytics)', 'gdpr-cookie-banner'); ?>
                </label>
                <p><?php esc_html_e('Comprendre comment le site est utilisé afin de l’améliorer.', 'gdpr-cookie-banner'); ?></p>
            </div>

            <div class="gcb__pref">
                <label for="gcb-marketing">
                    <input type="checkbox" id="gcb-marketing">
                    <?php esc_html_e('Publicité (Google Ads)', 'gdpr-cookie-banner'); ?>
                </label>
                <p><?php esc_html_e('Mesurer les demandes de contact issues de nos annonces et vous proposer des publicités pertinentes.', 'gdpr-cookie-banner'); ?></p>
            </div>
        </div>

        <div class="gcb__actions">
            <button type="button" id="gcb-refuse" class="gcb__btn"><?php esc_html_e('Tout refuser', 'gdpr-cookie-banner'); ?></button>
            <button type="button" id="gcb-customize" class="gcb__btn gcb__btn--ghost" aria-expanded="false" aria-controls="gcb-preferences"><?php esc_html_e('Personnaliser', 'gdpr-cookie-banner'); ?></button>
            <button type="button" id="gcb-save" class="gcb__btn gcb__btn--ghost" hidden><?php esc_html_e('Enregistrer mes choix', 'gdpr-cookie-banner'); ?></button>
            <button type="button" id="gcb-accept" class="gcb__btn"><?php esc_html_e('Tout accepter', 'gdpr-cookie-banner'); ?></button>
        </div>
    </div>
</div>
<?php
    }

    /**
     * [gestion_cookies] ou [gestion_cookies texte="Mes cookies"]
     * Un lien personnalisé "#gestion-cookies" dans un menu fonctionne aussi.
     */
    public function shortcode_manage_link($atts): string
    {
        $atts = shortcode_atts(
            ['texte' => __('Gérer les cookies', 'gdpr-cookie-banner')],
            (array) $atts,
            'gestion_cookies'
        );

        return '<a href="#gestion-cookies" class="gcb-manage" data-gcb-open>' . esc_html($atts['texte']) . '</a>';
    }
}
