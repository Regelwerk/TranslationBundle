<?php

namespace Regelwerk\TranslationBundle\Form\Type;

use Regelwerk\TranslationBundle\Xliff\TranslationUnit as State;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Description of TranslationForm
 *
 * @author georg
 */
class TranslationType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'setButtons']);
        $builder
                ->add('translation', 'text', [
                    'label' => 'translation',
                    'attr' => ['autofocus' => true]
                    ])
                ->add('continue', 'checkbox', [
                    'label' => 'continue_to_next',
                    'data' => true,
                    'mapped' => false
                    ]);
    }

    public function getName() {
        return 'regelwerk_translation_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults([
            'translation_domain' => 'regelwerk_translation',
        ]);
    }

    public function setButtons(FormEvent $event) {
        $translationUnit = $event->getData();
        $form = $event->getForm();
        switch ($translationUnit->getState()) {
            case State::STATE_NEEDS_TRANSLATION:
                $form
                        ->add('translated', 'submit', ['label' => 'save_as_translated'])
                        ->add('leaveState', 'submit', ['label' => 'save_keep_state']);
                break;
            case State::STATE_NEEDS_APPROVAL:
                $form
                        ->add('approve', 'submit', ['label' => 'approve'])
                        ->add('translated', 'submit', ['label' => 'save_changed_translation']);
                break;
            default:
                $form
                        ->add('translated', 'submit', ['label' => 'save_changed_translation']);
                break;
        }
    }

}
