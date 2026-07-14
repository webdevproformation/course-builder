<?php

declare(strict_types=1);

namespace CourseBuilder;

use RuntimeException;

/**
 * Générateur HTML à partir de fichiers Markdown.
 *
 * Cette classe orchestre tout le processus :
 * - Suppression des anciens HTML.
 * - Création du répertoire temporaire.
 * - Transformation des Markdown.
 * - Génération des pages HTML via Pandoc.
 * - Nettoyage final.
 */
final class HtmlGenerator
{
    /**
     * Répertoire temporaire utilisé durant la génération.
     */
    private const TMP_DIRECTORY = 'tmp';

    private const DEFAULT_RECENT_MINUTES = 15;

    /** @var string[] */
    private array $recentFiles = [];

    /**
     * @param CourseRepository $repository Gestionnaire des fichiers.
     * @param string           $regex      Expression régulière de nettoyage.
     */
    public function __construct(
        private readonly CourseRepository $repository,
        private readonly string $regex,
        private readonly Log $logService,
        private readonly bool $all = false,
    ) {}

    /**
     * Lance le processus complet de génération.
     *
     * @return void
     */
    public function generate(): void
    {
        $this->recentFiles = $this->repository->getRecentFiles(
            $this->all ? null : self::DEFAULT_RECENT_MINUTES
        );

        if (empty($this->recentFiles)) {
            $this->logService->msg("Aucun fichier à générer.");
            return;
        }


        $this->deleteHtmlFiles();
        $this->createTmp();
        try {
            $this->improveMarkdownFiles();
            $this->generateHtmlFiles();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Supprime les fichiers HTML correspondant
     * aux fichiers Markdown qui vont être régénérés.
     * @return void
     */
    private function deleteHtmlFiles(): void
    {
        foreach ($this->recentFiles as $filename) {

            $htmlFile = $this->repository->htmlFile($filename);

            if (file_exists($htmlFile)) {

                unlink($htmlFile);

                $this->logService->msg("🗑️ $htmlFile");
            }
        }
    }

    /**
     * Crée le répertoire temporaire et y copie
     * uniquement les fichiers à traiter.
     *
     * @return void
     */
    private function createTmp(): void
    {
        if (!file_exists(self::TMP_DIRECTORY)) {
            mkdir(self::TMP_DIRECTORY);
        }

        foreach (glob(self::TMP_DIRECTORY . '/*.md') as $file) {
            unlink($file);
        }

        foreach ($this->recentFiles as $filename) {

            copy(
                $this->repository->sourceFile($filename),
                self::TMP_DIRECTORY . '/' . $filename
            );

            $this->logService->msg("📄 Copie $filename");
        }
    }

    /**
     * Prépare les fichiers Markdown avant la génération HTML.
     *
     * Opérations réalisées :
     * - Extraction du contenu utile via regex.
     * - Ajout de la navigation précédent/suivant.
     *
     * @return void
     */
    private function improveMarkdownFiles(): void
    {
        foreach ($this->recentFiles as $filename) {

            $tmpFile = self::TMP_DIRECTORY . '/' . $filename;

            $content = file_get_contents($tmpFile);

            preg_match_all(
                $this->regex,
                $content,
                $matches,
                PREG_SET_ORDER
            );

            if (
                isset($matches[0][1]) &&
                strlen($matches[0][1]) > 0
            ) {
                $content = $matches[0][1];
            }

            $before = $this->repository->previous($filename);
            $after = $this->repository->next($filename);


            $links = PHP_EOL. "\n## Navigation" . PHP_EOL;
            $links .= "← " . "[$before]($before){.btn-nav}" . PHP_EOL;
            $links .= "[$after]($after){.btn-nav}". " →" . PHP_EOL;

            $content = $content . "<br><br>" . $links;

            file_put_contents($tmpFile, $content);
        }
    }

    /**
     * Génère les pages HTML via Pandoc.
     *
     * @return void
     */
    private function generateHtmlFiles(): void
    {

        foreach ($this->recentFiles as $filename) {

            $source = self::TMP_DIRECTORY . '/' . $filename;

            $html = $this->repository->htmlFile($filename);

            $title = $this->repository->getTitleFromFilename($filename);

            $command = $this->buildPandocCommand(
                $source,
                $html,
                $title,
                __DIR__ . "/../templates/pandoc.html",
                $this->getPandocVariables()
            );

            exec($command . ' 2>&1', $output, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    implode(PHP_EOL, $output)
                );
            } else {
                $this->logService->msg("✅ $html");
            }
        }
    }

    public function getPandocVariables(): array
    {
        return [
            'lang' => 'fr',
            'dir' => 'ltr',
            'author-meta' => 'Malik H',
            'date-meta' => date('d M Y'),
            'toc-title' => 'Sommaire :',
        ];
    }


    private function buildPandocCommand(
        string $source,
        string $html,
        string $title,
        string $templateFile,
        array $variables
    ): string {
        $pandocVariables = implode(
            ' ',
            array_map(
                static fn(string $key, string $value): string =>
                '--variable ' . escapeshellarg("{$key}={$value}"),
                array_keys($variables),
                $variables
            )
        );

        return sprintf(
            'pandoc '
                . '-f markdown '
                . '-t html '
                . '--template=%s '
                . '%s '
                . '--metadata title=%s '
                . '%s '
                . '--toc '
                . '--toc-depth=2 '
                . '--number-sections '
                . '--highlight-style espresso '
                . '-o %s '
                . '-s',
            escapeshellarg($templateFile),
            escapeshellarg($source),
            escapeshellarg($title),
            $pandocVariables,
            escapeshellarg($html)
        );
    }


    /**
     * Nettoie les fichiers temporaires et
     * supprime le répertoire tmp.
     *
     * @return void
     */
    private function cleanup(): void
    {
        foreach (glob(self::TMP_DIRECTORY . '/*.md') as $file) {
            unlink($file);
        }

        rmdir(self::TMP_DIRECTORY);

        $this->logService->msg("🗑️ rmdir tmp");
    }
}
