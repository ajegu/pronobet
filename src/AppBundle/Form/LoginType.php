<?php


namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('_username', EmailType::class, [
            'constraints' => [
                new NotBlank()
            ],
            'attr' => [
                'placeholder' => 'placeholder.email',
            ]

        ])
        ->add('_password', PasswordType::class, [
            'constraints' => [
                new NotBlank()
            ],
            'attr' => [
                'placeholder' => 'label.password',
            ]
        ])
        ->add('_target_path', HiddenType::class, [
            'attr' => [
                'value' => $options['redirect']
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('redirect');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return null;
    }

}