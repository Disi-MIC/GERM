<?php

namespace App\Service;

use App\Entity\DocumentAdministratif;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;

class DocumentAiSummaryService
{
    private readonly HttpClientInterface $http;

    public function __construct(
        private readonly FileStorage $fileStorage,
        #[Autowire('%env(GERM_AI_BASE_URL)%')] private readonly string $baseUrl,
        #[Autowire('%env(GERM_AI_TOKEN)%')] private readonly string $token,
    ) {
        $this->http = HttpClient::create();
    }

    public function resumePdf(DocumentAdministratif $document): string
    {
        if ('' === trim($this->token)) {
            throw new \RuntimeException("La passerelle IA n'est pas configurée.");
        }

        $path = $document->getCheminFichier();
        if (!$path || 'application/pdf' !== $this->fileStorage->mimeType($path)) {
            throw new \InvalidArgumentException("L'analyse IA est disponible uniquement pour les fichiers PDF.");
        }

        $texte = $this->extraireTextePdf($path);
        if ('' === trim($texte)) {
            throw new \InvalidArgumentException("Aucun texte extractible trouvé. Le PDF est peut-être scanné ; l'OCR n'est pas encore activé.");
        }

        $texte = iconv_substr($texte, 0, 20000, 'UTF-8') ?: substr($texte, 0, 20000);

        try {
            $response = $this->http->request('POST', rtrim($this->baseUrl, '/').'/v1/summarize', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token,
                    'Accept' => 'application/json',
                ],
                'json' => ['language' => 'fr', 'document_text' => $texte],
                'timeout' => 180,
                'max_duration' => 180,
            ]);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException("La passerelle IA n'a pas répondu.");
        }

        if ($status < 200 || $status >= 300 || !isset($data['summary']) || !is_string($data['summary'])) {
            throw new \RuntimeException("La passerelle IA a renvoyé une réponse invalide.");
        }

        return $data['summary'];
    }

    private function extraireTextePdf(string $chemin): string
    {
        $flux = $this->fileStorage->readStream($chemin);
        $temp = tempnam(sys_get_temp_dir(), 'germ-ai-');
        if (false === $temp) {
            if (is_resource($flux)) {
                fclose($flux);
            }
            throw new \RuntimeException("Impossible de préparer le document pour l'analyse.");
        }

        @chmod($temp, 0600);
        try {
            $sortie = fopen($temp, 'wb');
            if (false === $sortie || !is_resource($flux)) {
                throw new \RuntimeException("Impossible de lire le document.");
            }
            stream_copy_to_stream($flux, $sortie);
            fclose($sortie);
            fclose($flux);

            $process = new Process(['pdftotext', '-layout', $temp, '-']);
            $process->setTimeout(30);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \InvalidArgumentException("Impossible d'extraire le texte de ce PDF.");
            }

            return trim($process->getOutput());
        } catch (\Throwable $e) {
            if (is_resource($flux)) {
                fclose($flux);
            }
            throw $e;
        } finally {
            @unlink($temp);
        }
    }
}
