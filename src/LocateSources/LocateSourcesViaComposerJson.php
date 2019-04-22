<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\LocateSources;

use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJson;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class LocateSourcesViaComposerJson implements LocateSources
{
    /** @var Locator */
    private $astLocator;

    public function __construct(Locator $astLocator)
    {
        $this->astLocator = $astLocator;
    }

    public function __invoke(string $installationPath) : SourceLocator
    {
        return (new MakeLocatorForComposerJson())
            ->__invoke($installationPath, $this->astLocator);
    }
}
