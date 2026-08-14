<?php

namespace App\Form\Invoice;

use App\Entity\Invoice\InvoiceItem;
use App\Enum\Invoice\InvoiceMeasure;
use App\Form\Core\DefaultForm\EditForm;
use App\Service\Core\DomainTranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\RequestStack;

class InvoiceItemForm extends AbstractType
{

    public function __construct(private DomainTranslationService $domainTranslationService, RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('quantity', TextType::class, [
                'label' => 'invoiceItem.quantity',
                'constraints' => [
                    new NotBlank()
                ],
                'attr' => [
                    'class' => 'invoice-item-qty'
                ],
            ])
            ->add('measure', EnumType::class, [
                'class' => InvoiceMeasure::class,
                'label' => 'invoiceItem.measure',
                'choice_label' => function (InvoiceMeasure $measure) {
                    return $this->domainTranslationService->translate($measure->label());
                },
                'constraints' => [
                    new NotBlank()
                ],
                'attr' => [
                    'class' => 'invoice-item-measure'
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'invoiceItem.unitPrice',
                'scale' => 2,
                'constraints' => [
                    new NotBlank()
                ],
                'attr' => [
                    'class' => 'invoice-item-unit-price'
                ],
            ])
            ->add('total', NumberType::class, [
                'label' => 'invoiceItem.total',
                'scale' => 2,
                'constraints' => [
                    new NotBlank()
                ],
                'attr' => [
                    'class' => 'invoice-item-total d-none show-in-div'
                ],
            ])
            ->add('ord', HiddenType::class, [
                'attr' => [
                    'class' => 'invoice-item-order-index'
                ]
            ]);
        
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        if ($locale === 'en') {
            $builder->add('nameEng', TextareaType::class, [
                'label' => 'invoiceItem.nameEng',
                'empty_data' => '',
                'attr' => [
                    'class' => 'invoice-item-text invoice-item-text-bg-input form-control'
                ],
            ]);
        } else {
            $builder->add('name', TextareaType::class, [
                'label' => 'invoiceItem.name',
                'empty_data' => '',
                'attr' => [
                    'class' => 'invoice-item-text invoice-item-text-bg-input form-control'
                ],
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InvoiceItem::class,
        ]);
    }

}
