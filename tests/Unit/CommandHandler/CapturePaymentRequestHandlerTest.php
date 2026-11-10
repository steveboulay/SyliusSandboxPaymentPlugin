<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\CommandHandler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\Command\CapturePaymentRequest;
use SteveBoulay\SyliusSandboxPaymentPlugin\CommandHandler\CapturePaymentRequestHandler;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\PaymentBundle\Provider\PaymentRequestProviderInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\PaymentRequestTransitions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CapturePaymentRequestHandlerTest extends TestCase
{
    private const HASH = '0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    private const URL = 'https://localhost/sandbox/pay/0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    private PaymentRequestInterface&MockObject $paymentRequest;

    private UrlGeneratorInterface&MockObject $urlGenerator;

    private StateMachineInterface&MockObject $stateMachine;

    private CapturePaymentRequestHandler $handler;

    protected function setUp(): void
    {
        $this->paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $this->paymentRequest->method('getId')->willReturn(self::HASH);

        $paymentRequestProvider = $this->createMock(PaymentRequestProviderInterface::class);
        $paymentRequestProvider->method('provide')->willReturn($this->paymentRequest);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->stateMachine = $this->createMock(StateMachineInterface::class);

        $this->handler = new CapturePaymentRequestHandler(
            $paymentRequestProvider,
            $this->urlGenerator,
            $this->stateMachine,
        );
    }

    #[Test]
    public function it_stores_the_absolute_url_of_the_simulated_payment_page(): void
    {
        $this->paymentRequest->method('getState')->willReturn(PaymentRequestInterface::STATE_NEW);

        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with(
                CapturePaymentRequestHandler::SIMULATED_PAYMENT_PAGE_ROUTE,
                ['hash' => self::HASH],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn(self::URL);

        $this->paymentRequest
            ->expects(self::once())
            ->method('setResponseData')
            ->with(['url' => self::URL]);

        ($this->handler)(new CapturePaymentRequest(self::HASH));
    }

    #[Test]
    public function it_moves_the_payment_request_to_processing(): void
    {
        $this->paymentRequest->method('getState')->willReturn(PaymentRequestInterface::STATE_NEW);
        $this->urlGenerator->method('generate')->willReturn(self::URL);

        $this->stateMachine
            ->expects(self::once())
            ->method('apply')
            ->with(
                $this->paymentRequest,
                PaymentRequestTransitions::GRAPH,
                PaymentRequestTransitions::TRANSITION_PROCESS,
            );

        ($this->handler)(new CapturePaymentRequest(self::HASH));
    }

    #[Test]
    public function it_does_nothing_when_the_payment_request_is_already_processing(): void
    {
        $this->paymentRequest->method('getState')->willReturn(PaymentRequestInterface::STATE_PROCESSING);

        $this->urlGenerator->expects(self::never())->method('generate');
        $this->paymentRequest->expects(self::never())->method('setResponseData');
        $this->stateMachine->expects(self::never())->method('apply');

        ($this->handler)(new CapturePaymentRequest(self::HASH));
    }

    #[Test]
    public function it_looks_up_the_payment_request_of_the_command(): void
    {
        $command = new CapturePaymentRequest(self::HASH);

        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getState')->willReturn(PaymentRequestInterface::STATE_PROCESSING);

        $paymentRequestProvider = $this->createMock(PaymentRequestProviderInterface::class);
        $paymentRequestProvider
            ->expects(self::once())
            ->method('provide')
            ->with(self::identicalTo($command))
            ->willReturn($paymentRequest);

        $handler = new CapturePaymentRequestHandler(
            $paymentRequestProvider,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(StateMachineInterface::class),
        );

        $handler($command);
    }
}
