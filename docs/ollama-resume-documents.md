# Résumé de documents par Ollama — GERM

## Fonctionnement

- Angular appelle \`POST /api/documents-administratifs/{id}/resume-ia\`.
- Symfony vérifie le rôle RH Personnel, lit le document via \`FileStorage\` (déchiffrement inclus), extrait le texte PDF localement avec \`pdftotext\`, puis envoie au maximum 20 000 caractères à la passerelle IA configurée.
- La passerelle utilise Ollama sur la VM IA et renvoie \`{ "summary": "..." }\`.
- GERM ne stocke pas le résumé et ne journalise pas le contenu documentaire.

## Configuration

Dans le fichier \`.env.local\` du serveur GERM (ne jamais committer les vraies valeurs) :

\`\`\`dotenv
GERM_AI_BASE_URL=http://ADRESSE_PRIVEE_PASSERELLE:8000
GERM_AI_TOKEN=JETON_LONG_ALEATOIRE
\`\`\`

L'adresse doit être celle de la passerelle IA privée, joignable depuis le serveur GERM. Ne pas exposer Ollama directement à Internet. Autoriser le port de la passerelle uniquement depuis le serveur GERM et préférer HTTPS ou un tunnel privé chiffré entre serveurs.

## Prérequis serveur

Installer \`pdftotext\` (paquet Ubuntu \`poppler-utils\`) sur le serveur GERM :

\`\`\`bash
sudo apt update
sudo apt install -y poppler-utils
\`\`\`

Le fichier temporaire contenant le PDF déchiffré est créé avec des permissions restrictives et supprimé après extraction. La première version ne prend en charge que les PDF avec du texte extractible. Les PDF scannés nécessiteront une étape OCR distincte. Les documents longs sont tronqués à 20 000 caractères.

## Test

1. Configurer \`GERM_AI_BASE_URL\` et \`GERM_AI_TOKEN\` dans \`.env.local\`.
2. Vérifier que le compte connecté dispose de \`ROLE_RH_PERSONNEL\`.
3. Ouvrir la liste des documents administratifs et cliquer sur **Analyser avec l'IA** pour un PDF de test non confidentiel.
4. Vérifier que les erreurs de passerelle ne révèlent ni jeton ni contenu du document.

Le résumé est une aide à la lecture ; l'agent doit vérifier les informations dans le document original.
