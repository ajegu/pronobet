<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;


class SubscriptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('emailNotification', CheckboxType::class, [
                'label' => 'label.email_notification'
            ])
            ->add('smsNotification', CheckboxType::class, [
                'label' => 'label.sms_notification'
            ])
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
            'data_class' => 'AppBundle\Entity\Subscription'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_subscription';
    }


}
