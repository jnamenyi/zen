<?php
declare(strict_types=1);

namespace WoohooLabs\Zen\Tests\Container\Definition;

use PHPUnit\Framework\TestCase;
use WoohooLabs\Zen\Config\Autoload\AutoloadConfig;
use WoohooLabs\Zen\Config\FileBasedDefinition\FileBasedDefinitionConfig;
use WoohooLabs\Zen\Container\Definition\ClassDefinition;
use WoohooLabs\Zen\Container\Definition\ContextDependentDefinition;
use WoohooLabs\Zen\Container\DefinitionCompilation;
use WoohooLabs\Zen\Exception\ContainerException;
use WoohooLabs\Zen\Tests\Fixture\DependencyGraph\Constructor\ConstructorD;
use function dirname;
use function file_get_contents;
use function str_replace;

class ClassDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function singleton()
    {
        $definition = ClassDefinition::singleton("");

        $isSingleton = $definition->isSingleton("");

        $this->assertTrue($isSingleton);
    }

    /**
     * @test
     */
    public function prototype()
    {
        $definition = ClassDefinition::prototype("");

        $isSingleton = $definition->isSingleton("");

        $this->assertFalse($isSingleton);
    }

    /**
     * @test
     */
    public function getClassName()
    {
        $definition = new ClassDefinition("A\\B");

        $className = $definition->getClassName();

        $this->assertEquals("A\\B", $className);
    }

    /**
     * @test
     */
    public function needsDependencyResolutionByDefault()
    {
        $definition = new ClassDefinition("");

        $needsDependencyResolution = $definition->needsDependencyResolution();

        $this->assertTrue($needsDependencyResolution);
    }

    /**
     * @test
     */
    public function resolveDependencies()
    {
        $definition = new ClassDefinition("");

        $definition->resolveDependencies();

        $this->assertFalse($definition->needsDependencyResolution());
    }

    /**
     * @test
     */
    public function isConstructorParameterOverriddenWhenTrue()
    {
        $definition = ClassDefinition::singleton(
            "A\\B",
            false,
            false,
            false,
            [
                "param1" => "value",
                "param2" => null,
            ]
        );

        $isConstructorParameterOverridden = $definition->isConstructorParameterOverridden("param2");

        $this->assertTrue($isConstructorParameterOverridden);
    }

    /**
     * @test
     */
    public function isConstructorParameterOverriddenWhenFalse()
    {
        $definition = ClassDefinition::singleton(
            "A\\B",
            false,
            false,
            false,
            [
                "param1" => "value",
                "param2" => null,
            ]
        );

        $isConstructorParameterOverridden = $definition->isConstructorParameterOverridden("param3");

        $this->assertFalse($isConstructorParameterOverridden);
    }

    /**
     * @test
     */
    public function getOverriddenConstructorParameters()
    {
        $definition = ClassDefinition::singleton(
            "A\\B",
            false,
            false,
            false,
            [
                "param1" => "value",
                "param2" => null,
            ]
        );

        $overriddenConstructorParameters = $definition->getOverriddenConstructorParameters();

        $this->assertEquals(
            ["param1", "param2"],
            $overriddenConstructorParameters
        );
    }

    /**
     * @test
     */
    public function compileWhenUnoptimizedSingletonClass()
    {
        $definition = ClassDefinition::singleton("X\\A", false, false, false, [], [], 2);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionUnoptimizedSingleton.php"), $compiledDefinition);
    }

    /**
     * @test
     */
    public function compileWhenUnoptimizedSingletonEntryPoint()
    {
        $definition = ClassDefinition::singleton("X\\A", true, false, false, [], [], 0);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionUnoptimizedSingleton.php"), $compiledDefinition);
    }

    /**
     * @test
     */
    public function compileWhenOptimizedSingletonClass()
    {
        $definition = ClassDefinition::singleton("X\\A", false, false, false, [], [], 1);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionOptimizedSingleton.php"), $compiledDefinition);
    }

    /**
     * @test
     */
    public function compileWhenPrototypeWithOptionalConstructorDependencies()
    {
        $definition = ClassDefinition::prototype("X\\A");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWhenPrototype.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWithRequiredEntryPointConstructorDependencies()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addConstructorArgumentFromClass("X\\C");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => ClassDefinition::singleton("X\\B", true),
                    "X\\C" => ClassDefinition::singleton("X\\C", true),
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithRequiredEntryPointConstructorDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWhenPrototypeWithRequiredInlinedConstructorDependencies()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addConstructorArgumentFromClass("X\\C");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => ClassDefinition::singleton("X\\B", false),
                    "X\\C" => ClassDefinition::singleton("X\\C", false),
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithRequiredInlinedConstructorDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWhenContextDependentConstructorInjection()
    {
        $definition = ClassDefinition::singleton("X\\A", true)
            ->addConstructorArgumentFromClass("X\\B")
            ->addConstructorArgumentFromClass("X\\C");

        $this->expectException(ContainerException::class);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => new ContextDependentDefinition(
                        "X\\B",
                        null,
                        [
                            "X\\A" => ClassDefinition::singleton("X\\C", true),
                            "X\\F" => ClassDefinition::singleton("X\\D", true),
                        ]
                    ),
                    "X\\C" => ClassDefinition::singleton("X\\C", true),
                    "X\\D" => ClassDefinition::singleton("X\\D", true),
                ]
            ),
            0,
            false
        );

        /*
        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithContextDependentEntryPointConstructorDependencies.php"),
            $compiledDefinition
        );
        */
    }

    /**
     * @test
     */
    public function compileWithOptionalConstructorDependencies()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromValue("")
            ->addConstructorArgumentFromValue(true)
            ->addConstructorArgumentFromValue(0)
            ->addConstructorArgumentFromValue(1)
            ->addConstructorArgumentFromValue(1345.999)
            ->addConstructorArgumentFromValue(null)
            ->addConstructorArgumentFromValue(["a" => false]);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithOptionalConstructorDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWithOverriddenConstructorDependencies()
    {
        $definition = ClassDefinition::prototype(
            "X\\A",
            false,
            false,
            false,
            [
                "param1" => "",
                "param2" => null,
                "param3" => 0,
                "param4" => ["a" => false],
            ]
        )
            ->addConstructorArgumentFromOverride("param1")
            ->addConstructorArgumentFromOverride("param2")
            ->addConstructorArgumentFromOverride("param3")
            ->addConstructorArgumentFromOverride("param4");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithOverriddenConstructorDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWithPropertyDependencies()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addPropertyFromClass("b", "X\\B")
            ->addPropertyFromClass("c", "X\\C");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => ClassDefinition::singleton("X\\B"),
                    "X\\C" => ClassDefinition::singleton("X\\C"),
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithPropertyDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWithOverriddenPropertyDependencies()
    {
        $definition = ClassDefinition::prototype(
            "X\\A",
            false,
            false,
            false,
            [],
            [
                "b" => "abc",
                "c" => null,
                "d" => 0,
            ]
        )
            ->addPropertyFromOverride("b")
            ->addPropertyFromOverride("c")
            ->addPropertyFromOverride("d");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithOverriddenPropertyDependencies.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWhenContextDependentPropertyInjection()
    {
        $definition = ClassDefinition::singleton("X\\A")
            ->addPropertyFromClass("b", "X\\B")
            ->addPropertyFromClass("c", "X\\C");

        $this->expectException(ContainerException::class);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => new ContextDependentDefinition(
                        "X\\B",
                        null,
                        [
                            "X\\A" => new ClassDefinition("X\\C", "singleton"),
                            "X\\F" => new ClassDefinition("X\\D", "singleton"),
                        ]
                    ),
                    "X\\C" => new ClassDefinition("X\\C", "singleton"),
                    "X\\D" => new ClassDefinition("X\\D", "singleton"),
                ]
            ),
            0,
            false
        );

        /*
        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWithContextDependentPropertyDependencies.php"),
            $compiledDefinition
        );
        */
    }

    /**
     * @test
     */
    public function compileWhenMultipleReferenceForOptimizableClass()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addPropertyFromClass("b", "X\\B");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => ClassDefinition::singleton("X\\B"),
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWhenMultipleReferenceForOptimizableClass.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWhenIndented()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addPropertyFromClass("b", "X\\B")
            ->addPropertyFromClass("c", "X\\C");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    "X\\A" => $definition,
                    "X\\B" => ClassDefinition::singleton("X\\B"),
                    "X\\C" => ClassDefinition::singleton("X\\C")
                        ->addConstructorArgumentFromClass("X\\D")
                        ->addPropertyFromClass("e", "X\\E"),
                    "X\\D" => ClassDefinition::singleton("X\\D"),
                    "X\\E" => ClassDefinition::singleton("X\\E"),
                ]
            ),
            2,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWhenIndented.php"),
            $compiledDefinition
        );
    }

    /**
     * @test
     */
    public function compileWhenAutoloaded()
    {
        $definition = ClassDefinition::singleton(ConstructorD::class, true, true);

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(dirname(__DIR__, 2)),
                FileBasedDefinitionConfig::disabledGlobally(),
                [
                    ConstructorD::class => $definition,
                ]
            ),
            0,
            false
        );

        $this->assertEquals(
            $this->getDefinitionSourceCode("ClassDefinitionWhenAutoloaded.php"),
            $compiledDefinition
        );
    }

    private function getDefinitionSourceCode(string $fileName): string
    {
        return str_replace("<?php\n", "", file_get_contents(dirname(__DIR__, 2) . "/Fixture/Definition/" . $fileName));
    }
}
