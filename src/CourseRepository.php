<?php

declare(strict_types=1);

namespace CourseBuilder;

/**
 * Repository responsable de l'accès aux fichiers Markdown.
 *
 * Cette classe centralise :
 * - La recherche des fichiers Markdown récents.
 * - La détermination du chapitre précédent/suivant.
 * - La conversion d'un nom de fichier Markdown vers HTML.
 * - La construction des chemins sources.
 */
final class CourseRepository
{
    /** @var string[]|null */
    private ?array $files = null;

    private const NO_LINK = "#";


    /**
     * @param string $srcDirectory Répertoire contenant les fichiers .md.
     */
    public function __construct(private string $srcDirectory) {}

    private function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = array_map(
                'basename',
                glob($this->srcDirectory . '/*.md')
            );

            sort($this->files);
        }

        return $this->files;
    }

    /**
     * Retourne la liste des fichiers Markdown
     * modifiés depuis moins de X minutes.
     *
     * @param int $minutes Nombre de minutes à prendre en compte.
     *
     * @return string[] Liste des noms de fichiers.
     */
    public function getRecentFiles(?int $minutes = 15): array
    {
        $files = [];

        foreach ($this->getFiles() as $file) {

            $fullPath = $this->sourceFile($file);

            if (
                $minutes !== null
                && filemtime($fullPath) < time() - ($minutes * 60)
            ) {
                continue;
            }

            $files[] = $file;
        }

        sort($files);

        return $files;
    }

    /**
     * Retourne le fichier HTML précédent selon le numéro de chapitre.
     * 05-cours.md => 04-cours.html
     *
     * @param string $filename Nom du fichier courant.
     *
     * @return string Nom du fichier HTML précédent ou '#'.
     */
    public function previous(string $filename): string
    {
        return $this->findNeighbour($filename, -1);
    }

    /**
     * Retourne le fichier HTML suivant selon le numéro de chapitre.
     * 05-cours.md => 06-cours.html
     *
     * @param string $filename Nom du fichier courant.
     *
     * @return string Nom du fichier HTML suivant ou '#'.
     */
    public function next(string $filename): string
    {
        return $this->findNeighbour($filename, 1);
    }

    /**
     * Recherche un chapitre voisin.
     *
     * L'algorithme se base sur les deux premiers
     * caractères du nom de fichier.
     *
     * Exemple :
     * - offset = -1 => chapitre précédent
     * - offset = +1 => chapitre suivant
     *
     * @param string $filename Nom du fichier de référence.
     * @param int    $offset   Décalage à appliquer.
     *
     * @return string Nom du fichier HTML trouvé ou '#'.
     */
    private function findNeighbour(string $filename, int $offset): string
    {

        $files = $this->getFiles();

        $index = array_search($filename, $files, true);

        if ($index === false) {
            return self::NO_LINK;
        }

        $target = $files[$index + $offset] ?? null;

        return $target
            ? $this->htmlFile($target)
            : self::NO_LINK;
    }

    /**
     * Convertit un fichier Markdown en nom de fichier HTML.
     * 00-xxx.md => index.html
     *
     * @param string $filename Nom du fichier Markdown.
     *
     * @return string Nom du fichier HTML.
     */
    public function htmlFile(string $filename): string
    {
        if (str_starts_with($filename, "00")) {
            return "index.html";
        }

        return str_replace('.md', '.html', $filename);
    }

    /**
     * Convertit un fichier Markdown en nom de fichier HTML.
     * 00-xxx.md => xxx
     *
     * @param string $filename Nom du fichier Markdown.
     *
     * @return string Nom du fichier HTML.
     */

    public function getTitleFromFilename(string $filename): string
    {

        $title = pathinfo($filename, PATHINFO_FILENAME);

        $title = preg_replace('/^\d{2}-/', '', $title);

        return ucwords(str_replace('-', ' ', $title));
    }

    /**
     * Retourne le chemin complet d'un fichier source.
     *
     * @param string $filename Nom du fichier.
     *
     * @return string Chemin complet du fichier.
     */
    public function sourceFile(string $filename): string
    {
        return $this->srcDirectory . '/' . $filename;
    }
}