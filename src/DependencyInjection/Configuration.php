<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('steve_boulay_sylius_sandbox_payment');

        return $treeBuilder;
    }
}
