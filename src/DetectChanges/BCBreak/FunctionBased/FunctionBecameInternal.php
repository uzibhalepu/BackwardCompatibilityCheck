<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Psl\Str;
use Psl\Regex;

/**
 * A function that is marked internal is no available to downstream consumers.
 */
final class FunctionBecameInternal implements FunctionBased
{
    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        if (
            $this->isInternalDocComment($toFunction->getDocComment())
            && ! $this->isInternalDocComment($fromFunction->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                Str\format(
                    '%s was marked "@internal"',
                    $this->formatFunction->__invoke($fromFunction),
                ),
                true
            ));
        }

        return Changes::empty();
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
