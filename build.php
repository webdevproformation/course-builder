<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use CourseBuilder\HtmlGenerator;
use CourseBuilder\CourseRepository;
use CourseBuilder\Log;

$repository = new CourseRepository('parts');

$options = getopt('A');

$generator = new HtmlGenerator(
    repository: $repository,
    regex: '/static(.+)\:\:\: \{\.js\-courseSelementActions \.sideActions\}/ms',
    logService: new Log(),
    all: isset($options['A'])
);

$generator->generate();