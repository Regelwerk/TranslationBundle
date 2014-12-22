<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Description of ApproveCommand
 *
 * @author georg
 */
class ApproveCommand extends BaseTranslationCommand {

    protected function configure() {
        parent::configure();

        $this->setName('regelwerk:translation:approve')
                ->addArgument('language', InputArgument::REQUIRED, 'language to approve')
                ->addArgument('domain', InputArgument::OPTIONAL, 'Single domain to approve', '*')
                ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages, defaults to app/Resources folder', null)
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force dump, even if not all translations are approved')
                ->setDescription('Approves the translation file(s). This is useful if you do not follow a translate/approve workflow.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->setup($input, $output);
        $domains = $this->translationService->getDomains($input->getArgument('domain'));
        foreach ($domains as $domain) {
            $xliff = $this->translationService->getXliff($domain);
            if (!$xliff->isTranslated() && !$input->getOption('force')) {
                $output->writeln(['', "<error>Error: Domain '$domain' is not (completely) translated.</error> Use option --force to approve it (this also sets all keys to translated).", '']);
            } else {
                 $xliff->setApproved('system-cli');
            }
        }
    }


}
