<?php

namespace Regelwerk\TranslationBundle\Service;

use Symfony\Component\Finder\Finder;
use Regelwerk\TranslationBundle\Xliff\XliffFile;

/**
 * Description of TranslationService
 *
 * @author georg
 */
class TranslationService {

    private $path, $lang, $sourceLang, $kernel, $bundle = null, $stateDir;

    public function __construct($sourceLang = '', $stateDir = 'regelwerk_translation_state', $kernel = null, $bundle = '') {
        $this->kernel = $kernel;
        $this->setSourceLang($sourceLang);
        $this->setBundle($bundle);
        $this->stateDir = $stateDir;
    }

    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    public function setSourceLang($sourceLang) {
        $this->sourceLang = $sourceLang;
        return $this;
    }

    public function setLang($lang) {
        $this->lang = $lang;
        return $this;
    }

    public function setBundle($bundle) {
        if ($bundle && is_null($this->kernel)) {
            throw new \LogicException('setBundle must have a kernel');
        }
        $this->setPath($this->kernel->getBundle($bundle)->getPath() . '/Resources/translations');
        $this->bundle = $bundle;
        return $this;
    }

    public function getBundle() {
        return $this->bundle;
    }

    public function getDomains($domain = '*') {
        if ($domain != '*') {
            return [$domain];
        }
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->name("/\\.{$this->sourceLang}\\.xlf$/")->in($this->path);
        $domains = [];
        foreach ($finder as $file) {
            $domains[] = substr($file->getBasename(), 0, -7);
        }
        return $domains;
    }

    public function getStats($domain, $username) {
        $xliff = new XliffFile($this->path . '/' . $this->stateDir, $domain, $this->lang, $this->sourceLang);
        return [
            'total' => $xliff->getEntryCount(),
            'needsTranslation' => $xliff->getMissingTranslationCount(),
            'notApproved' => $xliff->getMissingApprovalCount(),
            'nextKey' => $xliff->getNextEditableKey('', $username),
        ];
    }

    public function getXLiff($domain, $lang = '') {
        if ($lang == '') {
            $lang = $this->lang;
        }
        return new XliffFile($this->path . '/' . $this->stateDir, $domain, $lang, $this->sourceLang);
    }

    public function getSourceXliff($domain) {
        return new XliffFile($this->path, $domain, $this->sourceLang);
    }

    public function merge($domain, $lang = '', $dryRun = true, $formatted = false) {
        if ($lang == '') {
            $lang = $this->lang;
        }
        $messages = [];
        $xliffSource = $this->getSourceXliff($domain);
        $xliffTarget = $this->getXliff($domain, $lang);
        $changed = false;
        foreach ($xliffSource->getKeys() as $key) {
            if (!$xliffTarget->has($key)) {
                $messages[] = "Missing: <comment>$key</comment>";
                $xliffTarget->insertFromSource($xliffSource->get($key));
                $changed = true;
            }
        }
        foreach ($xliffTarget->getKeys() as $key) {
            if (!$xliffSource->has($key)) {
                $messages[] = "Unused: <error>$key</error>";
                $xliffTarget->remove($key);
                $changed = true;
            } else {
                $translationUnitSource = $xliffSource->get($key);
                $translationUnitTarget = $xliffTarget->get($key);
                if ($translationUnitTarget->getSourceText() != $translationUnitSource->getTranslation()) {
                    $translationUnitTarget->updateSourceText($translationUnitSource->getTranslation());
                    $messages[] = "Updating source sting: <comment>$key</comment>";
                    $changed = true;
                }
                if ($translationUnitTarget->updateNotes($translationUnitSource)){
                    $messages[] = "Updating notes: <comment>$key</comment>";
                    $changed = true;
                }
            }
        }
        if ($changed && !$dryRun) {
            $xliffTarget->write();
        }
        if ($formatted) {
            return $messages;
        }
        return array_map(function($message) {
            return preg_replace('/<[^>]+>/', '', $message);
        });
    }

    public function dump($domain, $dumpUnapproved = true, $dumpUntranslated = false, $lang = '') {
        if ($lang == '') {
            $lang = $this->lang;
        }
        $xliff = $this->getXliff($domain, $lang);
        $xml = $xliff->dumpClean($lang, $this->sourceLang, $dumpUnapproved, $dumpUntranslated);
        file_put_contents("{$this->path}/$domain.{$lang}.xlf", $xml);
    }

}
