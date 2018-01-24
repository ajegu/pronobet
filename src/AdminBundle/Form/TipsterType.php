<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TipsterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pictureFile', FileType::class, array(
                'label' => 'label.tipster_picture',
                'constraints' => array(
                    new Length(array('max' => 255))
                )
            ))
            ->add('coverFile', FileType::class, array(
                'label' => 'label.tipster_cover',
                'constraints' => array(
                    new Length(array('max' => 255))
                )
            ))
            ->add('description', TextareaType::class, array(
                'label' => 'label.description',
                'attr' => [
                    'autofocus' => true
                ]
            ))
            ->add('fee', NumberType::class, array(
                'label' => 'label.fee',
                'constraints' => array(
                    new NotBlank(),
                    new GreaterThan(0)
                )
            ))
            ->add('commission', NumberType::class, array(
                'label' => 'label.commission',
                'constraints' => array(
                    new NotBlank(),
                    new GreaterThan(0)
                )
            ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Tipster'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'adminbundle_tipster';
    }


}
