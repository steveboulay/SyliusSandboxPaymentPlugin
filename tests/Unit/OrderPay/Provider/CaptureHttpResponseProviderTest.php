<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\OrderPay\Provider;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\OrderPay\Provider\CaptureHttpResponseProvider;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CaptureHttpResponseProviderTest extends TestCase
{
    private const URL = 'https://localhost/sandbox/pay/0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    #[Test]
    public function it_supports_a_processing_payment_request(): void
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getState')->willReturn(PaymentRequestInterface::STATE_PROCESSING);

        $provider = new CaptureHttpResponseProvider();

        self::assertTrue($provider->supports(new Request(), $paymentRequest));
    }

    #[Test]
    #[DataProvider('otherStates')]
    public function it_does_not_support_other_states(string $state): void
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getState')->willReturn($state);

        $provider = new CaptureHttpResponseProvider();

        self::assertFalse($provider->supports(new Request(), $paymentRequest));
    }

    #[Test]
    public function it_redirects_to_the_simulated_payment_page(): void
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getResponseData')->willReturn(['url' => self::URL]);

        $provider = new CaptureHttpResponseProvider();

        $response = $provider->getResponse(new Request(), $paymentRequest);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(self::URL, $response->getTargetUrl());
        self::assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $responseData
     */
    #[Test]
    #[DataProvider('invalidResponseData')]
    public function it_rejects_response_data_without_a_valid_url(array $responseData): void
    {
        $paymentRequest = $this->createMock(PaymentRequestInterface::class);
        $paymentRequest->method('getResponseData')->willReturn($responseData);

        $provider = new CaptureHttpResponseProvider();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The simulated payment page URL is missing.');

        $provider->getResponse(new Request(), $paymentRequest);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function otherStates(): iterable
    {
        yield 'new' => [PaymentRequestInterface::STATE_NEW];
        yield 'completed' => [PaymentRequestInterface::STATE_COMPLETED];
        yield 'failed' => [PaymentRequestInterface::STATE_FAILED];
        yield 'cancelled' => [PaymentRequestInterface::STATE_CANCELLED];
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidResponseData(): iterable
    {
        yield 'no url' => [[]];
        yield 'empty url' => [['url' => '']];
        yield 'url is not a string' => [['url' => 42]];
    }
}
