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

    const SEARCH_IN_SOURCE = 1;
    const SEARCH_IN_TARGET = 2;
    const SEARCH_IN_KEYS = 4;
    const SEARCH_EVERYWHERE = 7;

    private $path, $lang, $sourceLang, $kernel, $bundle = null, $stateDir;

    public function __construct($sourceLang = '', $stateDir = 'data/regelwerk/translation_state', $kernel = null, $bundle = '') {
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

    public function getSourceLang() {
        return $this->sourceLang;
    }

    public function setLang($lang) {
        $this->lang = $lang;
        return $this;
    }

    public function setBundle($bundle) {
        if ($bundle && is_null($this->kernel)) {
            throw new \LogicException('setBundle must have a kernel');
        }
        $this->setPath($this->kernel->getBundle($bundle)->getPath());
        $this->bundle = $bundle;
        return $this;
    }

    public function getBundle() {
        return $this->bundle;
    }

    /**
     *
     * @param string $domain
     * @param bool $sort
     * @return array
     */
    public function getDomains($domain = '*', $sort = true) {
        if ($domain != '*') {
            return [$domain];
        }
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->name("/\\.{$this->sourceLang}\\.xlf$/")->in($this->getTranslationDir());
        $domains = [];
        foreach ($finder as $file) {
            $domains[] = substr($file->getBasename(), 0, -7);
        }
        if ($sort) {
            natcasesort($domains);
        }
        return $domains;
    }

    protected function getTranslationDir() {
        return $this->path . '/Resources/translations';
    }

    protected function getStateDir() {
        return $this->path . '/' . $this->stateDir;
    }

    /**
     *
     * @param string $domain
     * @param string $username
     * @return array
     */
    public function getStats($domain, $username) {
        $xliff = new XliffFile($this->getStateDir(), $domain, $this->lang, $this->sourceLang);
        return [
            'total' => $xliff->getEntryCount(),
            'needsTranslation' => $xliff->getMissingTranslationCount(),
            'notApproved' => $xliff->getMissingApprovalCount(),
            'nextKey' => $xliff->getNextEditableKey('', $username),
        ];
    }

    /**
     *
     * @param string $domain
     * @param string $lang
     * @return XliffFile
     */
    public function getXLiff($domain, $lang = '') {
        if ($lang == '') {
            $lang = $this->lang;
        }
        return new XliffFile($this->getStateDir(), $domain, $lang, $this->sourceLang);
    }

    /**
     *
     * @param string $domain
     * @return XliffFile
     */
    public function getSourceXliff($domain) {
        return new XliffFile($this->getTranslationDir(), $domain, $this->sourceLang);
    }

    /**
     *
     * @param string $domain
     * @param string $lang
     * @param boolean $dryRun
     * @param boolean $formatted
     * @return array of messages
     */
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
                if ($translationUnitTarget->updateNotes($translationUnitSource)) {
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

    /**
     *
     * @param string $domain
     * @param boolean $dumpUnapproved
     * @param boolean $dumpUntranslated
     * @param string $lang
     */
    public function dump($domain, $dumpUnapproved = true, $dumpUntranslated = false, $lang = '') {
        if ($lang == '') {
            $lang = $this->lang;
        }
        $xliff = $this->getXliff($domain, $lang);
        $xml = $xliff->dumpClean($lang, $this->sourceLang, $dumpUnapproved, $dumpUntranslated);
        file_put_contents($this->getTranslationDir() . "/{$domain}.{$lang}.xlf", $xml);
    }

    /**
     *
     * @param string $search
     * @param string $domain
     * @param integer $searchIn
     * @param string $lang
     */
    public function find($search, $domain, $searchIn = self::SEARCH_EVERYWHERE, $lang = '') {
        if ($lang == '') {
            $lang = $this->lang;
        }
        $matches = [];
        $xliffTarget = $this->getXliff($domain, $lang);
        foreach ($xliffTarget as $translationUnit) {
            if (
                    (($searchIn & self::SEARCH_IN_KEYS) && stripos($translationUnit->getTranslationKey(), $search) !== false) ||
                    (($searchIn & self::SEARCH_IN_SOURCE) && stripos($translationUnit->getSourceText(), $search) !== false) ||
                    (($searchIn & self::SEARCH_IN_TARGET) && stripos($translationUnit->getTranslation(), $search) !== false)
            ) {
                $matches[] = $translationUnit;
            }
        }
        return $matches;
    }

}
