<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\Core\WebHook;

final readonly class WebhookSignature
{
    public function __construct(private string $secret, private int $toleranceInSeconds = 300)
    {
        if ($secret === '') {
            throw new \InvalidArgumentException("Secret don't be empty");
        }

        if ($toleranceInSeconds < 0) {
            throw new \InvalidArgumentException('toleranceInSeconds must be positive');
        }
    }

    private function generateHmac(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $payload, $this->secret);
    }

    public function sign(string $payload, int $timestamp): string
    {
        $hmac = $this->generateHmac($payload, $timestamp);

        return "t=$timestamp,v1=$hmac";
    }

    public function verify(string $payload, string $header, int $now): void
    {
        if (1 !== preg_match('/\At=(\d+),v1=([a-f0-9]{64})\z/', $header, $matches)) {
            throw new InvalidWebhookSignature('Malformed webhook signature header.');
        }

        $timestamp = (int) $matches[1];
        $receivedHmac = $matches[2];

        if (abs($now - $timestamp) > $this->toleranceInSeconds) {
            throw new InvalidWebhookSignature('Webhook timestamp is outside the tolerance window.');
        }

        $expectedHmac = $this->generateHmac($payload, $timestamp);

        if (!hash_equals($expectedHmac, $receivedHmac)) {
            throw new InvalidWebhookSignature('Invalid webhook signature.');
        }
    }
}
