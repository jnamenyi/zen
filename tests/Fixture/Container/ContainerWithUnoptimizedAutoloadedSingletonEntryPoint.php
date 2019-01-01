<?php
namespace WoohooLabs\Zen\Tests\Fixture\Container;

use WoohooLabs\Zen\AbstractCompiledContainer;

class ContainerWithUnoptimizedAutoloadedSingletonEntryPoint extends AbstractCompiledContainer
{
    /**
     * @var string[]
     */
    protected static $entryPoints = [
        \WoohooLabs\Zen\Tests\Double\StubSingletonDefinition::class => '_proxy__WoohooLabs__Zen__Tests__Double__StubSingletonDefinition',
    ];

    /**
     * @var string
     */
    protected $rootDirectory;

    public function __construct(string $rootDirectory = '')
    {
        $this->rootDirectory = $rootDirectory;
    }

    public function _proxy__WoohooLabs__Zen__Tests__Double__StubSingletonDefinition()
    {
        include_once $this->rootDirectory . '/src/Container/Definition/DefinitionInterface.php';
        include_once $this->rootDirectory . '/src/Container/Definition/AbstractDefinition.php';
        include_once $this->rootDirectory . '/tests/Double/TestDefinition.php';
        include_once $this->rootDirectory . '/tests/Double/StubSingletonDefinition.php';

        self::$entryPoints[\WoohooLabs\Zen\Tests\Double\StubSingletonDefinition::class] = 'WoohooLabs__Zen__Tests__Double__StubSingletonDefinition';

        return $this->WoohooLabs__Zen__Tests__Double__StubSingletonDefinition();
    }

    public function WoohooLabs__Zen__Tests__Double__StubSingletonDefinition()
    {
        // This is a dummy definition.
    }
}
