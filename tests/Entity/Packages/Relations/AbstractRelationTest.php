<?php

namespace App\Tests\Entity\Packages\Relations;

use App\Entity\Packages\Relations\Dependency;
use PHPUnit\Framework\TestCase;

class AbstractRelationTest extends TestCase
{
    /**
     * @param string $input
     * @param null|string $name
     * @param null|string $version
     * @dataProvider provideTargets
     */
    public function testCreateFromString(string $input, ?string $name, ?string $version)
    {
        $dependency = Dependency::createFromString($input);
        $this->assertEquals($name, $dependency->getTargetName());
        $this->assertEquals($version, $dependency->getTargetVersion());
    }

    /**
     * @return array
     */
    public function provideTargets(): array
    {
        return [
            ['php>=7.2', 'php', '>=7.2'],
            ['php:foo', 'php', null],
            ['php', 'php', null],
            ['🐱', '🐱', null]
        ];
    }
}