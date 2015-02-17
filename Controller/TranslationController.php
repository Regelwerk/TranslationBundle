<?php

namespace Regelwerk\TranslationBundle\Controller;

use Regelwerk\TranslationBundle\Form\Type\TranslationType;
use Regelwerk\TranslationBundle\Form\Type\SearchType;
use Regelwerk\TranslationBundle\Form\Type\DumpType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of BusinessController
 *
 * @author georg
 */
class TranslationController extends Controller {

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
                    'searchForm' => $this->getSearchForm($lang)->createView(),
                    'dumpForm' => $this->getDumpForm($lang)->createView(),
        ]);
    }

    public function domainAction($domain, $lang) {
        $this->denyAccessUnlessGranted('ROLE_TRANSLATOR', $lang);
        $translation = $this->get('regelwerk_translation')->setLang($lang);
        $xliff = $translation->getXliff($domain);
        return $this->render('RegelwerkTranslationBundle:Translation:domain.html.twig', [
                    'transUnits' => $xliff,
                    'domain' => $domain,
                    'lang' => $lang,
                    'form' => $this->getSearchForm($lang)->createView(),
        ]);
    }

    public function editKeyAction($domain, $key, $lang, Request $request) {
        $this->denyAccessUnlessGranted('ROLE_TRANSLATOR', $lang);
        $username = $this->getUser()->getUsername();
        $translation = $this->get('regelwerk_translation')->setLang($lang);
        $xliff = $translation->getXliff($domain);
        $translationUnit = $xliff->get($key);
        $form = $this->createForm(new TranslationType(), $translationUnit);
        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('skip')->isClicked()) {
                $nextKey = $xliff->getNextEditableKey($key, $username);
                if ($nextKey) {
                    return $this->redirectToRoute('regelwerk_translation_edit_key', ['lang' => $lang, 'domain' => $domain, 'key' => $nextKey]);
                } else {
                    return $this->redirectToRoute('regelwerk_translation_domain', ['lang' => $lang, 'domain' => $domain]);
                }
            } elseif ($form->has('translated') && $form->get('translated')->isClicked()) {
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
                    'form' => $form->createView(),
                    'domain' => $domain,
                    'lang' => $lang,
                    'approveButtonEnabled' => $translationUnit->canApprove($username),
        ]);
    }

    public function searchAction($lang, Request $request) {
        $form = $this->getSearchForm($lang);
        if ($form->handleRequest($request)->isValid()) {
            $translation = $this->get('regelwerk_translation')->setLang($lang);
            $domains = $translation->getDomains();
            $matches = [];
            foreach ($domains as $domain) {
                $matches[$domain] = $translation->find($form->get('search')->getData(), $domain);
            }
            return $this->render('RegelwerkTranslationBundle:Translation:search.html.twig', [
                        'lang' => $lang,
                        'matches' => array_filter($matches),
                        'search' => $form->get('search')->getData(),
                        'form' => $form->createView(),
            ]);
        }
        return $this->redirectToRoute('regelwerk_translation_index', ['lang' => $lang]);
    }

    public function dumpAction($lang, Request $request) {
        $form = $this->getDumpForm($lang);
        if ($form->handleRequest($request)->isValid()) {
            $translation = $this->get('regelwerk_translation')->setLang($lang);
            $domains = $translation->getDomains();
            foreach ($domains as $domain) {
                $translation->dump($domain, $form->get('dumpUnapproved')->getData());
            }
        }
        return $this->redirectToRoute('regelwerk_translation_index', ['lang' => $lang]);
    }

    /**
     * 
     * @param string $lang
     * @return Symfony\Component\Form\Form
     */
    private function getSearchForm($lang) {
        $options = ['action' => $this->generateUrl('regelwerk_translation_search', ['lang' => $lang])];
        return $this->createForm(new SearchType(), null, $options);
    }

    /**
     * 
     * @param string $lang
     * @return Symfony\Component\Form\Form
     */
    private function getDumpForm($lang) {
        $options = ['action' => $this->generateUrl('regelwerk_translation_dump', ['lang' => $lang])];
        return $this->createForm(new DumpType(), null, $options);
    }

}
