<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\CommandHandler;

use SteveBoulay\SyliusSandboxPaymentPlugin\Command\CapturePaymentRequest;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CapturePaymentRequestHandler
{
    public const SIMULATED_PAYMENT_PAGE_ROUTE = 'steve_boulay_sylius_sandbox_payment_simulated_payment_page';

    public function __construct(
        private PaymentRequestProviderInterface $paymentRequestProvider,
        private UrlGeneratorInterface $urlGenerator,
        private StateMachineInterface $stateMachine,
    ) {
    }

    public function __invoke(CapturePaymentRequest $command): void
    {
        $paymentRequest = $this->paymentRequestProvider->provide($command);

        if (PaymentRequestInterface::STATE_PROCESSING === $paymentRequest->getState()) {
            return;
        }

        $url = $this->urlGenerator->generate(
            self::SIMULATED_PAYMENT_PAGE_ROUTE,
            ['hash' => $paymentRequest->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $paymentRequest->setResponseData(['url' => $url]);

        $this->stateMachine->apply(
            $paymentRequest,
            PaymentRequestTransitions::GRAPH,
            PaymentRequestTransitions::TRANSITION_PROCESS,
        );
    }
}
