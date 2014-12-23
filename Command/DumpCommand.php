<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;

/**
 * Description of UpdateTranslationFiles
 *
 * @author georg
 */
class DumpCommand extends BaseTranslationCommand {

    protected function configure() {
        parent::configure();
        $this->setupArguments('regelwerk:translation:dump')
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force dump, even if not all translations are approved')
                ->addOption('dump-unapproved', 'a', InputOption::VALUE_NONE, 'If option force is set, keys without approval are dumped')
                ->addOption('dump-untranslated', 't', InputOption::VALUE_NONE, 'If option force is set, keys without translation are dumped')
                ->setDescription('Dumps the trasnslated files into the translation dir, so that they can be used');
    }

    protected function payload($domain) {
        $xliff = $this->translationService->getXliff($domain);
        if (!$xliff->isApproved() && !$this->input->getOption('force')) {
            $this->output->writeln(['', "<error>Error: Domain '$domain' is not approved.</error> Use option --force to dump it", '']);
        } else {
            $this->translationService->dump($domain, $this->input->getOption('dump-unapproved'), $this->input->getOption('dump-untranslated'));
        }
    }

}
