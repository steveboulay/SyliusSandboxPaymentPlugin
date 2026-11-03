<?php

declare(strict_types=1);

use SteveBoulay\SyliusSandboxPaymentPlugin\Form\Type\SandboxGatewayConfigurationType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('steve_boulay_sylius_sandbox_payment.form.type.gateway_configuration', SandboxGatewayConfigurationType::class)
        ->tag('sylius.gateway_configuration_type', [
            'type' => 'sandbox',
            'label' => 'steve_boulay_sylius_sandbox_payment.gateway_factory.sandbox',
        ])
        ->tag('form.type');
};
