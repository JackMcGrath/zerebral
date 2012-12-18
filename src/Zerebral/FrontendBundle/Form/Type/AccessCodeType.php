<?php

namespace Zerebral\FrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Zerebral\BusinessBundle\Model\Course\Discipline;
use Zerebral\CommonBundle\Form\Type\OptionalModelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AccessCodeType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('accessCode', 'text', array('required' => false));
    }

    public function getName()
    {
        return 'members';
    }
}
