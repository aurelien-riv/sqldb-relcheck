<?php

namespace DBChecker\modules\FileCheck;

use DBChecker\Config;
use DBChecker\ModuleInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class FileCheckModule implements ModuleInterface
{
    protected $configuration;
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getName()
    {
        return 'filecheck';
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root($this->getName())
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('enable_remotes')
                    ->defaultFalse()
                    ->info("If true, http and https URL will be fetched to detect 4xx and 5xx errors")
                ->end()
                ->arrayNode('ssh')
                    ->children()
                        ->scalarNode('host')->end()
                        ->integerNode('port')->defaultValue(22)->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('password')->defaultNull()->end()
                        ->scalarNode('pkey_file')->defaultNull()->end()
                        ->scalarNode('pkey_passphrase')
                            ->defaultNull()
                            ->info('Passphrase for the private key, or "prompt" for interactive')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mapping')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->arrayPrototype()
                        ->useAttributeAsKey('key')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        return $treeBuilder;
    }

    public function loadConfig(array $config)
    {
        return $config;
    }

    public function getWorker()
    {
        return new FileCheck($this->config);
    }
}