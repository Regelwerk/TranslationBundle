<?php

namespace Regelwerk\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('regelwerk_translation');
        $rootNode->children()
                ->scalarNode('state_dir')
                    ->defaultValue('regelwerk_translation_state')
                    ->cannotBeEmpty()
                    ->info('Where shell we keep the state information for the translation files (relative to translation files\' directory)?')
                ->end()
                ->scalarNode('bundle')
                    ->defaultValue('RegelwerkTranslationBundle')
                    ->cannotBeEmpty()
                    ->info('In which Bundle are the translation files?')
                ->end()
                ->scalarNode('source_language')
                    ->defaultValue('en')
                    ->cannotBeEmpty()
                    ->info('What is the source language for all other tanslation files?')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
