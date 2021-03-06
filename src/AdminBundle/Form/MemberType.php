<?php

namespace AdminBundle\Form;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MemberType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', TextType::class, array(
                'label' => 'label.username',
                'constraints' => array(
                    new Length(array('max' => 100))
                ),
                'attr' => [
                    'autofocus' => true
                ]
            ))
            ->add('email', EmailType::class, array(
                'attr' => array(
                    'autofocus' => true
                ),
                'label' => 'label.email',
                'constraints' => array(
                    new NotBlank(),
                    new Email()
                )
            ))
            ->add('plainPassword', PasswordType::class, array(
                'label' => 'label.password',
                'attr' => array(
                    'data-content' => $options['translator']->trans('help.password_length')
                ),
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min' => 6, 'max' => 4096))
                )
            ))
            ->add('phoneNumber', TextType::class, [
                'label' => 'label.phone_number',
                'mapped' => false,
                'constraints' => [
                    new Length(['min' => 6, 'max' => 10])
                ],
                'attr' => [
                    'placeholder' => '0614875696'
                ]
            ])
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));

        $resolver->setRequired('translator');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'adminbundle_user';
    }


}
