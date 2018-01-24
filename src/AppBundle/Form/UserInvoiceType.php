<?php

namespace AppBundle\Form;

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

class UserInvoiceType extends AbstractType
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
        return 'appbundle_user';
    }


}
