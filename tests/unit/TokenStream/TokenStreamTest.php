<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\tests\unit\TokenStream;

use ArrayIterator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use nicoSWD\Rule\Grammar\CallableFunction;
use nicoSWD\Rule\Parser\Exception\ParserException;
use nicoSWD\Rule\TokenStream\AST;
use nicoSWD\Rule\TokenStream\Exception\UndefinedFunctionException;
use nicoSWD\Rule\TokenStream\Exception\UndefinedMethodException;
use nicoSWD\Rule\TokenStream\Exception\UndefinedVariableException;
use nicoSWD\Rule\TokenStream\Token\BaseToken;
use nicoSWD\Rule\TokenStream\Token\TokenFunction;
use nicoSWD\Rule\TokenStream\Token\TokenMethod;
use nicoSWD\Rule\TokenStream\Token\TokenString;
use nicoSWD\Rule\TokenStream\Token\TokenVariable;
use nicoSWD\Rule\TokenStream\TokenStream;
use PHPUnit\Framework\TestCase;

class TokenStreamTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    
    /** @var ArrayIterator|MockInterface */
    private $stack;
    /** @var AST|MockInterface */
    private $ast;
    /** @var TokenStream */
    private $tokenStream;

    protected function setUp()
    {
        $this->stack = \Mockery::mock(ArrayIterator::class);
        $this->ast = \Mockery::mock(AST::class);

        $this->tokenStream = new TokenStream($this->stack, $this->ast);
    }

    public function testGivenAStackWhenNotEmptyItShouldBeIterable()
    {
        $this->stack->shouldReceive('rewind');
        $this->stack->shouldReceive('valid')->andReturn(true, true, true, false);
        $this->stack->shouldReceive('key')->andReturn(1, 2, 3);
        $this->stack->shouldReceive('next');
        $this->stack->shouldReceive('seek');
        $this->stack->shouldReceive('current')->times(5)->andReturn(
            new TokenString('a'),
            new TokenMethod('.foo('),
            new TokenString('b')
        );

        foreach ($this->tokenStream as $key => $value) {
            $this->assertInstanceOf(BaseToken::class, $value);
        }
    }

    public function testGivenATokenStackItShouldBeAccessibleViaGetter()
    {
        $this->assertInstanceOf(ArrayIterator::class, $this->tokenStream->getStack());
    }

    public function testGivenAVariableNameWhenFoundItShouldReturnItsValue()
    {
        $this->ast->shouldReceive('getVariable')->once()->with('foo')->andReturn(new TokenVariable('bar'));

        $token = $this->tokenStream->getVariable('foo');
        $this->assertInstanceOf(TokenVariable::class, $token);
    }

    public function testGivenAVariableNameWhenNotFoundItShouldThrowAnException()
    {
        $this->expectException(ParserException::class);

        $this->ast->shouldReceive('getVariable')->once()->with('foo')->andThrow(new UndefinedVariableException());
        $this->stack->shouldReceive('current')->once()->andReturn(new TokenVariable('nope'));

        $this->tokenStream->getVariable('foo');
    }

    public function testGivenAFunctionNameWhenFoundItShouldACallableClosure()
    {
        $this->ast->shouldReceive('getFunction')->once()->with('foo')->andReturn(function (): int {
            return 42;
        });

        $function = $this->tokenStream->getFunction('foo');
        $this->assertSame(42, $function());
    }

    public function testGivenAFunctionNameWhenNotFoundItShouldThrowAnException()
    {
        $this->expectException(ParserException::class);

        $this->ast->shouldReceive('getFunction')->once()->with('foo')->andThrow(new UndefinedFunctionException());
        $this->stack->shouldReceive('current')->once()->andReturn(new TokenFunction('nope('));

        $this->tokenStream->getFunction('foo');
    }

    public function testGivenAMethodNameWhenFoundItShouldReturnAnInstanceOfCallableFunction()
    {
        $token = new TokenString('bar');
        $callableFunction = \Mockery::mock(CallableFunction::class);

        $this->ast->shouldReceive('getMethod')->once()->with('foo', $token)->andReturn($callableFunction);

        $method = $this->tokenStream->getMethod('foo', $token);

        $this->assertInstanceOf(CallableFunction::class, $method);
    }

    public function testGivenAMethodNameWhenNotFoundItShouldThrowAnException()
    {
        $this->expectException(ParserException::class);

        $token = new TokenString('bar');
        $this->ast->shouldReceive('getMethod')->once()->with('foo', $token)->andThrow(new UndefinedMethodException());
        $this->stack->shouldReceive('current')->once()->andReturn(new TokenFunction('bar'));

        $this->tokenStream->getMethod('foo', $token);
    }
}
