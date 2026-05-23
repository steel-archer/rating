<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ModuleDependencyTest
{
    public function testCommonDoesNotDependOnClassic(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Common'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('App\Classic'));
    }
}
