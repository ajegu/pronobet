<?php

namespace ForecastBundle\Form;

use AppBundle\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BankAccountType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('iban', TextType::class, [
                'label' => 'label.iban',
                'constraints' => [
                    new NotBlank(),
                    new Iban()
                ]
            ])
            ->add('bic', TextType::class, [
                'label' => 'label.bic',
                'constraints' => [
                    new Length(['max' => 255])
                ]
            ])
            ->add('ownerName', TextType::class, [
                'label' => 'label.owner_name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255])
                ]
            ])
            ->add('addressLine1', TextType::class, [
                'label' => 'label.address_line_1',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255])
                ]
            ])
            ->add('addressLine2', TextType::class, [
                'label' => 'label.address_line_2',
                'constraints' => [
                    new Length(['max' => 255])
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'label.city',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255])
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'label.postal_code',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255])
                ]
            ])
            ->add('country', EntityType::class, [
                'label' => 'label.country',
                'class' => 'AppBundle:Country',
                'attr' => ['class' => 'ui dropdown'],
                'preferred_choices' => function ($val, $key) {
                    // prefer options within 3 days
                    return $val->getSorting() > 0;
                },
                'query_builder' => function (CountryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.sorting', 'DESC');
                },
                'constraints' => [
                    new NotBlank()
                ]
            ]);
    }


    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_bankaccount';
    }


}
