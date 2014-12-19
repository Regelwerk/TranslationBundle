<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Intl\Intl;

/**
 * Description of UpdateTranslationFiles
 *
 * @author georg
 */
class DumpCommand extends ContainerAwareCommand {

    private $dryRun, $language, $translationPath, $output, $translationService;

    protected function configure() {
        parent::configure();

        $this->setName('regelwerk:translation:dump')
                ->addArgument('language', InputArgument::REQUIRED, 'language to dump')
                ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages, defaults to app/Resources folder', null)
                ->addArgument('domain', InputArgument::OPTIONAL, 'Single domain to dump', '*')
                ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force dump, even if not all translations are approved')
                ->addOption('dump-unapproved', 'a', InputOption::VALUE_NONE, 'If option force is set, keys without approval are dumped')
                ->addOption('dump-untranslated', 't', InputOption::VALUE_NONE, 'If option force is set, keys without translation are dumped')
                ->setDescription('Dumps the trasnslated files into the translation dir, so that they can be used');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->setup($input, $output);
        $domains = $this->translationService
                ->getDomains($input->getArgument('domain'));
        foreach ($domains as $domain) {
            $xliff = $this->translationService->getXliff($domain);
            if (!$xliff->isApproved() && !$input->getOption('force')) {
                $output->writeln(['', "<error>Error: Domain '$domain' is not approved.</error> Use option --force to dump it", '']);
            } else {
                $this->translationService->dump($domain, $input->getOption('dump-unapproved'), $input->getOption('dump-untranslated'));
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
        \Locale::setDefault('en');

        $languages = Intl::getLanguageBundle()->getLanguageNames();
        if (!isset($languages[$input->getArgument('language')])) {
            $output->writeln('<error>Error: Unknown language</error>');
            return 1;
        }
        $this->output = $output;
    }

}
