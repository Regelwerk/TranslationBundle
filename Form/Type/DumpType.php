<?php

namespace Regelwerk\TranslationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of TranslationForm
 *
 * @author georg
 */
class DumpType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('dumpUnapproved', 'checkbox', [
                    'label' => 'dump_unapproved',
                    'required' => false,
        ]);
    }

    public function getName() {
        return 'regelwerk_dump_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'translation_domain' => 'regelwerk_translation',
        ]);
    }

}
