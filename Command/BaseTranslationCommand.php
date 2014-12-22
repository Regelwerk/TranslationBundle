<?php

namespace Regelwerk\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Intl\Intl;

/**
 * Description of BaseTranslationCommand
 *
 * @author georg
 */
class BaseTranslationCommand extends ContainerAwareCommand {
    protected $language, $translationService, $output;

    protected function setup($input, $output) {
        \Locale::setDefault('en');

        $languages = Intl::getLanguageBundle()->getLanguageNames();
        if (!isset($languages[$input->getArgument('language')])) {
            $output->writeln('<error>Error: Unknown language</error>');
            return 1;
        }
        $this->language = $input->getArgument('language');
        $this->translationService = $this->getContainer()->get('regelwerk_translation')->setLang($this->language);
        if (null !== $input->getArgument('bundle')) {
            $this->translationService->setBundle($input->getArgument('bundle'));
        } elseif (is_null($this->translationService->getBundle())) {
            $translationPath = $this->getApplication()->getKernel()->getRootDir() . '/Resources/translations';
            $this->translationService->setPath($translationPath);
        }
        $this->output = $output;
    }

    protected function info($message, $newline = true) {
        if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
            $this->output->write($message, $newline);
        }
    }

}
