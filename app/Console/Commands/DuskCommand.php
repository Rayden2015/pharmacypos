<?php

namespace App\Console\Commands;

use Laravel\Dusk\Console\DuskCommand as LaravelDuskCommand;

/**
 * Ensures PHPUnit loads tests/phpunit-prepend.php before vendor/autoload.php (PHP 8.4 deprecations).
 */
class DuskCommand extends LaravelDuskCommand
{
    protected function phpunitArguments($options)
    {
        $args = parent::phpunitArguments($options);

        array_unshift($args, '--prepend='.base_path('tests/phpunit-prepend.php'));

        return $args;
    }
}
