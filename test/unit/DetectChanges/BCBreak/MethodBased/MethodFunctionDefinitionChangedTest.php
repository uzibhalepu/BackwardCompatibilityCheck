<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodFunctionDefinitionChanged;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodFunctionDefinitionChanged
 */
final class MethodFunctionDefinitionChangedTest extends TestCase
{
    /** @var FunctionBased&MockObject */
    private $functionCheck;

    /** @var MethodBased */
    private $methodCheck;

    protected function setUp() : void
    {
        parent::setUp();

        $this->functionCheck = $this->createMock(FunctionBased::class);
        $this->methodCheck   = new MethodFunctionDefinitionChanged($this->functionCheck);
    }

    public function testWillCheckVisibleMethods() : void
    {
        /** @var ReflectionMethod&MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod&MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

        $result = Changes::fromList(Change::changed(uniqid('foo', true), true));

        $this
            ->functionCheck
            ->expects(self::any())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn($result);

        self::assertEquals($result, $this->methodCheck->__invoke($from, $to));
    }
}
