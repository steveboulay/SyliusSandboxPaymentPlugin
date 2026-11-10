<?php

declare(strict_types=1);

use SteveBoulay\SyliusSandboxPaymentPlugin\CommandHandler\CapturePaymentRequestHandler;
use SteveBoulay\SyliusSandboxPaymentPlugin\CommandProvider\CapturePaymentRequestCommandProvider;
use SteveBoulay\SyliusSandboxPaymentPlugin\Controller\SimulatedPaymentPageAction;
use SteveBoulay\SyliusSandboxPaymentPlugin\OrderPay\Provider\CaptureHttpResponseProvider;
use Sylius\Bundle\PaymentBundle\CommandProvider\ActionsCommandProvider;
use Sylius\Bundle\PaymentBundle\Provider\ActionsHttpResponseProvider;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('steve_boulay_sylius_sandbox_payment.command_provider', ActionsCommandProvider::class)
        ->args([tagged_locator('steve_boulay_sylius_sandbox_payment.command_provider', 'action')])
        ->tag('sylius.payment_request.command_provider', ['gateway_factory' => 'sandbox']);

    $services->set('steve_boulay_sylius_sandbox_payment.command_provider.capture', CapturePaymentRequestCommandProvider::class)
        ->tag('steve_boulay_sylius_sandbox_payment.command_provider', ['action' => PaymentRequestInterface::ACTION_CAPTURE]);

    $services->set('steve_boulay_sylius_sandbox_payment.command_handler.capture', CapturePaymentRequestHandler::class)
        ->args([
            service('sylius.provider.payment_request'),
            service('router'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.payment_request.command_bus']);

    $services->set('steve_boulay_sylius_sandbox_payment.provider.http_response', ActionsHttpResponseProvider::class)
        ->args([tagged_locator('steve_boulay_sylius_sandbox_payment.provider.http_response', 'action')])
        ->tag('sylius.payment_request.provider.http_response', ['gateway_factory' => 'sandbox']);

    $services->set('steve_boulay_sylius_sandbox_payment.provider.http_response.capture', CaptureHttpResponseProvider::class)
        ->tag('steve_boulay_sylius_sandbox_payment.provider.http_response', ['action' => PaymentRequestInterface::ACTION_CAPTURE]);

    $services->set(SimulatedPaymentPageAction::class)
        ->args([service('sylius.repository.payment_request'), service('twig')])
        ->public();
};
