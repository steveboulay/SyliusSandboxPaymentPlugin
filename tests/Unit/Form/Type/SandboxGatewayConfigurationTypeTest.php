<?php

declare(strict_types=1);

namespace Tests\SteveBoulay\SyliusSandboxPaymentPlugin\Unit\Form\Type;

use PHPUnit\Framework\Attributes\Test;
use SteveBoulay\SyliusSandboxPaymentPlugin\Form\Type\SandboxGatewayConfigurationType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class SandboxGatewayConfigurationTypeTest extends TypeTestCase
{
    private const SECRET = 'whsec_test';

    #[Test]
    public function it_keeps_the_submitted_webhook_secret(): void
    {
        $form = $this->factory->create(SandboxGatewayConfigurationType::class);

        $form->submit(['webhook_secret' => self::SECRET]);

        self::assertTrue($form->isSynchronized());
        self::assertSame(['webhook_secret' => self::SECRET], $form->getData());
    }

    #[Test]
    public function it_requires_a_webhook_secret(): void
    {
        $form = $this->factory->create(SandboxGatewayConfigurationType::class, null, [
            'validation_groups' => ['sandbox'],
        ]);

        $form->submit(['webhook_secret' => '']);

        self::assertFalse($form->isValid());
    }

    protected function getExtensions(): array
    {
        return [new ValidatorExtension(Validation::createValidator())];
    }
}
