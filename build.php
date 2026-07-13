<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use CourseBuilder\HtmlGenerator;
use CourseBuilder\CourseRepository;
use CourseBuilder\Log;
use CourseBuilder\MenuGenerator;

$rootDir = dirname(__DIR__);

$dossier_contenant_mds = $rootDir . DIRECTORY_SEPARATOR . "parts";

$repository = new CourseRepository($dossier_contenant_mds);

$options = getopt('A');

$fichiersMd = new HtmlGenerator(
    repository: $repository,
    regex: '/static(.+)\:\:\: \{\.js\-courseSelementActions \.sideActions\}/ms',
    logService: new Log(),
    all: isset($options['A'])
);

$fichiersMd->generate();

$menuTop = new MenuGenerator(
    rootDir : $rootDir ,
    repository: $repository,
    logService: new Log(),
);

$menuTop->generate();