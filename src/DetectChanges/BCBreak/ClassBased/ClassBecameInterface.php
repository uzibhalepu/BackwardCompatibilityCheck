<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * A class cannot become an interface without introducing an explicit BC break, since
 * all child classes or implementors need to be changed from `extends` to `implements`,
 * and all instantiations start failing
 */
final class ClassBecameInterface implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if ($fromClass->isInterface() || ! $toClass->isInterface()) {
            // checking whether a class became an interface is done in `InterfaceBecameClass`
            return Changes::empty();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Class %s became an interface', $fromClass->getName()),
            true
        ),
        ]);
    }
}
