<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_combine;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_values;
use function sprintf;

final class MethodVisibilityReduced implements ClassBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $visibilitiesFrom = $this->methodVisibilities($fromClass);
        $visibilitiesTo   = $this->methodVisibilities($toClass);
        $sharedKeys       = array_keys(array_intersect_key($visibilitiesFrom, $visibilitiesTo));

        $affectedVisibilities = array_filter(
            array_combine(
                $sharedKeys,
                array_map(
                    function (string $propertyName) use ($visibilitiesFrom, $visibilitiesTo) : array {
                        return [$visibilitiesFrom[$propertyName], $visibilitiesTo[$propertyName]];
                    },
                    $sharedKeys
                )
            ),
            function (array $visibilities) : bool {
                // Note: works because public, protected and private are (luckily) sortable
                return $visibilities[0] > $visibilities[1];
            }
        );

        return Changes::fromArray(array_values(array_map(function (
            string $methodName,
            array $visibilities
        ) use (
            $fromClass
        ) : Change {
            return Change::changed(
                sprintf(
                    'Method %s#%s() changed visibility from %s to %s',
                    $fromClass->getName(),
                    $fromClass->getMethod($methodName)->getName(),
                    $visibilities[0],
                    $visibilities[1]
                ),
                true
            );
        }, array_keys($affectedVisibilities), $affectedVisibilities)));
    }

    /** @return string[] */
    private function methodVisibilities(ReflectionClass $class) : array
    {
        $methods = $class->getMethods();

        return array_combine(
            array_map(function (ReflectionMethod $method) : string {
                return $method->getName();
            }, $methods),
            array_map(function (ReflectionMethod $method) : string {
                if ($method->isPublic()) {
                    return self::VISIBILITY_PUBLIC;
                }

                if ($method->isProtected()) {
                    return self::VISIBILITY_PROTECTED;
                }

                return self::VISIBILITY_PRIVATE;
            }, $methods)
        );
    }
}
