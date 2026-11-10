<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sylius\Component\Core\Model\Payment;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;

final class SimulatedPaymentPageTemplateTest extends KernelTestCase
{
    private const TEMPLATE = '@SteveBoulaySyliusSandboxPaymentPlugin/shop/simulated_payment.html.twig';

    #[Test]
    #[DataProvider('locales')]
    public function it_renders_the_translated_page(string $locale, string $expectedText): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $request = Request::create('/');
        $request->setLocale($locale);

        $requestStack = $container->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push($request);

        $translator = $container->get('translator');
        self::assertInstanceOf(LocaleAwareInterface::class, $translator);
        $translator->setLocale($locale);

        $twig = $container->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $payment = new Payment();
        $payment->setAmount(1250);
        $payment->setCurrencyCode('USD');

        $html = $twig->render(self::TEMPLATE, ['payment' => $payment]);

        self::assertStringContainsString($expectedText, $html);
        self::assertStringNotContainsString('steve_boulay_sylius_sandbox_payment.', $html);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function locales(): iterable
    {
        yield 'English' => ['en_US', 'Amount to pay: $12.50'];
        yield 'French' => ['fr_FR', 'Montant à payer'];
    }
}
