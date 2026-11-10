<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\Controller;

use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final readonly class SimulatedPaymentPageAction
{
    private const TEMPLATE = '@SteveBoulaySyliusSandboxPaymentPlugin/shop/simulated_payment.html.twig';

    /**
     * @param PaymentRequestRepositoryInterface<PaymentRequestInterface> $paymentRequestRepository
     */
    public function __construct(
        private PaymentRequestRepositoryInterface $paymentRequestRepository,
        private Environment $twig,
    ) {
    }

    public function __invoke(string $hash): Response
    {
        $paymentRequest = $this->paymentRequestRepository->find($hash);

        if (!$paymentRequest instanceof PaymentRequestInterface) {
            throw new NotFoundHttpException('Payment request not found.');
        }

        return new Response($this->twig->render(self::TEMPLATE, [
            'payment' => $paymentRequest->getPayment(),
        ]));
    }
}
