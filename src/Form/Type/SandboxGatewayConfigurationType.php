<?php

declare(strict_types=1);

namespace SteveBoulay\SyliusSandboxPaymentPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SandboxGatewayConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('webhook_secret', TextType::class, [
            'label' => 'steve_boulay_sylius_sandbox_payment.form.gateway_configuration.webhook_secret',
            'constraints' => [
                new NotBlank(groups: ['sylius', 'sandbox']),
            ],
        ]);
    }
}
