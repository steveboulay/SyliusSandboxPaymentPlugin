<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\Core\WebHook;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SteveBoulay\SyliusSandboxPaymentPlugin\Core\WebHook\WebhookSignature;

final class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test';

    private const PAYLOAD = '{"id":"evt_1","type":"payment.succeeded"}';

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
}
