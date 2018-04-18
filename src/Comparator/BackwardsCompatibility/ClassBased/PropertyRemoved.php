<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function sprintf;

final class PropertyRemoved implements ClassBased
{
    /** @var ReflectionPropertyName */
    private $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $fromProperties    = $this->accessibleProperties($fromClass);
        $removedProperties = array_diff(
            array_keys($fromProperties),
            array_keys($this->accessibleProperties($toClass))
        );

        return Changes::fromArray(array_values(array_map(function (string $property) use ($fromProperties) : Change {
            return Change::removed(
                sprintf('Property %s was removed', $this->formatProperty->__invoke($fromProperties[$property])),
                true
            );
        }, $removedProperties)));
    }

    /** @return ReflectionProperty[] */
    private function accessibleProperties(ReflectionClass $class) : array
    {
        return array_filter($class->getProperties(), function (ReflectionProperty $property) : bool {
            return $property->isPublic() || $property->isProtected();
        });
    }
}
