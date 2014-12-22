<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Description of checkSystemCommand
 *
 * @author georg
 */
class MergeCommand extends BaseTranslationCommand {

    protected function configure() {
        parent::configure();
        $this->setName('regelwerk:translation:merge')
                ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Don\'t merge, only show what would be done')
                ->addArgument('language', InputArgument::REQUIRED, 'language to merge into')
                ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages, defaults to app/Resources folder', null)
                ->addArgument('domain', InputArgument::OPTIONAL, 'Single domain to merge', '*')
                ->setDescription('Merges and updates XLIFF files. Run it with -d and -v option at first to see what would happen if it was let loose ;-)');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->setup($input, $output);
        $domains = $this->translationService->getDomains($input->getArgument('domain'));
        foreach ($domains as $domain) {
            $this->info("Processing <info>$domain</info>");
            foreach ($this->translationService->merge($domain, $this->language, $input->getOption('dry-run'), true) as $message) {
                $this->info($message);
            }
        }
    }

}
