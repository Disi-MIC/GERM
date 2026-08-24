# GERM Internal Root CA

Autorité de certification interne signant le certificat HTTPS du serveur de
production (`10.112.26.30`, voir `environment.mobile.prod.ts`). Pas une
autorité publique reconnue : chaque appareil mobile doit installer et faire
confiance à ce CA avant de pouvoir s'y connecter.

Régénéré le 2026-08-24 — l'original n'avait jamais été copié dans ce dépôt ni
sur le serveur (seul le certificat serveur déjà signé y était), il a donc
fallu recréer un nouveau CA et réémettre le certificat serveur. Toute
installation de l'ancien CA sur un appareil doit être refaite avec celui-ci.

## Fichiers ici (publics, sans risque à committer)

- `germ-ca-cert.pem` — certificat du CA (PEM), pour référence/vérification.
- `GERM-Internal-CA.mobileconfig` — profil à installer sur un appareil iOS
  (AirDrop, email, ou lien de téléchargement) pour lui faire confiance.

**La clé privée du CA n'est PAS ici** (jamais committée) — elle vit dans
`~/.germ-internal-ca/germ-ca-key.pem` sur ce Mac. Nécessaire uniquement pour
réémettre un nouveau certificat serveur (renouvellement avant le
2028-11-26, ou si l'IP du serveur change).

## Installer sur un iPhone/iPad

1. Envoyer `GERM-Internal-CA.mobileconfig` à l'appareil (AirDrop, mail...).
2. Réglages > Profil téléchargé > Installer (demande le code d'accès de
   l'appareil).
3. **Étape séparée, indispensable** : Réglages > Général > Informations >
   Réglages de confiance des certificats > activer la confiance totale pour
   "GERM Internal Root CA". Sans cette étape, le profil est installé mais
   les connexions HTTPS continuent d'être rejetées.

## Réémettre le certificat serveur (renouvellement ou changement d'IP)

Sur ce Mac, avec `~/.germ-internal-ca/germ-ca-key.pem` :

```bash
openssl genrsa -out germ-server-key.pem 2048
openssl req -new -key germ-server-key.pem -subj "/CN=<IP_OU_DOMAINE>" -out germ-server.csr
echo "subjectAltName=IP:<IP_OU_DOMAINE>
basicConstraints=CA:FALSE
keyUsage=critical,digitalSignature,keyEncipherment
extendedKeyUsage=serverAuth" > ext.cnf
openssl x509 -req -in germ-server.csr -CA ~/.germ-internal-ca/germ-ca-cert.pem \
  -CAkey ~/.germ-internal-ca/germ-ca-key.pem -CAcreateserial -days 825 -sha256 \
  -extfile ext.cnf -out germ-server-cert.pem
cat germ-server-cert.pem ~/.germ-internal-ca/germ-ca-cert.pem > germ-fullchain.pem
```

Puis déployer `germ-fullchain.pem` (renommé `germ-server-cert.pem`) et
`germ-server-key.pem` dans `/home/adminappdbserver/germ-ssl-setup/` sur
`germ-appdbserver`, et lancer `sudo ./install-ssl.sh` là-bas (ce script gère
le test de config Apache et le rechargement).
