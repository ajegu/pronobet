<?php

namespace ForecastBundle\Form;

use AppBundle\Repository\ChampionshipRepository;
use AppBundle\Repository\SportRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SportBetType extends AbstractType
{
    private $sports;
    private $sportId;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->sports = $options['sports'];
        $this->sportId = $options['sportId'];

        $builder
            ->add('winner', TextType::class, [
                'label' => 'label.winner',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100])
                ]
            ])
            ->add('playedAt', DateTimeType::class, [
                'label' => 'label.playedAt',
                'attr' => ['class' => 'js-datepicker'],
                'format' => 'dd/MM/yyyy HH:mm',
                'widget' => 'single_text'

            ])
            ->add('rating', NumberType::class, [
                'label' => 'label.rating',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan(0)
                ]
            ])
            ->add('analysis', TextareaType::class, [
                'label' => 'label.analysis'
            ])
            ->add('sport', EntityType::class, [
                'label' => 'label.sport',
                'class' => 'AppBundle:Sport',
                'attr' => ['class' => 'ui dropdown'],
                'required' => true,
                'choices' => $this->sports,
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('championship', EntityType::class, [
                'label' => 'label.championship',
                'class' => 'AppBundle:Championship',
                'attr' => ['class' => 'ui dropdown championship'],
                'empty_data'  => null,
                'required' => false,
                'query_builder' => function (ChampionshipRepository $cr) {
                    if ($this->sportId === false) {
                        foreach ($this->sports as $sport) {
                            $this->sportId = $sport->getId();
                            break;
                        }
                    }

                    return $cr->createQueryBuilder('c')
                        ->where('c.sport = :sport')
                        ->andWhere('c.visible = 1')
                        ->setParameter('sport', $this->sportId)
                        ->orderBy('c.name', 'ASC');
                }
            ])
            ->add('confidenceIndex', ChoiceType::class, [
                'choices' => [
                    0, 1, 2, 3, 4, 5
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'label.confidence_index',
                'required' => true,
                'choice_attr' => function($val, $key, $index) {
                    return ['style' => 'margin:0 5px 0 10px'];
                }
            ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\SportBet'
        ));

        $resolver->setRequired('sports');
        $resolver->setRequired('sportId');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'forecastbundle_sportbet';
    }


}
