<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Description of BaseTranslationCommand
 *
 * @author georg
 */
abstract class BaseTranslationCommand extends ContainerAwareCommand {

    protected $languages, $translationService, $input, $output;

    protected function setup($input, $output) {
        \Locale::setDefault('en');
        if ($input->getArgument('language') && $input->getArgument('language') != 'all') {
            $languages = Intl::getLanguageBundle()->getLanguageNames();
            if (!isset($languages[$input->getArgument('language')])) {
                $output->writeln('<error>Error: Unknown language</error>');
                return 1;
            }
            $this->languages = explode(',', $input->getArgument('language'));
        } else {
            $this->languages = $this->getContainer()->getParameter('regelwerk_translation.locales');
        }
        $this->translationService = $this->getContainer()->get('regelwerk_translation');
        if (null !== $input->getArgument('bundle')) {
            $this->translationService->setBundle($input->getArgument('bundle'));
        } elseif (is_null($this->translationService->getBundle())) {
            $translationPath = $this->getApplication()->getKernel()->getRootDir() . '/Resources/translations';
            $this->translationService->setPath($translationPath);
        }
        $this->output = $output;
        $this->input = $input;
    }

    protected function info($message, $newline = true) {
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->write($message, $newline);
        }
    }

    protected function setupArguments($name) {
        return $this
                        ->setName($name)
                        ->addArgument('domain', InputArgument::OPTIONAL, 'Single domain to approve', 'all')
                        ->addArgument('language', InputArgument::OPTIONAL, 'language to approve', 'all')
                        ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle where to load the messages, defaults to app/Resources folder', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->setup($input, $output);
        $domains = $this->translationService->getDomains($input->getArgument('domain') == 'all' ? '*' : $input->getArgument('domain'));
        foreach ($this->languages as $language) {
            $this->translationService->setLang($language);
            foreach ($domains as $domain) {
                $this->payload($domain);
            }
        }
    }
    
    abstract protected function payload($domain);
}
