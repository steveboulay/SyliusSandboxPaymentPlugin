<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\CommandProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\Command\CapturePaymentRequest;
use SteveBoulay\SyliusSandboxPaymentPlugin\CommandProvider\CapturePaymentRequestCommandProvider;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

final class CapturePaymentRequestCommandProviderTest extends TestCase
{
    private const HASH = '0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    #[Test]
    public function it_supports_any_payment_request(): void
    {
        $provider = new CapturePaymentRequestCommandProvider();

        self::assertTrue($provider->supports($this->createMock(PaymentRequestInterface::class)));
    }

    #[Test]
    public function it_provides_a_capture_command_with_the_payment_request_hash(): void
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getId')->willReturn(self::HASH);

        $provider = new CapturePaymentRequestCommandProvider();
        $command = $provider->provide($paymentRequest);

        self::assertInstanceOf(CapturePaymentRequest::class, $command);
        self::assertSame(self::HASH, $command->getHash());
    }
}
