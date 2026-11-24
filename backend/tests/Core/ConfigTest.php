<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testGetSupportsDotNotationAndDefaults(): void
    {
        $config = new Config(['app' => ['name' => 'X', 'debug' => false, 'empty' => null]]);

        self::assertSame('X', $config->get('app.name'));
        self::assertFalse($config->get('app.debug', true));
        self::assertSame('fallback', $config->get('app.missing', 'fallback'));
        self::assertSame('fallback', $config->get('nope.deep.path', 'fallback'));
    }

    public function testHasDistinguishesMissingFromNull(): void
    {
        $config = new Config(['app' => ['empty' => null]]);

        self::assertTrue($config->has('app.empty'));
        self::assertFalse($config->has('app.missing'));
    }

    public function testSetCreatesNestedKeys(): void
    {
        $config = new Config();
        $config->set('database.host', 'db.example');

        self::assertSame('db.example', $config->get('database.host'));
        self::assertSame(['database' => ['host' => 'db.example']], $config->all());
    }

    public function testFromDirectoryLoadsEachFileUnderItsName(): void
    {
        $dir = sys_get_temp_dir() . '/config_' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/demo.php', "<?php return ['a' => ['b' => 5]];");

        try {
            $config = Config::fromDirectory($dir);

            self::assertSame(5, $config->get('demo.a.b'));
        } finally {
            unlink($dir . '/demo.php');
            rmdir($dir);
        }
    }

    public function testProjectConfigFilesAreLoadable(): void
    {
        $config = Config::fromDirectory(dirname(__DIR__, 2) . '/config');

        self::assertTrue($config->has('app.name'));
        self::assertTrue($config->has('database.driver'));
        self::assertIsInt($config->get('database.port'));
    }
}
