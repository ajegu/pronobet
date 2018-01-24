<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', PasswordType::class, array(
                'label' => 'label.password',
                'attr' => array(
                    'placeholder' => 'label.password',
                    'autofocus' => true,

                ),
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min' => 6))
                )
            ))
            ->add('repeatPassword', PasswordType::class, array(
                'label' => 'label.repeat_password',
                'attr' => array(
                    'placeholder' => 'label.repeat_password',
                    'data-content' => $options['translator']->trans('help.password_length'),
                    'data-position' => 'top right'
                ),
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min' => 6))
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('translator');
    }

}