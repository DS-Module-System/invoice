<?php

namespace App\Form\Invoice;

// use App\Entity\Admin\Client;
use App\Enum\Invoice\InvoicePaymentMethod;
use App\Form\Core\DefaultForm\SearchForm;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvoiceSearchForm extends SearchForm
{

    public function __construct(
        private RequestStack          $requestStack,
        private UrlGeneratorInterface $router,
        private TranslatorInterface   $translator
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('invoicePaymentMethod', EnumType::class, [
                'class' => InvoicePaymentMethod::class,
                'choice_label' => 'label',
                'label' => 'paymentMethod',
                'placeholder' => 'paymentMethod.placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                    'data-select2-config' => '{"placeholder": "Изберете тип плащане"}',
                ]
            ])
            ->add('fromIssueDate', DateType::class, [
                'label' => 'fromIssueDate',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['data-datepicker' => '{}'],
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            ->add('toIssueDate', DateType::class, [
                'label' => 'toIssueDate',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['data-datepicker' => '{}'],
                'required' => false,
                'input' => 'datetime_immutable',
            ])
            // ->add('client', EntityType::class, [
            //     'class' => Client::class,
            //     'query_builder' => function (ClientRepository $cr) {
            //         return $cr->createQueryBuilder('c')
            //             ->orderBy('c.name', 'ASC');
            //     },
            //     'choice_label' => 'name',
            //     'label' => 'invoice.client',
            //     'placeholder' => $this->translator->trans('project.client.placeholder'),
            //     'required' => false,
            //     'attr' => [
            //         'class' => 'select2',
            //         'data-select2-config' => '{"placeholder": "Изберете клиент"}',
            //     ],
            // ])
            ->add('number', TextType::class, [
                'label' => 'number',
                'required' => false,
            ])
            ->add('isPaid', ChoiceType::class, [
                'label' => 'isPaid',
                'choices' => [
                    'Да' => true,
                    'Не' => false,
                ],
                'placeholder' => 'isPaid.placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'select2',
                    'data-select2-config' => '{"placeholder": "Изберете платена ли е"}',
                ],
            ])
            ->add('priceNet', MoneyType::class, [
                'label' => 'netPrice',
                'currency' => false,
                'scale' => 2,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $resolver->setDefault('action', $this->router->generate($request->get('_route'),
                array_merge($request->get('_route_params'), ['page' => 1])));
        }
        $resolver->setDefault('translation_domain', 'invoice');

    }

}
