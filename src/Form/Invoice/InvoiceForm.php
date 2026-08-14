<?php

namespace App\Form\Invoice;

use App\Entity\BankAccount\BankAccount;
use App\Entity\Client\Client;
use App\Entity\Invoice\Invoice;
use App\Entity\User\BaseUser;
use App\Enum\Invoice\InvoiceDdsOptions;
use App\Enum\Invoice\InvoicedStatus;
use App\Enum\Invoice\InvoicePaymentMethod;
use App\Enum\Invoice\InvoicePromotionType;
use App\Form\Core\DefaultForm\EditForm;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Enum\Invoice\InvoiceType;

class InvoiceForm extends EditForm
{

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        parent::buildForm($builder, $options);

        $issueDateDatePickerJson = '{}';
        if($options['lastInvoiceDate']) {
            $issueDateDatePickerJson = "{\"minDate\": \"{$options['lastInvoiceDate']}\"}";
        }

        $builder
            ->add('number', TextType::class, [
                'label' => 'number',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Regex('/[0-9]{10}/', 'Нометър трябва да е от 10 символа!')
                ],
                'empty_data' => '',
            ])
            ->add('issueDate', DateType::class, [
                'label' => 'issueDate',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['data-datepicker' => $issueDateDatePickerJson, 'class'=>'ajax-date'],
                'input' => 'datetime_immutable',
                'empty_data' => '0000-00-00',
            ])
            ->add('taxDate', DateType::class, [
                'label' => 'taxDate',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['data-datepicker' => $issueDateDatePickerJson],
                'input' => 'datetime_immutable',
                'empty_data' => '0000-00-00',
            ])
            ->add('subTotalPrice', NumberType::class, [
                'label' => 'subTotalPrice',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'd-none show-in-div invoice-sub-total'
                ],
            ])
            ->add('promotionType', EnumType::class, [
                'class' => InvoicePromotionType::class,
                'choice_label' => 'label',
                'label' => 'promotionType',
                // 'placeholder' => 'status.placeholder',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'invoice-promotion-type'
                ],
            ])
            ->add('promotionValue', NumberType::class, [
                'label' => 'promotionPrice',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'invoice-promotion-value'
                ],
            ])
            ->add('promotionPrice', NumberType::class, [
                'label' => 'promotionPrice',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'd-none show-in-div invoice-promotion-price'
                ],
            ])
            ->add('taxBasePrice', NumberType::class, [
                'label' => 'taxBasePrice',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'd-none show-in-div invoice-tax-base-price'
                ],
            ])
            ->add('ddsPercentage', NumberType::class, [
                'label' => 'ddsPercentage',
                'required' => true,
                'scale' => 0,
                'attr' => [
                    'class' => 'invoice-dds-percentage'
                ],
            ])
            ->add('ddsPrice', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'd-none show-in-div invoice-dds-price'
                ],
            ])
            ->add('totalPrice', NumberType::class, [
                'label' => 'totalPrice',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'd-none show-in-div invoice-total-price'
                ],
            ])
            ->add('paymentMethod', EnumType::class, [
                'class' => InvoicePaymentMethod::class,
                'label' => 'paymentMethod',
                'choice_label' => 'labelInForm',
                'required' => false,
                'placeholder' => null,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('ddsOption', EnumType::class, [
                'class' => InvoiceDdsOptions::class,
                'choice_label' => 'label',
                'label' => 'ddsOption',
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'select2 invoice-dds-option'
                ],
                'required' => true,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'label' => 'client',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'choice_label' => function (Client $entity) {
                    return $entity->getName();
                },
                'placeholder' => 'choose',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('publisher', EntityType::class, [
                'class' => BaseUser::class,
                'label' => 'publisher',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'choice_label' => fn(BaseUser $entity) => $entity->getName(),
                'placeholder' => 'choose',
                'attr' => [
                    'class' => 'select2',
                ]
            ])
            ->add('invoiceItems', CollectionType::class, [
                'entry_type' => InvoiceItemForm::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
                'by_reference' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'name',
                'label_attr' => [
                    'class' => 'bg'
                ],
                'required' => false,
                'translation_domain' => 'client',
            ])
            ->add('eek', TextType::class, [
                'label' => 'eek',
                'required' => false,
                'translation_domain' => 'client',
            ])
            ->add('vat', TextType::class, [
                'label' => 'vat',
                'required' => false,
                'translation_domain' => 'client',
            ])
            ->add('responsiblePerson', TextType::class, [
                'label' => 'responsiblePerson',
                'required' => false,
                'label_attr' => [
                    'class' => 'bg'
                ],
                'translation_domain' => 'client',
            ])
            ->add('address', TextareaType::class, [
                'label' => 'address',
                'required' => false,
                'label_attr' => [
                    'class' => 'bg'
                ],
                'translation_domain' => 'client',
            ])
            ->add('countryCode', TextType::class, [
                'label' => 'countryCode',
                'required' => false,
                'translation_domain' => 'client',
            ])
            // ->add('isPaid', EnumType::class, [
            //     'class' => InvoicedStatus::class,
            //     'choice_label' => 'label',
            //     'label' => 'isPaid',
            //     'constraints' => [
            //         new NotBlank(),
            //     ],
            //     'data' => InvoicedStatus::from(0),
            //     'attr' => [
            //         'class' => 'select2 invoice-dds-option'
            //     ],
            //     'required' => true,
            // ])
            // ->add('isPosted', EnumType::class, [
            //     'class' => InvoicedStatus::class,
            //     'choice_label' => 'label',
            //     'label' => 'isPosted',
            //     'choices' => [
            //         InvoicedStatus::from(0),
            //         InvoicedStatus::from(1),
            //     ],
            //     'constraints' => [
            //         new NotBlank(),
            //     ],
            //     'data' => InvoicedStatus::from(0),
            //     'required' => true,
            //     'attr' => [
            //         'class' => 'select2 invoice-dds-option'
            //     ],

            //     // 'disabled' => true,
            // ])
            ->add('bankAccount', EntityType::class, [
                'class' => BankAccount::class,
                'label' => 'bankAccount',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'choice_label' => function (BankAccount $entity) {
                    return $entity->getName();
                },
                'placeholder' => 'choose',
                'attr' => [
                    'class' => 'select2',
                ]
            ]);

            
        /** @var Invoice|null $invoice */
        $invoice = $options['data'];

        $isProforma = $invoice?->getType() === InvoiceType::ProformaInvoice;

            if (!$isProforma) {

                $builder->add('isPaid', EnumType::class, [
                    'class' => InvoicedStatus::class,
                    'choice_label' => 'label',
                    'label' => 'isPaid',
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'data' => InvoicedStatus::from(0),
                    'attr' => [
                        'class' => 'select2 invoice-dds-option'
                    ],
                    'required' => true,
                ]);

                $builder->add('isPosted', EnumType::class, [
                    'class' => InvoicedStatus::class,
                    'choice_label' => 'label',
                    'label' => 'isPosted',
                    'choices' => [
                        InvoicedStatus::from(0),
                        InvoicedStatus::from(1),
                    ],
                    'constraints' => [
                        new NotBlank(),
                    ],
                    'data' => InvoicedStatus::from(0),
                    'required' => true,
                    'attr' => [
                        'class' => 'select2 invoice-dds-option'
                    ],
                ]);

            }

            $locale = $this->requestStack->getCurrentRequest()->getLocale();

            if ($locale === 'en') {
                $builder->add('noteEng', TextareaType::class, [
                    'label' => 'note',
                    'required' => false,
                ]);
            } else {
                $builder->add('note', TextareaType::class, [
                    'label' => 'note',
                    'required' => false,
                ]);
            }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
            'lastInvoiceDate' => null,
            'translation_domain' => 'invoice',
        ]);
    }

}
