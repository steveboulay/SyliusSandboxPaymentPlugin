<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $container->import('services/**');
    $services->set(\SteveBoulay\SyliusSandboxPaymentPlugin\Controller\GreetingController::class)
        ->public()
        ->autowire()
        ->autoconfigure();
};
