<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;

class BookmakerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'constraints' => array(
                    new Length(array('max' => 100)),
                    new NotBlank()
                ),
                'attr' => [
                    'autofocus' => true
                ]
            ])

            ->add('logoFile',FileType::class, [
                'label' => 'label.logo',
                'required' => true
            ])


            ->add('bonus', IntegerType::class, [
                'label' => 'label.bonus',
                'data' => 0,
                'constraints' => array(
                    new GreaterThanOrEqual(0),
                    new Type('int'),
                    new NotBlank()
                )
            ])

            ->add('description', TextareaType::class, [
                'label' => 'label.description',
            ])

            ->add('websiteLink', UrlType::class, [
                'label' => 'label.websiteLink',
                'constraints' => array(
                    new Url(),
                    new NotBlank()
                )
            ])

            ->add('adLink', UrlType::class, [
                'label' => 'label.adLink',
                'constraints' => new Url()
            ])

            ->add('facebookLink', UrlType::class, [
                'label' => 'label.facebookLink',
                'constraints' => new Url()
            ])

            ->add('twitterLink', UrlType::class, [
                'label' => 'label.twitterLink',
                'constraints' => new Url()
            ])

            ->add('youtubeLink', UrlType::class, [
                'label' => 'label.youtubeLink',
                'constraints' => new Url()
            ])

            ->add('visible', CheckboxType::class, [
                'label' => 'label.visible',
                'required' => true,
            ]);

    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Bookmaker'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'adminbundle_bookmaker';
    }


}
