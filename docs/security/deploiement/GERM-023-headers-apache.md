# GERM-023 — En-têtes de sécurité sur le SPA Angular

**✅ Appliqué et vérifié en production le 15/09/2026** par un administrateur
disposant de `sudo` — les 6 en-têtes sont confirmés présents sur `/app/`,
sans régression sur `/api` ni `/admin`. Procédure conservée ci-dessous pour
référence (et en cas de réinstallation du vhost).

**Blocage initial** : `mod_headers` n'était pas activé sur le serveur et
`sudo` exigeait un mot de passe — ni l'activation du module, ni la
modification du vhost, ni le rechargement d'Apache n'étaient possibles par
un canal automatisé. Exécuté manuellement par un administrateur disposant de
`sudo`.

## Ce qui a été vérifié avant de choisir la CSP

Contrairement à l'admin Twig, le SPA n'a besoin d'**aucune** ressource
externe : `curl https://10.112.26.30/app/index.html` confirme zéro script
inline, zéro référence à un CDN, polices et scripts tous servis en même
origine. Beasties (l'optimiseur Angular) inline du CSS critique dans un
`<style>` — d'où le seul `'unsafe-inline'` nécessaire, sur `style-src`
uniquement. La CSP proposée est donc **plus stricte** que celle déjà posée par
Symfony sur `/admin` (pas de `cdn.jsdelivr.net`).

## Pourquoi un `<Directory>` dédié à `/app`, pas un `Header` global

`Header set` remplace toute valeur déjà posée par le backend. Symfony pose
déjà les bons en-têtes sur `/api`, `/admin` et `/verif-carte` (routes qui
passent par `index.php`) — une directive au niveau du `<Directory>` racine
`/var/www/germ/public` écraserait cette CSP, adaptée à chaque contexte, par
une seule règle. En la scopant à `/var/www/germ/public/app` (le seul
répertoire qui contient les fichiers statiques du SPA, jamais atteint par
`index.php`), aucun conflit n'est possible.

## Commandes

```bash
ssh germ-appdbserver

sudo a2enmod headers

sudo tee -a /etc/apache2/sites-enabled/germ.conf > /dev/null << 'EOF'

    <Directory /var/www/germ/public/app>
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "DENY"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
        Header always set Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=(), usb=()"
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
        Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'"
    </Directory>
EOF
```

**Attention** : la ligne `</VirtualHost>` de fermeture doit rester la dernière
ligne du fichier après cet ajout. Le `tee -a` (append) l'ajoute avant si le
fichier se termine bien par `</VirtualHost>` sur sa propre ligne — vérifier
avec `tail -20 /etc/apache2/sites-enabled/germ.conf` avant de continuer.
Idéalement, placer le bloc `<Directory>` À L'INTÉRIEUR du `<VirtualHost *:443>`
existant, avant son `</VirtualHost>` — éditer le fichier avec un éditeur
(`sudo nano` ou `sudo vim`) est plus sûr qu'un append en aveugle pour ce point
précis.

```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

## Vérification après application

```bash
curl -skI https://10.112.26.30/app/ | grep -iE "content-security|x-frame|strict-transport|x-content-type|referrer|permissions"

curl -sk https://10.112.26.30/app/ -o /dev/null -w "%{http_code}\n"
curl -sk https://10.112.26.30/api -o /dev/null -w "%{http_code}\n"
```

Le premier doit maintenant afficher les 6 en-têtes. Les deux derniers doivent
rester inchangés (`200` et `401` respectivement) — la portée `<Directory>`
dédiée garantit que `/api` et `/admin` ne sont pas affectés.

## Test fonctionnel à faire dans un navigateur

Ouvrir `https://10.112.26.30/app/` et vérifier dans la console qu'aucune
ressource n'est bloquée par la CSP (onglet Réseau, ou messages "Refused to
load..." dans la console). Si un futur ajout au SPA (police, image, script)
provient d'un domaine externe, cette CSP le bloquera — il faudra alors
l'ajouter explicitement à la ligne concernée.
