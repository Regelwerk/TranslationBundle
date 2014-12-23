<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;

/**
 * Description of checkSystemCommand
 *
 * @author georg
 */
class MergeCommand extends BaseTranslationCommand {

    protected function configure() {
        parent::configure();
        $this->setupArguments('regelwerk:translation:merge')
                ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Don\'t merge, only show what would be done')
                ->setDescription('Merges and updates XLIFF files. Run it with -d and -v option at first to see what would happen if it was let loose ;-)');
    }

    protected function payload($domain) {
        $this->info("Processing <info>$domain</info>");
        foreach ($this->translationService->merge($domain, $this->language, $this->input->getOption('dry-run'), true) as $message) {
            $this->info($message);
        }
    }

}
