<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Integration;

use PHPUnit\Framework\Attributes\Test;
use SteveBoulay\SyliusSandboxPaymentPlugin\CommandHandler\CapturePaymentRequestHandler;
use SteveBoulay\SyliusSandboxPaymentPlugin\Controller\SimulatedPaymentPageAction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

final class SimulatedPaymentPageRouteTest extends KernelTestCase
{
    private const HASH = '0195f2a6-7b1c-7d3e-9a41-2c8e5f6b1d20';

    private RouterInterface $router;

    protected function setUp(): void
    {
        self::bootKernel();

        $router = self::getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $this->router = $router;
    }

    #[Test]
    public function it_generates_the_simulated_payment_page_url(): void
    {
        $url = $this->router->generate(
            CapturePaymentRequestHandler::SIMULATED_PAYMENT_PAGE_ROUTE,
            ['hash' => self::HASH],
        );

        self::assertSame('/sandbox/pay/' . self::HASH, $url);
    }

    #[Test]
    public function it_routes_the_url_to_the_simulated_payment_page_action(): void
    {
        $parameters = $this->router->match('/sandbox/pay/' . self::HASH);

        self::assertSame(CapturePaymentRequestHandler::SIMULATED_PAYMENT_PAGE_ROUTE, $parameters['_route']);
        self::assertSame(SimulatedPaymentPageAction::class, $parameters['_controller']);
        self::assertSame(self::HASH, $parameters['hash']);
    }

    #[Test]
    public function it_does_not_route_a_hash_that_is_not_a_uuid(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->router->match('/sandbox/pay/not-a-uuid');
    }
}
