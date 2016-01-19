<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace OnBoarding;

use Shudrum\Component\ArrayFinder\ArrayFinder;
use Symfony\Component\Yaml\Yaml;

class OnBoarding
{
    private $loader;
    private $twig;

    /** @var ArrayFinder */
    private $localization;
    private $steps;

    private $configuration;

    /**
     * OnBoarding constructor.
     *
     * @param string $languageIsoCode Language ISO code.
     */
    public function __construct($languageIsoCode = 'en')
    {
        $this->loader = new \Twig_Loader_Filesystem(realpath(__DIR__.'/../views')); // TODO: A injecter
        $this->twig = new \Twig_Environment($this->loader);

        $this->configuration = Yaml::parse(file_get_contents(__DIR__.'/../config/configuration.yml'));

        $this->loadSteps(__DIR__.'/../config', $languageIsoCode);
    }

    public function showModuleContent()
    {
        $templates = [];
        foreach ($this->configuration['templates'] as $template) {
            $templates[] = [
                'name'    => $template,
                'content' => $this->getTemplateContent("templates/$template"),
            ];
        }

        echo $this->getTemplateContent('content', [
            'currentStep' => $this->getCurrentStep(),
            'totalSteps'  => $this->getTotalSteps(),
            'steps'       => $this->steps,
            'jsonSteps'   => json_encode($this->steps),
            'templates'   => $templates,
        ]);
    }

    public function setCurrentStep($step)
    {
        // TODO: Find how to inject the Configuration if not done
        return \Configuration::updateValue('ONBOARDINGV2_CURRENT_STEP', $step);
    }

    private function loadSteps($configPath, $languageIsoCode)
    {
        $this->localization = Yaml::parse(file_get_contents($configPath.'/localization/'.$languageIsoCode.'.yml'));
        $this->localization = new ArrayFinder($this->localization);

        $steps = Yaml::parse(file_get_contents($configPath.'/steps.yml'));

        foreach ($steps['groups'] as &$currentGroup) {
            $currentGroup['title'] = $this->getTextFromSettings($currentGroup['title']);
            foreach ($currentGroup['steps'] as &$currentStep) {
                $currentStep['title'] = $this->getTextFromSettings($currentStep['title']);
                $currentStep['text'] = $this->getTextFromSettings($currentStep['text']);
            }
        }

        $this->steps = $steps;
    }

    private function getTextFromSettings($text)
    {
        switch (array_keys($text)[0]) {
            case 'loc':
                return $this->localization[$text['loc']];
            case 'content':
                return $this->getTemplateContent($text['content']);
        }

        return null;
    }

    public function showTemplate($templateName)
    {
        echo $this->getTemplateContent($templateName);
    }

    /**
     * Display the current step.
     *
     * @return null
     */
    private function getTemplateContent($templateName, $additionnalParameters = []) // TODO: Find a better name
    {
        return $this->twig->render($templateName.'.twig', array_merge(
            $additionnalParameters,
            $this->localization->get()
        ));
    }

    /**
     * Return the current step.
     *
     * @return int Current step.
     */
    private function getCurrentStep()
    {
        // TODO: Find how to inject the Configuration if not done
        return (int)\Configuration::get('ONBOARDINGV2_CURRENT_STEP');
    }

    private function getTotalSteps()
    {
        $total = 0;

        if ($this->steps != null) {
            foreach ($this->steps['groups'] as &$group) {
                $total += count($group['steps']);
            }
        }

        return $total;
    }
}