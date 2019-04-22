<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use function getcwd;
use function realpath;

/**
 * @covers \Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer
 */
final class LocateDependenciesViaComposerTest extends TestCase
{
    /** @var string */
    private $originalCwd;

    /** @var callable */
    private $makeInstaller;

    /** @var Installer|MockObject */
    private $composerInstaller;

    /** @var string|null */
    private $expectedInstallatonPath;

    /** @var Locator */
    private $astLocator;

    /** @var LocateDependenciesViaComposer */
    private $locateDependencies;

    protected function setUp() : void
    {
        parent::setUp();

        $originalCwd = getcwd();

        self::assertInternalType('string', $originalCwd);

        $this->originalCwd       = $originalCwd;
        $this->composerInstaller = $this->createMock(Installer::class);
        $this->astLocator        = (new BetterReflection())->astLocator();
        $this->makeInstaller     = function (string $installationPath) : Installer {
            self::assertSame($this->expectedInstallatonPath, $installationPath);

            return $this->composerInstaller;
        };

        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDevMode')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDumpAutoloader')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setRunScripts')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setIgnorePlatformRequirements')
            ->with(true);

        $this->locateDependencies = new LocateDependenciesViaComposer($this->makeInstaller, $this->astLocator);
    }

    protected function tearDown() : void
    {
        self::assertSame($this->originalCwd, getcwd());

        parent::tearDown();
    }

    public function testWillLocateDependencies() : void
    {
        $this->expectedInstallatonPath = $this->realpath(__DIR__ . '/../../asset/composer-installation-structure');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function () : void {
                self::assertSame($this->expectedInstallatonPath, getcwd());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($this->expectedInstallatonPath);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(2, $locators);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[1]);
    }

    private function realpath(string $path) : string
    {
        $realPath = realpath($path);

        self::assertInternalType('string', $realPath);

        return $realPath;
    }
}
