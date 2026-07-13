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

        $menu = "";
        $i = 0 ;
        foreach($files as $key => $file){
            $link = htmlentities($this->repository->htmlFile($file));
            $label = htmlentities($this->repository->getTitleFromFilename($file));
            if($i === 0){
                $menu .= "<ul>".PHP_EOL;
                $menu .= "<li> Sommaire :".PHP_EOL;
                $menu .= "<ol start='". $key+1 . "'>".PHP_EOL;
            }else if($i % 5 === 0 && $i !== count($files) - 1){
                $menu .= "</ol>".PHP_EOL;
                $menu .= "</li>".PHP_EOL;
                $menu .= "</ul>".PHP_EOL;
                $menu .= "<ul>".PHP_EOL;
                $menu .= "<li> &nbsp;".PHP_EOL;
                $menu .= "<ol start='". $key+1 . "'>".PHP_EOL;
            }
            $menu .= "<li><a href='$link'>$label</a></li>". PHP_EOL;
            if($i === count($files) - 1){
                $menu .= "</ol>".PHP_EOL;
                $menu .= "</li>".PHP_EOL;
                $menu .= "</ul>".PHP_EOL;
            }
            $i++ ; 
        } 
         $menuPath = $this->rootDir. DIRECTORY_SEPARATOR . "menu.html";
        if(file_exists($menuPath)) unlink($menuPath) ;
        file_put_contents($menuPath , $menu);
        $this->logService->msg("✅ {$menuPath}");

    }
}