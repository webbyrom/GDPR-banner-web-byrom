/**
 * GDPR Cookie Banner — interactions du bandeau.
 * La configuration et les fonctions de consentement sont fournies
 * par le script en ligne du <head> (window.gcb).
 */
(function () {
    'use strict';

    var banner = document.getElementById('gcb-banner');
    if (!banner || !window.gcb) {
        return;
    }

    var cfg = window.gcb.cfg;
    var prefs = document.getElementById('gcb-preferences');
    var btnAccept = document.getElementById('gcb-accept');
    var btnRefuse = document.getElementById('gcb-refuse');
    var btnCustomize = document.getElementById('gcb-customize');
    var btnSave = document.getElementById('gcb-save');
    var chkAnalytics = document.getElementById('gcb-analytics');
    var chkMarketing = document.getElementById('gcb-marketing');

    function save(c) {
        var value = encodeURIComponent(JSON.stringify({
            v: 1,
            a: c.analytics ? 1 : 0,
            m: c.marketing ? 1 : 0,
            t: Math.floor(Date.now() / 1000)
        }));
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = cfg.cookie + '=' + value + '; Max-Age=' + (cfg.days * 86400) +
            '; Path=/; SameSite=Lax' + secure;

        var previous = window.gcb.consent;
        window.gcb.consent = c;

        // Un consentement retiré ne peut pas "décharger" une balise déjà active :
        // on recharge la page pour repartir proprement.
        if (previous && ((previous.analytics && !c.analytics) || (previous.marketing && !c.marketing))) {
            location.reload();
            return;
        }
        window.gcb.apply(c);
    }

    function open(showPrefs) {
        var current = window.gcb.consent || { analytics: false, marketing: false };
        chkAnalytics.checked = current.analytics;
        chkMarketing.checked = current.marketing;
        togglePrefs(!!showPrefs);
        banner.hidden = false;
    }

    function close() {
        banner.hidden = true;
    }

    function togglePrefs(show) {
        prefs.hidden = !show;
        btnSave.hidden = !show;
        btnCustomize.hidden = show;
        btnCustomize.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show) {
            chkAnalytics.focus();
        }
    }

    btnAccept.addEventListener('click', function () {
        save({ analytics: true, marketing: true });
        close();
    });

    btnRefuse.addEventListener('click', function () {
        save({ analytics: false, marketing: false });
        close();
    });

    btnCustomize.addEventListener('click', function () {
        togglePrefs(true);
    });

    btnSave.addEventListener('click', function () {
        save({ analytics: chkAnalytics.checked, marketing: chkMarketing.checked });
        close();
    });

    // Lien "Gérer les cookies" : shortcode [gestion_cookies] ou lien de menu "#gestion-cookies".
    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-gcb-open], a[href$="#gestion-cookies"]');
        if (!link) {
            return;
        }
        e.preventDefault();
        open(true);
    });

    // Conversion Google Ads sur les clics "tel:", uniquement avec l'accord publicité.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href^="tel:"]');
        if (!link || !cfg.ads || !cfg.phoneLabel) {
            return;
        }
        var c = window.gcb.consent;
        if (!c || !c.marketing || typeof window.gtag !== 'function') {
            return;
        }
        window.gtag('event', 'conversion', { send_to: cfg.ads + '/' + cfg.phoneLabel });
    });

    if (!window.gcb.consent) {
        open(false);
    }
})();
