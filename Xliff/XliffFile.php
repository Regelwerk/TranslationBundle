<?php

namespace Regelwerk\TranslationBundle\Xliff;

/**
 * Description of XliffFile
 *
 * @author georg
 */
class XliffFile implements \Iterator {

    private $path, $xliff, $transUnits = false;

    public function __construct($path, $domain, $language, $sourceLanguage = '') {
        $this->path = "{$path}/$domain.{$language}.xlf";

        if (!file_exists($this->path)) {
            $this->xliff = $this->getEmpty($language, $sourceLanguage);
        } else {
            $this->xliff = simplexml_load_file($this->path, 'SimpleXMLElement', LIBXML_NOBLANKS);
        }
        $this->xliff->registerXPathNamespace('c', 'urn:oasis:names:tc:xliff:document:1.2');
        $this->extractTranslationUnits();
    }

    public function has($key) {
        return isset($this->transUnits[$key]);
    }

    public function get($key) {
        return $this->transUnits[$key];
    }

    public function insertFromSource(TranslationUnit $source) {
        $key = $source->getTranslationKey();
        $transUnit = $this->xliff->file->body->addChild('trans-unit');
        $this->transUnits[$key] = new TranslationUnit($transUnit);
        $this->transUnits[$key]->copyFromSource($source);
    }

    public function remove($key) {
        unset($this->transUnits[$key]);
    }

    public function write() {
        if (!file_exists(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }
        file_put_contents($this->path, $this->formatXml($this->xliff));
    }

    private function extractTranslationUnits() {
        $this->transUnits = [];
        foreach ($this->xliff->file->body->children() as $transUnit) {
            $id = (string) $transUnit['id'];
            $key = trim($transUnit->source);
            if ($key != $id) {
                throw new \Exception("{$this->path}: key '$key' != id '$id'");
            }
            $this->transUnits[$id] = new TranslationUnit($transUnit);
        }
    }

    public function getPath() {
        return $this->path;
    }

    public function getKeys() {
        return array_keys($this->transUnits);
    }

    public function getEntryCount() {
        return count($this->transUnits);
    }

    public function getMissingTranslationCount() {
        $count = 0;
        foreach ($this->transUnits as $transUnit) {
            if (!$transUnit->isTranslated()) {
                $count++;
            }
        }
        return $count;
    }

    public function getMissingApprovalCount() {
        $count = 0;
        foreach ($this->transUnits as $transUnit) {
            if (!$transUnit->isApproved() && $transUnit->isTranslated()) {
                $count++;
            }
        }
        return $count;
    }

    public function isApproved() {
        return $this->getMissingTranslationCount() == 0 && $this->getMissingApprovalCount() == 0;
    }

    private function getEmpty($lang, $sourceLang) {
        $date = date('c');
        return simplexml_load_string(<<<EOX
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-core-1.2-strict.xsd">
    <file original="global" source-language="$sourceLang" target-language="$lang" datatype="plaintext" date="$date">
        <body>
        </body>
    </file>
</xliff>
EOX
                , 'SimpleXMLElement', LIBXML_NOBLANKS);
    }

    public function dumpClean($lang, $sourceLang, $dumpUnapproved = true, $dumpUntranslated = false) {
        $xliff = $this->getEmpty($lang, $sourceLang);
        foreach ($this->transUnits as $key => $translationUnit) {
            if ($translationUnit->isApproved() || ($translationUnit->isTranslated() && $dumpUnapproved) || $dumpUntranslated) {
                $transUnit = $xliff->file->body->addChild('trans-unit');
                $transUnit->addAttribute('id', $key);
                $transUnit->addChild('source', $key);
                $transUnit->addChild('target', $translationUnit->getTranslation());
            }
        }
        return $this->formatXml($xliff);
    }

    private function formatXml(\SimpleXMLElement $xliff) {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xliff->asXML());
        return $dom->saveXML();
    }

    public function rewind() {
        reset($this->transUnits);
    }

    public function current() {
        return current($this->transUnits);
    }

    public function key() {
        return key($this->transUnits);
    }

    public function next() {
        return next($this->transUnits);
    }

    public function valid() {
        $key = key($this->transUnits);
        return ($key !== NULL && $key !== FALSE);
    }

    public function getNextEditableKey($key, $username) {
        $keySeen = $key ? false : true;
        $nextKey = '';
        foreach ($this->transUnits as $tkey => $translationUnit) {
            if ($tkey == $key) {
                $keySeen = true;
                continue;
            }
            if (!$translationUnit->isTranslated() || (!$translationUnit->isApproved() && $translationUnit->canApprove($username))) {
                $nextKey = $tkey;
                if ($keySeen) {
                    break;
                }
            }
        }
        return $nextKey;
    }

}
