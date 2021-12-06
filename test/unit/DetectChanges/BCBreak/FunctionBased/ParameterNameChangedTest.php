<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterNameChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterNameChanged
 */
final class ParameterNameChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider functionsToBeTested
     */
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages
    ): void {
        $changes = (new ParameterNameChanged())
            ->__invoke($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionMethod|ReflectionFunction,
     *     1: ReflectionMethod|ReflectionFunction,
     *     2: list<string>
     * }>
     */
    public function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function changed(int $a, int $b) {}
   function untouched(int $a, int $b) {}
   /** @no-named-arguments */
   function changedButAnnotated(int $a, int $b) {}
   /** @no-named-arguments */
   function removingAnnotation(int $a, int $b) {}
   function addingAnnotation(int $a, int $b) {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function changed(int $c, int $d) {}
   function untouched(int $a, int $b) {}
   /** @no-named-arguments */
   function changedButAnnotated(int $c, int $d) {}
   function removingAnnotation(int $a, int $b) {}
   /** @no-named-arguments */
   function addingAnnotation(int $a, int $b) {}
}
PHP
            ,
            $astLocator
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'changed'      => [
                '[BC] CHANGED: Parameter 0 of changed() changed name from a to c',
                '[BC] CHANGED: Parameter 1 of changed() changed name from b to d',
            ],
            'untouched' => [],
            'changedButAnnotated' => [],
            'removingAnnotation'      => ['[BC] REMOVED: The @no-named-arguments annotation was removed from removingAnnotation()'],
            'addingAnnotation'      => ['[BC] ADDED: The @no-named-arguments annotation was added from addingAnnotation()'],
        ];

        return array_combine(
            array_keys($functions),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $function, array $errorMessages) use ($fromReflector, $toReflector): array {
                    return [
                        $fromReflector->reflectFunction($function),
                        $toReflector->reflectFunction($function),
                        $errorMessages,
                    ];
                },
                array_keys($functions),
                $functions
            )
        );
    }
}
