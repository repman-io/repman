<?php

declare(strict_types=1);

namespace Buddy\Repman\Form\Type\Organization;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class EnableSecurityScanType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isSecurityScanEnabled', ChoiceType::class, [
                'choices' => [
                    'Enable' => true,
                    'Disable' => false,
                ],
                'label' => 'Enable security scan for new packages',
                'required' => true,
            ])
            ->add('enableSecurityScan', SubmitType::class, ['label' => 'Change'])
        ;
    }
}
