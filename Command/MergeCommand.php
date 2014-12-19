<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Intl\Intl;

/**
 * Description of checkSystemCommand
 *
 * @author georg
 */
class MergeCommand extends ContainerAwareCommand {

    private $dryRun, $language, $translationPath, $output, $translationService;

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
        $domains = $this->translationService
                ->getDomains($input->getArgument('domain'));
        foreach ($domains as $domain) {
            $this->info("Processing <info>$domain</info>");
            foreach ($this->translationService->merge($domain, $this->language, $this->dryRun, true) as $message) {
                $this->info($message);
            }
        }
    }

    private function setup($input, $output) {
        $this->language = $input->getArgument('language');
        $this->translationService = $this->getContainer()->get('regelwerk_translation')->setLang($this->language);
        if (null !== $input->getArgument('bundle')) {
            $this->translationService->setBundle($input->getArgument('bundle'));
        } elseif (is_null($this->translationService->getBundle())) {
            $this->translationPath = $this->getApplication()->getKernel()->getRootDir() . '/Resources/translations';
            $this->translationService->setPath($this->translationPath);
        }
        $this->dryRun = $input->getOption('dry-run');
        \Locale::setDefault('en');

        $languages = Intl::getLanguageBundle()->getLanguageNames();
        if (!isset($languages[$input->getArgument('language')])) {
            $output->writeln('<error>Error: Unknown language</error>');
            return 1;
        }
        $this->output = $output;
    }

    private function info($message, $newline = true) {
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->write($message, $newline);
        }
    }

}
