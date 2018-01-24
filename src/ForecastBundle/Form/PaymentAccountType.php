<?php
/**
 * Created by PhpStorm.
 * User: allan
 * Date: 03/08/17
 * Time: 15:06
 */

namespace ForecastBundle\Form;


use AppBundle\Repository\CountryRepository;
use AppBundle\Repository\NationalityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentAccountType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $birthday = new \DateTime();
        $birthday->sub(new \DateInterval("P13Y"));

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'label.firstname',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'label.lastname',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100])
                ]
            ])
            ->add('birthday', DateType::class, [
                'label' => 'label.birthday',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'constraints' => [
                    new Date(),
                    new LessThan($birthday),
                    new NotBlank()
                ]
            ])
            ->add('nationality', EntityType::class, [
                'label' => 'label.nationality',
                'class' => 'AppBundle:Nationality',
                'attr' => ['class' => 'ui dropdown'],
                'preferred_choices' => function ($val, $key) {
                    // prefer options within 3 days
                    return $val->getSorting() > 0;
                },
                'query_builder' => function (NationalityRepository $er) {
                    return $er->createQueryBuilder('n')
                        ->orderBy('n.sorting', 'DESC');
                },
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('addressLine1', TextType::class, [
                'label' => 'label.address_line_1',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100])
                ]
            ])
            ->add('addressLine2', TextType::class, [
                'label' => 'label.address_line_2',
                'constraints' => [
                    new Length(['max' => 100])
                ]
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'label.postal_code',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 10])
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'label.city',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 50])
                ]
            ])
            ->add('country', EntityType::class, [
                'label' => 'label.country_of_residence',
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
            ])
            ->add('occupation', TextType::class, [
                'label' => 'label.occupation',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100])
                ]
            ])
            ->add('incomeRange', ChoiceType::class, [
                'label' => 'label.income_range',
                'constraints' => [
                    new NotBlank()
                ],
                'choices' => [
                    '<18K€' => 1,
                    '18-30K€' => 2,
                    '30-50K€' => 3,
                    '50-80K€' => 4,
                    '80-120K€' => 5,
                    '>120K€' => 6,
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'forecastbundle_user';
    }
}