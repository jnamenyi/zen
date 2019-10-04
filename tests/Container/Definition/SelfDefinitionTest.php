<?php

declare(strict_types=1);

namespace WoohooLabs\Zen\Tests\Container\Definition;

use PHPUnit\Framework\TestCase;
use WoohooLabs\Zen\Config\Autoload\AutoloadConfig;
use WoohooLabs\Zen\Config\FileBasedDefinition\FileBasedDefinitionConfig;
use WoohooLabs\Zen\Container\Definition\DefinitionInterface;
use WoohooLabs\Zen\Container\Definition\SelfDefinition;
use WoohooLabs\Zen\Container\DefinitionCompilation;
use WoohooLabs\Zen\Container\DefinitionInstantiation;
use WoohooLabs\Zen\RuntimeContainer;
use WoohooLabs\Zen\Tests\Double\DummyCompilerConfig;

use function dirname;
use function file_get_contents;
use function str_replace;
use function substr;

class SelfDefinitionTest extends TestCase
{
    /**
     * @test
     */
    public function isSingleton(): void
    {
        $definition = new SelfDefinition("");

        $singleton = $definition->isSingleton("");

        $this->assertTrue($singleton);
    }

    /**
     * @test
     */
    public function isEntryPoint(): void
    {
        $definition = new SelfDefinition("");

        $isEntryPoint = $definition->isEntryPoint();

        $this->assertFalse($isEntryPoint);
    }

    /**
     * @test
     */
    public function isAutoloaded(): void
    {
        $definition = new SelfDefinition("");

        $isAutoloaded = $definition->isAutoloaded();

        $this->assertFalse($isAutoloaded);
    }

    /**
     * @test
     */
    public function isFileBased(): void
    {
        $definition = new SelfDefinition("");

        $isFileBased = $definition->isFileBased();

        $this->assertFalse($isFileBased);
    }

    /**
     * @test
     */
    public function getSingletonReferenceCount(): void
    {
        $definition = new SelfDefinition("");

        $referenceCount = $definition->getSingletonReferenceCount();

        $this->assertEquals(0, $referenceCount);
    }

    /**
     * @test
     */
    public function increaseReferenceCount(): void
    {
        $definition = new SelfDefinition("");

        $definition
            ->increaseReferenceCount("", true)
            ->increaseReferenceCount("", false);

        $this->assertEquals(0, $definition->getSingletonReferenceCount());
        $this->assertEquals(0, $definition->getPrototypeReferenceCount());
    }

    /**
     * @test
     */
    public function needsDependencyResolution(): void
    {
        $definition = new SelfDefinition("");

        $needsDependencyResolution = $definition->needsDependencyResolution();

        $this->assertFalse($needsDependencyResolution);
    }

    /**
     * @test
     */
    public function resolveDependencies(): void
    {
        $definition = new SelfDefinition("");

        $result = $definition->resolveDependencies();

        $this->assertSame($definition, $result);
    }

    /**
     * @test
     */
    public function getClassDependencies(): void
    {
        $definition = new SelfDefinition("");

        $classDependencies = $definition->getClassDependencies();

        $this->assertEmpty($classDependencies);
    }

    /**
     * @test
     */
    public function instantiate(): void
    {
        $definition = new SelfDefinition("");

        $object = $definition->instantiate($this->createDefinitionInstantiation([]), "");

        $this->assertInstanceOf(RuntimeContainer::class, $object);
    }

    /**
     * @test
     */
    public function compile(): void
    {
        $definition = new SelfDefinition("");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                []
            ),
            "",
            0,
            false
        );

        $this->assertEquals($this->getDefinitionSourceCode("SelfDefinition.php"), $compiledDefinition);
    }

    /**
     * @test
     */
    public function compileWhenIndented(): void
    {
        $definition = new SelfDefinition("");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                []
            ),
            "",
            2,
            false
        );

        $this->assertEquals($this->getDefinitionSourceCode("SelfDefinitionWhenIndented.php"), $compiledDefinition);
    }

    /**
     * @test
     */
    public function compileWhenInlined(): void
    {
        $definition = new SelfDefinition("");

        $compiledDefinition = $definition->compile(
            new DefinitionCompilation(
                AutoloadConfig::disabledGlobally(),
                FileBasedDefinitionConfig::disabledGlobally(),
                []
            ),
            "",
            0,
            true
        );

        $this->assertEquals($this->getInlinedDefinitionSourceCode("SelfDefinitionWhenInlined.php"), $compiledDefinition);
    }

    /**
     * @param DefinitionInterface[] $definitions
     */
    private function createDefinitionInstantiation(array $definitions): DefinitionInstantiation
    {
        $instantiation = new DefinitionInstantiation(new RuntimeContainer(new DummyCompilerConfig()));
        $instantiation->definitions = $definitions;

        return $instantiation;
    }

    private function getDefinitionSourceCode(string $fileName): string
    {
        return str_replace("<?php\n", "", file_get_contents(dirname(__DIR__, 2) . "/Fixture/Definition/" . $fileName));
    }

    private function getInlinedDefinitionSourceCode(string $fileName): string
    {
        return substr($this->getDefinitionSourceCode($fileName), 0, -2);
    }
}
