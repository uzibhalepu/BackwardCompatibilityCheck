<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class OpenClassChanged implements ClassBased
{
    /** @var ClassBased */
    private $checkClass;

    public function __construct(ClassBased $checkClass)
    {
        $this->checkClass = $checkClass;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if ($fromClass->isFinal()) {
            return Changes::empty();
        }

        return $this->checkClass->__invoke($fromClass, $toClass);
    }
}
