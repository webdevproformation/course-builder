<?php

declare(strict_types=1);

namespace CourseBuilder;

final class Log
{
    public function msg(string $txt): void
    {
        echo $txt . PHP_EOL;
    }
}