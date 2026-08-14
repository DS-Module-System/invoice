<?php

namespace App\Form\Invoice;

use App\Entity\Invoice\InvoicePayment;
use App\Form\Core\DefaultForm\EditForm;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoicePaymentForm extends EditForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('date', DateType::class, [
                'label' => 'paymentDate',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['data-datepicker' => '{}'],
                'input' => 'datetime_immutable',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'paymentPrice',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'paymentDescription',
                'required' => false,
            ])
            ->add('transaction', TextType::class, [
                'label' => 'paymentTransaction',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoicePayment::class,
            'translation_domain' => 'invoice',
        ]);
    }
}
