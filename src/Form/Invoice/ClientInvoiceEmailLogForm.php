<?php

namespace App\Form\Invoice;

use App\Form\Core\DefaultForm\EditForm;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotNull;

class ClientInvoiceEmailLogForm extends EditForm
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        parent::buildForm($builder, $options);

        $builder
            ->add('receivers', ChoiceType::class, [
                'label' => 'clientName',
                'choices' => array_flip($options['receivers']),
                'constraints' => [
                    new NotBlank(),
                    new NotNull(),
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('title', TextType::class, [
                'label' => 'emailTitle',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'emailContent',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 9
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'receivers' => [],
        ]);
        $resolver->setDefault('translation_domain', 'invoice');
    }
}
