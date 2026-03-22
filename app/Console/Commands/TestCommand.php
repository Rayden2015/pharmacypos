<?php

namespace App\Console\Commands;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand as CollisionTestCommand;

/**
 * Wraps Collision's test runner so PHPUnit runs with --prepend before vendor/autoload.php,
 * suppressing PHP 8.4 implicit-nullable deprecations from Laravel 8 / Symfony vendor code.
 */
class TestCommand extends CollisionTestCommand
{
    protected function phpunitArguments($options)
    {
        $args = parent::phpunitArguments($options);

        array_unshift($args, '--prepend='.base_path('tests/phpunit-prepend.php'));

        return $args;
    }
}
