<?php
declare(strict_types=1);

namespace WoohooLabs\Zen\Tests\Container\Definition;

use PHPUnit\Framework\TestCase;
use WoohooLabs\Zen\Container\Definition\ClassDefinition;
use WoohooLabs\Zen\Container\Definition\ContextDependentDefinition;

class ClassDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function getHash()
    {
        $definition = new ClassDefinition("A\\B");

        $hash = $definition->getHash("");

        $this->assertEquals("A__B", $hash);
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
    public function singletonClassToPhpCode()
    {
        $definition = new ClassDefinition("X\\A");

        $phpCode = $definition->toPhpCode([$definition->getId("") => $definition]);

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionSingleton.php"), $phpCode);
    }

    /**
     * @test
     */
    public function prototypeWithRequiredConstructorDependenciesToPhpCode()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addConstructorArgumentFromClass("X\\C");

        $phpCode = $definition->toPhpCode(
            [
                "X\\A" => $definition,
                "X\\B" => ClassDefinition::singleton("X\\B"),
                "X\\C" => ClassDefinition::singleton("X\\C"),
            ]
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionWithRequiredConstructorDependencies.php"), $phpCode);
    }

    /**
     * @test
     */
    public function contextDependentConstructorInjectionToPhpCode()
    {
        $definition = ClassDefinition::singleton("X\\A")
            ->addConstructorArgumentFromClass("X\\B")
            ->addConstructorArgumentFromClass("X\\C");

        $phpCode = $definition->toPhpCode(
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
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionWithContextDependentConstructorDependencies.php"), $phpCode);
    }

    /**
     * @test
     */
    public function prototypeWithOptionalConstructorDependenciesToPhpCode()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addConstructorArgumentFromValue("")
            ->addConstructorArgumentFromValue(true)
            ->addConstructorArgumentFromValue(0)
            ->addConstructorArgumentFromValue(1)
            ->addConstructorArgumentFromValue(1345.999)
            ->addConstructorArgumentFromValue(null)
            ->addConstructorArgumentFromValue(["a" => false]);

        $phpCode = $definition->toPhpCode(
            [
                "X\\A" => $definition,
            ]
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionWithOptionalConstructorDependencies.php"), $phpCode);
    }

    /**
     * @test
     */
    public function prototypeWithPropertyDependenciesToPhpCode()
    {
        $definition = ClassDefinition::prototype("X\\A")
            ->addPropertyFromClass("b", "X\\B")
            ->addPropertyFromClass("c", "X\\C");

        $phpCode = $definition->toPhpCode(
            [
                "X\\A" => $definition,
                "X\\B" => ClassDefinition::singleton("X\\B"),
                "X\\C" => ClassDefinition::singleton("X\\C"),
            ]
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionWithPropertyDependencies.php"), $phpCode);
    }

    /**
     * @test
     */
    public function contextDependentPropertyInjectionToPhpCode()
    {
        $definition = ClassDefinition::singleton("X\\A")
            ->addPropertyFromClass("b", "X\\B")
            ->addPropertyFromClass("c", "X\\C");

        $phpCode = $definition->toPhpCode(
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
        );

        $this->assertEquals($this->getDefinitionSourceCode("ClassDefinitionWithContextDependentPropertyDependencies.php"), $phpCode);
    }

    private function getDefinitionSourceCode(string $fileName)
    {
        return str_replace("<?php\n", "", file_get_contents(dirname(__DIR__, 2) . "/Fixture/Definition/" . $fileName));
    }
}