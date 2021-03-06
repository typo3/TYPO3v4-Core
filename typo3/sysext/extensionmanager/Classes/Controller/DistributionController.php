<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Controller for distribution related actions
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class DistributionController extends AbstractModuleController
{
    protected PackageManager $packageManager;
    protected PageRenderer $pageRenderer;

    public function __construct(PackageManager $packageManager, PageRenderer $pageRenderer)
    {
        $this->packageManager = $packageManager;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            /** @var BackendTemplateView $view */
            parent::initializeView($view);
            $this->generateMenu();
            $this->registerDocHeaderButtons();
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Extensionmanager/DistributionImage');
        }
    }

    /**
     * Shows information about the distribution
     *
     * @param Extension $extension
     * @return ResponseInterface
     */
    public function showAction(Extension $extension): ResponseInterface
    {
        $extensionKey = $extension->getExtensionKey();
        // Check if extension/package is installed
        $active = $this->packageManager->isPackageActive($extensionKey);

        $this->view->assign('distributionActive', $active);
        $this->view->assign('extension', $extension);

        return $this->htmlResponse();
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        $uri = $this->uriBuilder->reset()->uriFor('distributions', [], 'List');
        $title = $this->translate('extConfTemplate.backToList');
        $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
        $button = $buttonBar->makeLinkButton()
            ->setHref($uri)
            ->setTitle($title)
            ->setIcon($icon);
        $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT);
    }
}
