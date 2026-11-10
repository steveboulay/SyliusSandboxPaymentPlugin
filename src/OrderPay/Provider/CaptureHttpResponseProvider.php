<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\OrderPay\Provider;

use Sylius\Bundle\PaymentBundle\Provider\HttpResponseProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CaptureHttpResponseProvider implements HttpResponseProviderInterface
{
    public function supports(
        Request|RequestConfiguration $request,
        PaymentRequestInterface $paymentRequest,
    ): bool {
        return PaymentRequestInterface::STATE_PROCESSING === $paymentRequest->getState();
    }

    public function getResponse(
        Request|RequestConfiguration $request,
        PaymentRequestInterface $paymentRequest,
    ): Response {
        $url = $paymentRequest->getResponseData()['url'] ?? null;

        if (!is_string($url) || '' === $url) {
            throw new \LogicException('The simulated payment page URL is missing.');
        }

        return new RedirectResponse($url, Response::HTTP_SEE_OTHER);
    }
}
