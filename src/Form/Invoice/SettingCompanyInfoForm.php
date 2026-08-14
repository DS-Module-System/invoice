<?php
/**
 * Created by PhpStorm.
 * User: Ervin
 * Date: 2019-07-02
 * Time: 14:23
 */

namespace App\Form\Invoice;

use App\Form\Core\DefaultForm\EditForm;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SettingCompanyInfoForm extends EditForm
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('companyName', TextType::class, [
                'label' => "settingCompanyInfo.companyName",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
            ->add('companyLogo', FileType::class, [
                'label' => "settingCompanyInfo.companyLogo",
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5120k',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
            ])
            ->add('country', TextType::class, [
                'label' => "settingCompanyInfo.country",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
            
            ->add('city', TextType::class, [
                'label' => "settingCompanyInfo.city",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
            ->add('address', TextareaType::class, [
                'label' => "settingCompanyInfo.address",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
            ->add('eek', TextType::class, [
                'label' => "settingCompanyInfo.eek",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
            ->add('ddsNumber', TextType::class, [
                'label' => "settingCompanyInfo.ddsNumber",
                'required' => false,
                'empty_data' => '',
            ])
            ->add('mol', TextType::class, [
                'label' => "settingCompanyInfo.mol",
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3]),
                ],
                'empty_data' => '',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('translation_domain', 'invoice');
    }
}
