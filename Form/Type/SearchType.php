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
class SearchType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('search', 'text', [
                    'label' => 'search_field',
                    'attr' => ['autofocus' => true, 'placeholder' => 'search_field'],
                    'required' => true,
        ]);
    }

    public function getName() {
        return 'regelwerk_search_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'translation_domain' => 'regelwerk_translation',
        ]);
    }

}
