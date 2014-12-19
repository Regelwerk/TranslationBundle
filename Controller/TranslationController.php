<?php

namespace Regelwerk\TranslationBundle\Controller;

use Regelwerk\TranslationBundle\Form\Type\TranslationType;
use Regelwerk\TranslationBundle\Xliff\TranslationUnit as State;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of BusinessController
 *
 * @author georg
 */
class TranslationController extends Controller {

    const TRANSLATION_DOMAIN = 'translation';

    public function indexAction($lang) {
        $this->denyAccessUnlessGranted('ROLE_TRANSLATOR', $lang);
        $translation = $this->get('regelwerk_translation')->setLang($lang);
        $domains = $translation->getDomains();
        $stats = [];
        foreach ($domains as $domain) {
            $stats[$domain] = $translation->getStats($domain, $this->getUser()->getUsername());
        }
        return $this->render('RegelwerkTranslationBundle:Translation:index.html.twig', [
                    'domainStats' => $stats,
                    'summary' => [
                        'total' => array_sum(array_column($stats, 'total')),
                        'needsTranslation' => array_sum(array_column($stats, 'needsTranslation')),
                        'notApproved' => array_sum(array_column($stats, 'notApproved')),
                    ],
                    'lang' => $lang,
        ]);
    }

    public function domainAction($domain, $lang) {
        $this->denyAccessUnlessGranted('ROLE_TRANSLATOR', $lang);
        $bootstrapIconMap = $this->getBootstrapIcons();
        $translation = $this->get('regelwerk_translation')->setLang($lang);
        $xliff = $translation->getXliff($domain);
        $mappedTransUnits = [];
        foreach ($xliff as $transUnit) {
            $mappedTransUnits[] = [
                'source' => (string) $transUnit->getTranslationKey(),
                'target' => (string) $transUnit->getTranslation(),
                'state' => $bootstrapIconMap[$transUnit->getState()],
            ];
        }
        return $this->render('RegelwerkTranslationBundle:Translation:domain.html.twig', [
                    'transUnits' => $mappedTransUnits,
                    'domain' => $domain,
                    'lang' => $lang,
        ]);
    }

    protected function getBootstrapIcons() {
        return [
            State::STATE_NEEDS_TRANSLATION => 'pencil',
            State::STATE_NEEDS_APPROVAL => 'eye-open',
            State::STATE_APPROVED => 'ok',
        ];
    }

    public function editKeyAction($domain, $key, $lang, Request $request) {
        $this->denyAccessUnlessGranted('ROLE_TRANSLATOR', $lang);
        $username = $this->getUser()->getUsername();
        $translation = $this->get('regelwerk_translation')->setLang($lang);
        $xliff = $translation->getXliff($domain);
        $translationUnit = $xliff->get($key);
        $form = $this->createForm(new TranslationType(), $translationUnit);
        if ($form->handleRequest($request)->isValid()) {
            if ($form->has('translated') && $form->get('translated')->isClicked()) {
                $translationUnit->setTranslated($username);
            } elseif ($form->has('approve') && $form->get('approve')->isClicked()) {
                $translationUnit->setApproved($username);
            }
            $xliff->write($domain, $xliff);
            if ($form->get('continue')->getData() && $nextKey = $xliff->getNextEditableKey($key, $username)) {
                return $this->redirectToRoute('regelwerk_translation_edit_key', ['lang' => $lang, 'domain' => $domain, 'key' => $nextKey]);
            }
            return $this->redirectToRoute('regelwerk_translation_domain', ['lang' => $lang, 'domain' => $domain]);
        }
        return $this->render('RegelwerkTranslationBundle:Translation:edit_key.html.twig', [
                    'key' => $key,
                    'form' => $form->createView(),
                    'domain' => $domain,
                    'lang' => $lang,
                    'originalText' => $translationUnit->getSourceText(),
                    'state' => $translationUnit->getState(),
                    'approveButtonEnabled' => $translationUnit->canApprove($username),
        ]);
    }

}
