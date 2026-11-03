<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\Core\Wehhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\Core\Webhook\InvalidWebhookSignature;
use SteveBoulay\SyliusSandboxPaymentPlugin\Core\Webhook\WebhookSignature;

final class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test';

    private const PAYLOAD = '{"id":"evt_1","type":"payment.succeeded"}';

    private const PAYLOADFAILED = '{"id":"evt_1","type":"payment.failed"}';

    private const TIMESTAMP = 1762041600;

    #[Test]
    public function it_signs_a_payload_with_its_timestamp(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        self::assertSame(
            't=1762041600,v1=4f06cceb21de2640ee71104150f1090fd9b56632bcb9289ca74095d5e3ea4e28',
            $signature->sign(self::PAYLOAD, self::TIMESTAMP),
        );
    }

    #[Test]
    public function it_accepts_a_valid_signature(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $header = $signature->sign(self::PAYLOAD, self::TIMESTAMP);

        $this->expectNotToPerformAssertions();

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP);
    }

    #[Test]
    public function it_rejects_a_modified_payload(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $header = $signature->sign(self::PAYLOAD, self::TIMESTAMP);

        $this->expectException(InvalidWebhookSignature::class);

        $signature->verify(self::PAYLOADFAILED, $header, self::TIMESTAMP);
    }

    #[Test]
    public function it_rejects_a_signature_made_with_another_secret(): void
    {
        $forger = new WebhookSignature('another_secret');
        $header = $forger->sign(self::PAYLOAD, self::TIMESTAMP);

        $signature = new WebhookSignature(self::SECRET);

        $this->expectException(InvalidWebhookSignature::class);

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP);
    }

    #[Test]
    public function it_rejects_an_expired_timestamp(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $header = $signature->sign(self::PAYLOAD, self::TIMESTAMP);

        $this->expectException(InvalidWebhookSignature::class);

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP + 301);
    }

    #[Test]
    public function it_accepts_a_timestamp_at_the_tolerance_limit(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $header = $signature->sign(self::PAYLOAD, self::TIMESTAMP);

        $this->expectNotToPerformAssertions();

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP + 300);
    }

    #[Test]
    public function it_rejects_a_timestamp_too_far_in_the_future(): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $header = $signature->sign(self::PAYLOAD, self::TIMESTAMP);

        $this->expectException(InvalidWebhookSignature::class);

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP - 301);
    }

    #[Test]
    #[DataProvider('malformedHeaders')]
    public function it_rejects_a_malformed_header(string $header): void
    {
        $signature = new WebhookSignature(self::SECRET);

        $this->expectException(InvalidWebhookSignature::class);
        $this->expectExceptionMessage('Malformed webhook signature header.');

        $signature->verify(self::PAYLOAD, $header, self::TIMESTAMP);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedHeaders(): iterable
    {
        $validHmac = str_repeat('a', 64);

        yield 'empty header' => [''];
        yield 'random text' => ['garbage'];
        yield 'non-numeric timestamp' => ['t=abc,v1=' . $validHmac];
        yield 'missing timestamp' => ['v1=' . $validHmac];
        yield 'missing signature' => ['t=1762041600'];
        yield 'signature too short' => ['t=1762041600,v1=abc'];
    }

    #[Test]
    public function it_refuses_an_empty_secret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WebhookSignature('');
    }

    #[Test]
    public function it_refuses_a_negative_tolerance(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new WebhookSignature(self::SECRET, -1);
    }
}
