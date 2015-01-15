<?php

namespace Regelwerk\TranslationBundle\Xliff;

/**
 * Description of TranslationUnit
 *
 * @author georg
 */
class TranslationUnit {

    const STATE_NEEDS_TRANSLATION = 'needs-translation';
    const STATE_NEEDS_APPROVAL = 'needs-approval';
    const STATE_APPROVED = 'approved';

    private $transUnit;

    public function __construct(\SimpleXMLElement $transUnit) {
        $this->transUnit = $transUnit;
        $this->transUnit->registerXPathNamespace('c', 'urn:oasis:names:tc:xliff:document:1.2');
    }

    public function getSourceText() {
        $sourceElement = $this->transUnit->xpath('c:alt-trans[@alttranstype="reference"]');
        if ($sourceElement) {
            return (string) $sourceElement[0]->target;
        }
        return '';
    }

    public function getTranslation() {
        return (string) $this->transUnit->target;
    }
    
    public function needsTranslation() {
        return $this->getState() == self::STATE_NEEDS_TRANSLATION;
    }
    
    public function needsApproval() {
        return $this->getState() == self::STATE_NEEDS_APPROVAL;
    }

    public function getState() {
        if (!isset($this->transUnit->target['state']) || $this->transUnit->target['state'] == 'needs-translation') {
            return self::STATE_NEEDS_TRANSLATION;
        }
        if (!isset($this->transUnit['approved']) || $this->transUnit['approved'] != 'yes') {
            return self::STATE_NEEDS_APPROVAL;
        }
        return self::STATE_APPROVED;
    }

    public function isApproved() {
        return $this->getState() == self::STATE_APPROVED;
    }

    public function setTranslated($username) {
        $this->setAttribute($this->transUnit, 'extradata', 'last-edit:' . $username);
        $this->setAttribute($this->transUnit->target, 'state', 'translated');
        $this->setAttribute($this->transUnit, 'approved', 'no');
        return $this;
    }

    public function canApprove($username) {
        return $this->getLastEdit() != $username;
    }

    public function setApproved($username, $force = false) {
        if ($force || $this->canApprove($username)) {
            $this->setTranslated($username);
            $this->setAttribute($this->transUnit, 'approved', 'yes');
        }
        return $this;
    }

    public function setNeedsTranslation($username = '') {
        $this->setAttribute($this->transUnit, 'extradata', 'last-edit:' . $username);
        $this->setAttribute($this->transUnit->target, 'state', 'needs-translation');
        $this->setAttribute($this->transUnit, 'approved', 'no');
        return $this;
    }

    private function setAttribute(\SimpleXMLElement $element, $attribute, $value) {
        if (!isset($element[$attribute])) {
            $element->addAttribute($attribute, $value);
        } else {
            $element[$attribute] = $value;
        }
    }

    public function getLastEdit() {
        return isset($this->transUnit['extradata']) && strpos($this->transUnit['extradata'], 'last-edit:') === 0 ? substr($this->transUnit['extradata'], 10) : '';
    }

    public function setTranslation($translation) {
        if ((string) $this->transUnit->target != $translation) {
            $altTrans = $this->transUnit->addChild('alt-trans');
            $altTrans->addChild('target', (string) $this->transUnit->target);
            $altTrans->addAttribute('alttranstype', 'previous-version');
            $altTrans->addAttribute('extradata', 'date:' . date('c'));
            $this->transUnit->target = $translation;
        }
        return $this;
    }

    public function updateSourceText($sourceText) {
        if ($sourceText != $this->getSourceText()) {
            $sourceElement = $this->transUnit->xpath('c:alt-trans[@alttranstype="reference"]');
            if ($sourceElement) {
                $sourceElement[0]->target = $sourceText;
            } else {
                $altTrans = $this->transUnit->addChild('alt-trans');
                $altTrans->addAttribute('alttranstype', 'reference');
                $altTrans->addChild('target', $sourceText);
            }
            $this->setNeedsTranslation();
        }
        return $this;
    }

    public function getTranslationKey() {
        return trim($this->transUnit->source);
    }

    public function getOldVersions() {
        $oldElements = $this->transUnit->xpath('c:alt-trans[@alttranstype="previous-version"]');
        $oldVersions = [];
        foreach ($oldElements as $oldElement) {
            $oldVersions[(string) $oldElement['extradata']] = (string) $oldElement->target;
        }
        arsort($oldVersions);
        return $oldVersions;
    }

    public function __destroy() {
        // remove the trans-unit element from the xliff body
        unset($this->transUnit[0]);
    }

    public function copyFromSource(TranslationUnit $source) {
        $key = $source->getTranslationKey();
        $this->setAttribute($this->transUnit, 'approved', 'no');
        $this->setAttribute($this->transUnit, 'id', $key);
        $this->transUnit->addChild('source', $key);
        $this->transUnit->addChild('target', $source->getTranslation())->addAttribute('state', 'needs-translation');
        $altTrans = $this->transUnit->addChild('alt-trans');
        $altTrans->addChild('target', $source->getTranslation());
        $altTrans->addAttribute('alttranstype', 'reference');
        $this->updateNotes($source);
    }

    public function isTranslated() {
        return $this->getState() != self::STATE_NEEDS_TRANSLATION;
    }

    public function getNotes() {
        $noteElements = $this->transUnit->xpath('c:note');
        $notes = [];
        foreach ($noteElements as $noteElement) {
            $notes[] = [
                'text' => (string) $noteElement,
                'from' => isset($noteElement['from']) ? (string) $noteElement['from'] : '',
                'priority' => isset($noteElement['priority']) ? (string) $noteElement['priority'] : 10,
            ];
        }
        usort($notes, function($n1, $n2) {
            return $n1['priority'] == $n2['priority'] ? 0 : $n1['priority'] > $n2['priority'] ? 1 : -1;
        });
        return $notes;
    }

    public function updateNotes(TranslationUnit $source) {
        $sourceNotes = $source->getNotes();
        $targetNotes = $this->getNotes();
        $changed = false;
        foreach ($sourceNotes as $key => $note) {
            if (
                    !isset($targetNotes[$key])
                    || $targetNotes[$key]['text'] != $note['text']
                    || $targetNotes[$key]['from'] != $note['from']
                    || $targetNotes[$key]['priority'] != $note['priority']
            ) {
                $changed = true;
                break;
            }
        }
        if ($changed) {
            $targetNotes = $this->transUnit->xpath('c:note');
            $keys = array_keys($targetNotes);
            foreach ($keys as $key) {
                unset($targetNotes[$key][0]);
            }
            foreach ($sourceNotes as $note) {
                $newNote = $this->transUnit->addChild('note', $note['text']);
                if ($note['from']) {
                    $newNote->addAttribute('from', $note['from']);
                }
                if ($note['priority']) {
                    $newNote->addAttribute('priority', $note['priority']);
                }
            }
            return true;
        }
        return false;
    }

}
