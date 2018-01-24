<?php

namespace ForecastBundle\Form;

use AppBundle\Repository\BookmakerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SportForecastType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title/match',
                'constraints' => [
                    new Length(['max' => 100])
                ]
            ])
            ->add('betting', NumberType::class, [
                'label' => 'label.betting',
                'constraints' => [
                    new GreaterThan(0),
                    new NotBlank()
                ]
            ])
            ->add('ticketFile', FileType::class, [
                'label' => 'label.ticket'
            ])
            ->add('isVip', ChoiceType::class, [
                'choices' => [
                    'label.yes' => true,
                    'label.no' => false
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'label.isVip',
                'required' => true,
                'choice_attr' => function($val, $key, $index) {
                    return ['style' => 'margin:0 5px 0 10px'];
                }
            ])
            ->add('bookmaker', EntityType::class, [
                'label' => 'label.bookmaker',
                'class' => 'AppBundle:Bookmaker',
                'attr' => ['class' => 'ui dropdown'],
                'query_builder' => function (BookmakerRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->where('b.visible = 1')
                        ->orderBy('b.name', 'ASC');
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
            'data_class' => 'AppBundle\Entity\SportForecast'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sportforecastbundle_sportforecast';
    }


}
