# GERM-006 — Rotation de `QR_SECRET_KEY`

**Ne pas exécuter sans en avoir informé le service informatique** : cette
rotation invalide **toutes les étiquettes QR déjà imprimées et collées** sur
le matériel du parc. Après bascule, scanner une ancienne étiquette renverra
« matériel introuvable » (`resoudreQrcode()` : jeton invalide → 404, par
conception, pour ne rien révéler du format attendu — voir `QrTokenService`).
Il faudra réimprimer les étiquettes du matériel actif.

## Pourquoi

`QR_SECRET_KEY` de production **n'est pas surchargée** par `.env.local` : la
valeur utilisée pour chiffrer chaque jeton d'étiquette (libsodium
`secretbox`) est celle versionnée dans `.env`, donc dans l'historique Git du
dépôt. Quiconque a accès en lecture au dépôt — développeurs actuels,
anciens, un clone égaré — peut forger un jeton `germ://materiel/...` valide.
L'impact reste contenu (résoudre un jeton exige `ROLE_IT_STOCK`, voir
`MeController::materielQrcode()` et `MaterielInformatiqueController`), mais
c'est un secret qui n'aurait jamais dû être commun au dépôt et à la
production.

## Aucune valeur de clé ne doit apparaître dans un ticket, un chat ou un
## commit — y compris celle-ci. La générer directement là où elle sera collée.

## Procédure

**1. Générer la nouvelle clé, directement sur le serveur de production**
(pour qu'elle ne transite par aucun autre canal) :

```bash
ssh germ-appdbserver
php -r "echo base64_encode(sodium_crypto_secretbox_keygen()), PHP_EOL;"
```

**2. La déposer dans `.env.local`** (déjà gitignoré — jamais dans `.env`) :

```bash
cd /var/www/germ
echo "QR_SECRET_KEY=<la_valeur_generee_a_l_etape_1>" | sudo tee -a .env.local
sudo -u www-data php bin/console cache:clear --env=prod --no-debug
```

Remplacer une éventuelle ligne `QR_SECRET_KEY=` déjà présente plutôt que
d'en ajouter une seconde (`.env.local` prévaut sur `.env`, mais deux lignes
pour la même clé dans le même fichier prêtent à confusion).

**3. Vérifier qu'une étiquette se génère avec la nouvelle clé** :

```bash
# Depuis un compte ROLE_IT_STOCK authentifié :
curl -sk -b cookies.txt https://10.112.26.30/api/materiels-informatiques/<id>/qrcode -o test-qr.png
file test-qr.png   # doit annoncer "PNG image data"
```

**4. Réimprimer les étiquettes** du matériel actif — la vraie contrainte
opérationnelle de cette rotation, à planifier avec le service informatique
plutôt qu'à faire sans préavis.

**5. Retirer `QR_SECRET_KEY` de `.env` dans le dépôt Git** (garder uniquement
un commentaire expliquant qu'elle vit désormais dans `.env.local`, jamais
versionné) — sans quoi le prochain lecteur du dépôt retombe sur l'ancienne
clé, croira qu'elle est toujours active, et le problème renaît à la prochaine
rotation manquée.

**Cette dernière étape (retrait de `.env`) est un changement de code, pas une
opération serveur : je peux la faire moi-même sur simple confirmation, une
fois la bascule en production effectuée** — sinon `.env` local (dev)
n'aurait plus de valeur par défaut pour développer, il faudra alors que
chaque développeur ait sa propre clé de dev dans son propre `.env.local`.
