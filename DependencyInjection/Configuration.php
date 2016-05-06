<?php

namespace Niif\ShibAuthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('niif_shib_auth');
        $rootNode
            ->children()
                ->scalarNode('baseURL')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('sessionInitiator')
                    ->defaultValue('Shibbholeth.sso/DSS')
                ->end()
                ->scalarNode('logoutPath')
                    ->defaultValue('Shibboleth.sso/Logout')
                ->end()
                ->scalarNode('usernameAttribute')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
}
