# Journal des modifications

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
numérotation [SemVer](https://semver.org/lang/fr/).

## [2.1.0] — 2026-11-05

### Ajouté
- Route REST `GET /wp-json/gcb/v1/config` pour les fronts headless (React…).
- Fichier `uninstall.php` : suppression des réglages à la désinstallation.
- En-têtes de licence GPL-2.0-or-later.

## [2.0.0] — 2026-11-05

### Ajouté
- Google Consent Mode v2 : tout refusé par défaut, mise à jour après le choix.
- Chargement de Google Analytics 4 et Google Ads uniquement après accord.
- Conversion Google Ads « clic sur le téléphone » (liens `tel:`).
- Boutons « Tout refuser », « Personnaliser », « Tout accepter » de même niveau.
- Choix par catégorie (mesure d'audience, publicité).
- Page de réglages *Réglages > Cookies & Google*.
- Lien « Gérer les cookies » : shortcode `[gestion_cookies]` ou lien `#gestion-cookies`.
- Durée de conservation du choix réglable (6 mois par défaut, 13 au maximum).
- Compatibilité avec les plugins de cache de pages (choix lu dans le navigateur).
- Accessibilité : rôle `dialog`, focus visible, responsive mobile.

### Modifié
- Bandeau entièrement traduit en français.

### Supprimé
- Widget « GDPR Cookie Banner » (il relançait `wp_footer` et dupliquait les scripts).
- Balise Universal Analytics (`UA-`), abandonnée par Google.

### Corrigé
- Les choix cochés n'étaient pas enregistrés et Google Analytics ne se chargeait jamais.
- Le cookie de consentement était `HttpOnly`, donc illisible par le JavaScript.
- Absence de bouton de refus et consentement implicite « en continuant à naviguer ».

## [1.2.0]

- Version initiale.
