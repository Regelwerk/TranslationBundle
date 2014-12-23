<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;

/**
 * Description of ApproveCommand
 *
 * @author georg
 */
class ApproveCommand extends BaseTranslationCommand {

    protected function configure() {
        parent::configure();
        $this->setupArguments('regelwerk:translation:approve')
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force dump, even if not all translations are approved')
                ->setDescription('Approves the translation file(s). This is useful if you do not follow a translate/approve workflow.');
    }

    public function payload($domain) {
        $xliff = $this->translationService->getXliff($domain);
        if (!$xliff->isTranslated() && !$this->input->getOption('force')) {
            $this->output->writeln(['', "<error>Error: Domain '$domain' is not (completely) translated.</error> Use option --force to approve it (this also sets all keys to translated).", '']);
        } else {
            $xliff->setApproved('system-cli');
        }
    }

}
