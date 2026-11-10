<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\Core\Webhook;

final readonly class WebhookSignature
{
    public function __construct(private string $secret, private int $toleranceInSeconds = 300)
    {
        if ('' === $secret) {
            throw new \InvalidArgumentException('The secret must not be empty.');
        }

        if ($toleranceInSeconds < 0) {
            throw new \InvalidArgumentException('The tolerance must not be negative.');
        }
    }

    public function sign(string $payload, int $timestamp): string
    {
        $hmac = $this->generateHmac($payload, $timestamp);

        return sprintf('t=%d,v1=%s', $timestamp, $hmac);
    }

    public function verify(string $payload, string $header, int $now): void
    {
        if (1 !== preg_match('/\At=(\d+),v1=([a-f0-9]{64})\z/', $header, $matches)) {
            throw new InvalidWebhookSignatureException('Malformed webhook signature header.');
        }

        $timestamp = (int) $matches[1];
        $receivedHmac = $matches[2];

        if (abs($now - $timestamp) > $this->toleranceInSeconds) {
            throw new InvalidWebhookSignatureException('Webhook timestamp is outside the tolerance window.');
        }

        $expectedHmac = $this->generateHmac($payload, $timestamp);

        if (!hash_equals($expectedHmac, $receivedHmac)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }
    }

    private function generateHmac(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $this->secret);
    }
}
