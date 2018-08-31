<?php

/*
 * Twig extension for TYPO3 CMS
 * Copyright (C) 2018 CARL von CHIARI GmbH
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Cvc\Typo3\CvcTwig\Mvc\View;

use Cvc\Typo3\CvcTwig\Twig\Cache\Typo3Cache;
use Cvc\Typo3\CvcTwig\Twig\Environment;
use Cvc\Typo3\CvcTwig\Twig\Extension\ExtbaseDebugExtension;
use Cvc\Typo3\CvcTwig\Twig\Extension\FormExtension;
use Cvc\Typo3\CvcTwig\Twig\Extension\HtmlFormatExtension;
use Cvc\Typo3\CvcTwig\Twig\Extension\ImageExtension;
use Cvc\Typo3\CvcTwig\Twig\Extension\TranslationExtension;
use Cvc\Typo3\CvcTwig\Twig\Extension\UriExtension;
use Cvc\Typo3\CvcTwig\Twig\Loader\Typo3Loader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * A standalone view.
 * Should be used as view if you want to use Twig without Extbase.
 *
 * In contrast to the Fluid standalone view this class has no dependency to Extbase.
 */
class StandaloneView
{
    /**
     * @var string
     */
    protected $templateName;

    /**
     * @var string[]
     */
    protected $templateRootPaths = [];

    /**
     * @var array
     *
     * @see assign()
     */
    protected $variables;

    /**
     * Class names of extensions to be added to the environment.
     *
     * @var string[]
     */
    protected $extensions = [
        ExtbaseDebugExtension::class,
        FormExtension::class,
        HtmlFormatExtension::class,
        ImageExtension::class,
        TranslationExtension::class,
        UriExtension::class,
    ];

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * Renders the view.
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     *
     * @return string The rendered view
     */
    public function render()
    {
        $templatePaths = array_map(function (string $path) {
            return GeneralUtility::getFileAbsFileName($path);
        }, $this->templateRootPaths);

        // reverse order of template paths since twig will match first path first
        // to match the fluid behavior and to make it easier to extend, we want to match last path first
        $templatePaths = array_reverse($templatePaths, true);

        // ensure that a controller context exists
        if ($this->controllerContext === null) {
            $this->createControllerContext();
        }

        $twigEnvironment = new Environment(
            new ChainLoader([
                new FilesystemLoader($templatePaths),
                new Typo3Loader(),
            ]),
            [
                'debug' => $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'],
                'cache' => GeneralUtility::makeInstance(Typo3Cache::class),
            ],
            $this->controllerContext
        );

        foreach ($this->extensions as $extensionClass) {
            $twigEnvironment->addExtension(GeneralUtility::makeInstance($extensionClass));
        }

        return $twigEnvironment->render($this->templateName, $this->variables);
    }

    /**
     * Add a variable to $this->viewData.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible.
     *
     * @param string $key   Key of variable
     * @param mixed  $value Value of object
     *
     * @return $this
     */
    public function assign($key, $value)
    {
        $this->variables[$key] = $value;

        return $this;
    }

    /**
     * Add multiple variables to $this->viewData.
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2)
     *
     * @return $this
     */
    public function assignMultiple(array $values)
    {
        foreach ($values as $key => $value) {
            $this->assign($key, $value);
        }

        return $this;
    }

    /**
     * Set the template name.
     *
     * @param string $templateName
     */
    public function setTemplateName(string $templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * Sets the template root paths.
     *
     * @param string[] $templateRootPaths
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->templateRootPaths = $templateRootPaths;
    }

    /**
     * Creates a new controller context if no context was set from external.
     */
    private function createControllerContext(): void
    {
        if ($this->controllerContext !== null) {
            throw new \LogicException('The controllerContext was already created.');
        }

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $request = $objectManager->get(Request::class);
        $request->setRequestUri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseUri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $uriBuilder = $objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($request);
        $this->controllerContext = $objectManager->get(ControllerContext::class);
        $this->controllerContext->setRequest($request);
        $this->controllerContext->setUriBuilder($uriBuilder);
    }
}
