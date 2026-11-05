# GDPR Cookie Banner

Bandeau de consentement aux cookies pour WordPress, conforme aux
recommandations de la CNIL, avec **Google Consent Mode v2** pour
Google Analytics 4 et Google Ads.

Léger et sans dépendance : un fichier PHP principal, trois classes, un script
et une feuille de style.

![Version](https://img.shields.io/badge/version-2.1.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![Licence](https://img.shields.io/badge/licence-GPL--2.0--or--later-green)

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Lien « Gérer les cookies »](#lien--gérer-les-cookies-)
- [Fonctionnement technique](#fonctionnement-technique)
- [Conformité CNIL](#conformité-cnil)
- [Compatibilité avec d'autres extensions](#compatibilité-avec-dautres-extensions)
- [Vérifier l'installation](#vérifier-linstallation)
- [Utilisation en headless](#utilisation-en-headless)
- [Structure des fichiers](#structure-des-fichiers)
- [Désinstallation](#désinstallation)
- [Licence](#licence)

---

## Fonctionnalités

- **Trois boutons de même niveau** : « Tout refuser », « Personnaliser », « Tout accepter ».
- **Choix par catégorie** : mesure d'audience (Google Analytics) et publicité (Google Ads).
- **Google Consent Mode v2** : tout est refusé par défaut, puis mis à jour selon le choix.
- **Aucune balise Google chargée avant accord.**
- **Conversion Google Ads « clic sur le téléphone »** sur les liens `tel:`, uniquement avec l'accord publicité.
- **Lien « Gérer les cookies »** pour modifier son choix à tout moment.
- **Durée de conservation réglable** (6 mois par défaut, 13 au maximum).
- **Compatible avec les plugins de cache** : le choix est lu dans le navigateur, pas côté serveur.
- **Accessible** : rôle `dialog`, navigation au clavier, focus visible, affichage mobile.
- **Route REST** pour les fronts headless.

## Prérequis

- WordPress 6.0 ou supérieur
- PHP 7.4 ou supérieur (testé avec PHP 8.4)
- Une page de politique de confidentialité définie dans
  *Réglages > Confidentialité* (pour le lien « En savoir plus »)

## Installation

### Depuis une archive zip

1. Télécharger le zip du dépôt (*Code > Download ZIP*) ou d'une release.
2. *Extensions > Ajouter > Téléverser une extension*, choisir le zip, puis **Activer**.

> Si l'archive vient de *Download ZIP*, le dossier se nomme
> `gdpr-cookie-banner-main`. Renommez-le `gdpr-cookie-banner` avant de
> l'installer pour garder un nom stable lors des mises à jour.

### Avec Git

```bash
cd wp-content/plugins
git clone https://github.com/<utilisateur>/gdpr-cookie-banner.git
```

Puis activer l'extension dans *Extensions*.

### Mise à jour depuis la version 1.x

1. Désactiver l'ancienne version.
2. Remplacer le dossier du plugin par la nouvelle version.
3. Réactiver, puis renseigner les réglages.
4. Retirer le widget « GDPR Cookie Banner » s'il était utilisé : il n'existe plus.

Le nom du cookie a changé (`gcb_consent`) : le bandeau s'affichera de nouveau
une fois pour chaque visiteur, ce qui est voulu puisque l'ancien consentement
n'était pas valable.

## Configuration

Tout se règle dans **Réglages > Cookies & Google** (lien « Réglages » aussi
présent dans la liste des extensions).

| Champ | Exemple | Où le trouver |
|---|---|---|
| ID Google Analytics 4 | `G-XXXXXXXXXX` | Analytics > Administration > Flux de données |
| ID Google Ads | `AW-123456789` | Google Ads > Objectifs > Conversions > balise Google |
| Libellé de conversion téléphone | `AbCdEfGhIjKlMnOp` | Partie après le `/` dans `send_to: 'AW-123456789/AbCdEf…'` |
| Durée de conservation du choix | `6` | En mois, entre 1 et 13 |

Un champ vide désactive la balise correspondante. Les identifiants sont
vérifiés à l'enregistrement.

### Créer la conversion « clic sur le téléphone »

1. Google Ads > *Objectifs > Conversions > Nouvelle action de conversion*.
2. Choisir **Site web**, puis la création manuelle d'une action de type
   « Clic sur un numéro de téléphone » (ou « Contact »).
3. Récupérer le `send_to` proposé et copier le libellé dans les réglages.

Assurez-vous que les liens téléphone du site sont au bon format :
`tel:+33XXXXXXXXX` (sans le 0 après `+33`).

## Lien « Gérer les cookies »

Le visiteur doit pouvoir changer d'avis à tout moment. Deux méthodes :

**Dans un menu** (*Apparence > Menus > Liens personnalisés*) :

- URL : `#gestion-cookies`
- Texte : `Gérer les cookies`

**Avec le shortcode**, dans un widget, une page ou un modèle :

```
[gestion_cookies]
[gestion_cookies texte="Mes préférences cookies"]
```

Le lien rouvre le bandeau directement sur les préférences, avec les choix
actuels cochés.

## Fonctionnement technique

1. **`wp_head` (priorité 1)** : un script en ligne déclare le consentement par
   défaut (`gtag('consent', 'default', …)` tout refusé), puis lit le cookie
   `gcb_consent`. Si un choix existe, il est appliqué immédiatement.
2. **Après accord**, `gtag.js` est chargé et seules les balises autorisées sont
   configurées : GA4 si « mesure d'audience », Google Ads si « publicité ».
3. **`wp_footer`** : le bandeau est inséré masqué. Le script l'affiche si aucun
   choix n'a été fait.
4. **Retrait d'un accord** : la page est rechargée, car une balise déjà chargée
   ne peut pas être retirée proprement.

### Cookie utilisé

| Nom | Contenu | Durée | Attributs |
|---|---|---|---|
| `gcb_consent` | `{"v":1,"a":0\|1,"m":0\|1,"t":timestamp}` | Réglable (6 mois par défaut) | `Path=/`, `SameSite=Lax`, `Secure` en HTTPS |

`a` = mesure d'audience, `m` = publicité, `t` = date du choix.

### Correspondance avec le Consent Mode

| Catégorie | Signaux Google |
|---|---|
| Mesure d'audience | `analytics_storage` |
| Publicité | `ad_storage`, `ad_user_data`, `ad_personalization` |
| Toujours accordés | `functionality_storage`, `security_storage` |

## Conformité CNIL

Le plugin applique les points principaux des
[recommandations de la CNIL](https://www.cnil.fr/fr/cookies-et-autres-traceurs) :

- refuser est aussi simple qu'accepter (boutons de même niveau, au premier écran) ;
- aucun consentement implicite (« en continuant à naviguer… ») ;
- choix par finalité ;
- aucune balise de mesure ou de publicité avant accord ;
- choix modifiable à tout moment ;
- conservation du choix limitée (6 mois recommandés, 13 au maximum).

Il reste de votre responsabilité de tenir à jour la politique de
confidentialité (finalités, durées, destinataires) et de vérifier qu'aucune
autre extension ne dépose de traceurs sans consentement. Ce plugin est un
outil, pas un avis juridique.

## Compatibilité avec d'autres extensions

**Site Kit by Google, MonsterInsights, extensions de « header/footer scripts »…**
Si une autre extension insère déjà la balise Google, elle le fait sans attendre
le consentement, et les visites risquent d'être comptées deux fois.

- *Site Kit* : désactiver l'insertion du code de suivi dans
  *Site Kit > Réglages > Analytics*. Les statistiques restent visibles dans le
  tableau de bord.
- Autres extensions : retirer le code Google qu'elles ajoutent.

**Contenus tiers** (Google Maps, YouTube…) : ils ne sont pas bloqués par ce
plugin. Pour les conditionner au consentement, lisez `window.gcb.consent`
(voir ci-dessous).

**Plugins de cache** : compatibles. Videz le cache après l'installation ou une
modification des réglages.

### API JavaScript

```js
window.gcb.consent; // { analytics: true|false, marketing: true|false } ou null
window.gcb.cfg;     // réglages publics (ID GA4, ID Ads…)
```

## Vérifier l'installation

1. Ouvrir le site en navigation privée : le bandeau s'affiche.
2. Ouvrir les outils du navigateur (F12), onglet **Réseau**, puis cliquer
   **Tout refuser** : aucune requête vers `googletagmanager.com` ni
   `google-analytics.com`.
3. Cliquer sur **Gérer les cookies**, puis **Tout accepter** : `gtag/js` se charge.
4. Vérifier avec l'extension **Google Tag Assistant** que le Consent Mode et la
   conversion téléphone remontent.

## Utilisation en headless

En headless, WordPress ne génère plus les pages : `wp_head` et `wp_footer` ne
sont pas exécutés et le bandeau doit être affiché par le front. Le plugin
expose sa configuration :

```
GET /wp-json/gcb/v1/config
```

```json
{
  "ga4": "G-XXXXXXXXXX",
  "ads": "AW-123456789",
  "phoneLabel": "AbCdEfGhIjKlMnOp",
  "days": 182,
  "privacyUrl": "https://exemple.fr/politique-de-confidentialite/",
  "version": "2.1.0"
}
```

Seuls des identifiants publics sont exposés (ils sont de toute façon visibles
dans le code des pages). La réponse est mise en cache une heure.

## Structure des fichiers

```
gdpr-cookie-banner/
├── gdpr-cookie-banner.php                 Fichier principal (en-tête, chargement)
├── uninstall.php                          Nettoyage à la suppression
├── includes/
│   ├── class-gdpr-cookie-manager.php      Consent Mode, bandeau, shortcode
│   ├── class-gdpr-cookie-settings.php     Page Réglages > Cookies & Google
│   └── class-gdpr-cookie-rest.php         Route REST pour le headless
├── assets/
│   ├── css/cookie-banner.css
│   └── js/cookie-banner.js
├── CHANGELOG.md
├── LICENSE
└── README.md
```

## Désinstallation

- **Désactivation** : le bandeau disparaît, les réglages sont conservés.
- **Suppression** depuis *Extensions* : l'option `gcb_options` est supprimée.

Le cookie `gcb_consent` déjà déposé chez les visiteurs expire de lui-même.

## Licence

[GPL-2.0-or-later](LICENSE), comme WordPress.

Développé par [Romain Fourel — Web-Byrom](https://www.web-byrom.com/).
