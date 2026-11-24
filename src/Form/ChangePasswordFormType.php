<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'options' => [
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                ],
            ],
            'first_options' => [
                'label' => 'Nouveau mot de passe',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un nouveau mot de passe.'),
                    new Length(
                        min: 12,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                    new PasswordStrength(
                        message: 'Votre mot de passe est trop facile à deviner. Renforcez-le.'
                    ),
                    new NotCompromisedPassword(
                        message: 'Ce mot de passe figure dans une liste de mots de passe compromis. Choisissez-en un autre.'
                    ),
                ],
            ],
            'second_options' => [
                'label' => 'Confirmer le nouveau mot de passe',
            ],
            'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
            'mapped' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}