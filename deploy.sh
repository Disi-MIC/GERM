#!/bin/bash
# Déploie l'état courant du dépôt vers la production (10.112.26.30).
#
# Committe les changements en cours (si présents) puis pousse vers le
# remote "production" — un dépôt bare sur le serveur dont le hook
# post-receive (/var/git/germ.git/hooks/post-receive) enchaîne seul
# composer install, build Angular, migrations et cache:clear.
#
# Usage :
#   ./deploy.sh                        # message de commit par défaut
#   ./deploy.sh "Message du commit"    # message personnalisé

set -e

cd "$(dirname "$0")"

if [ -n "$(git status --porcelain)" ]; then
    git add -A
    MESSAGE="${1:-Deploy $(date '+%Y-%m-%d %H:%M')}"
    git commit -m "$MESSAGE"
else
    echo "Rien à committer, on pousse le dernier commit existant."
fi

git push production main
