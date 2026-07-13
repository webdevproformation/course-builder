<?php

declare(strict_types=1);

namespace CourseBuilder;

final class MenuGenerator
{
    private const ITEMS_PER_GROUP = 5;

    public function __construct(
        private readonly string $rootDir,
        private readonly CourseRepository $repository,
        private readonly Log $logService,
    ) {
    }

    public function generate(): void
    {
        $files = $this->repository->getRecentFiles(null);

        if ($files === []) {
            $this->logService->msg('⚠️ Aucun fichier trouvé.');
            return;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $rootUl = $document->createElement('ul');
        $document->appendChild($rootUl);

        $rootLi = $document->createElement('li', 'Sommaire :');
        $rootUl->appendChild($rootLi);

        foreach (array_chunk($files, self::ITEMS_PER_GROUP, true) as $chunk) {
            $start = array_key_first($chunk) + 1;

            $ol = $document->createElement('ol');
            $ol->setAttribute('start', (string) $start);

            foreach ($chunk as $file) {
                $link = str_replace(
                    '.md',
                    '.html',
                    $this->repository->htmlFile($file)
                );

                $label = pathinfo($file, PATHINFO_FILENAME);

                $li = $document->createElement('li');

                $a = $document->createElement('a');
                $a->setAttribute('href', $link);
                $a->appendChild(
                    $document->createTextNode($label)
                );

                $li->appendChild($a);
                $ol->appendChild($li);
            }

            $rootLi->appendChild($ol);
        }

        $menuPath = $this->rootDir. DIRECTORY_SEPARATOR . "menu.html";

        file_put_contents(
            $menuPath,
            $document->saveHTML()
        );

        $this->logService->msg("✅ {$menuPath}");
    }
}