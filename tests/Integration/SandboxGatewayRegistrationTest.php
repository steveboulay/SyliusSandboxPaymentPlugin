<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SandboxGatewayRegistrationTest extends KernelTestCase
{
    #[Test]
    public function it_registers_the_sandbox_gateway(): void
    {
        self::bootKernel();

        $gatewayFactories = self::getContainer()->getParameter('sylius.gateway_factories');

        self::assertIsArray($gatewayFactories);
        self::assertArrayHasKey('sandbox', $gatewayFactories);
    }
}
