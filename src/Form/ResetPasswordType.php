<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResetPasswordType extends AbstractType
{
    public function getParent()
    {
        return ChangePasswordType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('currentPassword');
    }
}
