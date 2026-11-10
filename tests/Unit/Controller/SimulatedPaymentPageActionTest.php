<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\Controller\SimulatedPaymentPageAction;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class SimulatedPaymentPageActionTest extends TestCase
{
    private const HASH = '0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    private const TEMPLATE = '@SteveBoulaySyliusSandboxPaymentPlugin/shop/simulated_payment.html.twig';

    private const HTML = '<p>Simulated payment page</p>';

    #[Test]
    public function it_renders_the_simulated_payment_page(): void
    {
        $payment = $this->createMock(PaymentInterface::class);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getPayment')->willReturn($payment);

        $paymentRequestRepository = $this->createMock(PaymentRequestRepositoryInterface::class);
        $paymentRequestRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::HASH)
            ->willReturn($paymentRequest);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects(self::once())
            ->method('render')
            ->with(self::TEMPLATE, ['payment' => $payment])
            ->willReturn(self::HTML);

        $action = new SimulatedPaymentPageAction($paymentRequestRepository, $twig);

        $response = $action(self::HASH);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(self::HTML, $response->getContent());
    }

    #[Test]
    public function it_returns_a_404_for_an_unknown_payment_request(): void
    {
        $paymentRequestRepository = $this->createMock(PaymentRequestRepositoryInterface::class);
        $paymentRequestRepository
            ->expects(self::once())
            ->method('find')
            ->with(self::HASH)
            ->willReturn(null);

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::never())->method('render');

        $action = new SimulatedPaymentPageAction($paymentRequestRepository, $twig);

        $this->expectException(NotFoundHttpException::class);

        $action(self::HASH);
    }
}
