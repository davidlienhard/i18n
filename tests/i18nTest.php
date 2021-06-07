<?php declare(strict_types=1);

namespace DavidLienhard;

use DavidLienhard\i18n\i18n;
use DavidLienhard\i18n\i18nInterface;
use PHPUnit\Framework\TestCase;

class i18nTest extends TestCase
{
    /** @covers \DavidLienhard\Config\Config */
    public function testCanBeCreated(): void
    {
        $config = new i18n();
        $this->assertInstanceOf(i18n::class, $config);
        $this->assertInstanceOf(i18nInterface::class, $config);
    }
}
