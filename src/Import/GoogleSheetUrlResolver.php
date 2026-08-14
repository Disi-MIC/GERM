<?php

namespace App\Import;

/**
 * Convertit n'importe quel lien Google Sheets copié depuis le navigateur
 * (lien de partage "edit", lien déjà en "export?format=csv"...) en son URL
 * canonique d'export CSV, pour un import en lecture seule sans identifiants
 * (le classeur doit être partagé "Toute personne disposant du lien —
 * Lecteur", ou publié sur le web).
 *
 * L'hôte est volontairement restreint à docs.google.com : le serveur va
 * ensuite effectuer une requête HTTP sortante vers l'URL résolue (voir
 * ImportRunner::runFromGoogleSheet()), donc n'importe quel domaine
 * accepté ici serait une porte ouverte au SSRF pour un compte
 * superadmin — seul rôle pouvant utiliser cette fonctionnalité.
 */
class GoogleSheetUrlResolver
{
    public function resoudreUrlExportCsv(string $urlSaisie): string
    {
        $urlSaisie = trim($urlSaisie);
        $parts = parse_url($urlSaisie);

        if (false === $parts || !isset($parts['host'], $parts['scheme'])) {
            throw new \InvalidArgumentException("Cette adresse n'est pas une URL valide.");
        }

        if ('https' !== $parts['scheme'] || 'docs.google.com' !== $parts['host']) {
            throw new \InvalidArgumentException('Seuls les liens docs.google.com (Google Sheets) sont acceptés.');
        }

        if (!preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $parts['path'] ?? '', $matches)) {
            throw new \InvalidArgumentException("Impossible de trouver l'identifiant du classeur dans ce lien. Copiez le lien de partage complet depuis Google Sheets.");
        }
        $id = $matches[1];
        $gid = $this->extraireGid($parts['query'] ?? '') ?? $this->extraireGid($parts['fragment'] ?? '');

        // Un gid absent du lien collé est volontairement omis plutôt que
        // supposé valoir '0' : Google Sheets répond 400 Bad Request si le
        // gid ne correspond à aucun onglet du classeur (fréquent — '0'
        // n'est le premier onglet que s'il n'a jamais été dupliqué/réordonné/
        // supprimé), alors qu'omettre le paramètre exporte fiablement le
        // premier onglet.
        return null === $gid
            ? sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv', $id)
            : sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s', $id, $gid);
    }

    private function extraireGid(string $queryOuFragment): ?string
    {
        parse_str($queryOuFragment, $params);

        return isset($params['gid']) && is_string($params['gid']) ? $params['gid'] : null;
    }
}
