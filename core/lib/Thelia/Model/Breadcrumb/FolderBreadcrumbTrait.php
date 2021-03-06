<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Model\Breadcrumb;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Router;
use Thelia\Core\Template\Loop\FolderPath;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

trait FolderBreadcrumbTrait
{
    public function getBaseBreadcrumb(Router $router, ContainerInterface $container, $folderId)
    {
        $translator = Translator::getInstance();
        $foldersUrl = $router->generate('admin.folders.default', [], Router::ABSOLUTE_URL);
        $breadcrumb = [
            $translator->trans('Home') => URL::getInstance()->absoluteUrl('/admin'),
            $translator->trans('Folder') => $foldersUrl,
        ];

        // Todo stop using loop in php
        $folderPath = new FolderPath(
            $container,
            $container->get('request_stack'),
            $container->get('event_dispatcher'),
            $container->get('thelia.securityContext'),
            Translator::getInstance(),
            $container->getParameter('Thelia.parser.loops'),
            $container->getParameter('kernel.environment')
        );
        $folderPath->initializeArgs([
                'folder' => $folderId,
                'visible' => '*',
            ]);

        $results = $folderPath->buildArray();

        foreach ($results as $result) {
            $breadcrumb[$result['TITLE']] = sprintf(
                '%s?parent=%d',
                $router->generate(
                    'admin.folders.default',
                    [],
                    Router::ABSOLUTE_URL
                ),
                $result['ID']
            );
        }

        return $breadcrumb;
    }

    public function getFolderBreadcrumb(Router $router, $container, $tab, $locale)
    {
        if (!method_exists($this, 'getFolder')) {
            return null;
        }

        /** @var \Thelia\Model\Folder $folder */
        $folder = $this->getFolder();
        $breadcrumb = $this->getBaseBreadcrumb($router, $container, $this->getParentId());

        $folder->setLocale($locale);

        $breadcrumb[$folder->getTitle()] = sprintf(
            '%s?current_tab=%s',
            $router->generate(
                'admin.folders.update',
                ['folder_id' => $folder->getId()],
                Router::ABSOLUTE_URL
            ),
            $tab
        );

        return $breadcrumb;
    }

    public function getContentBreadcrumb(Router $router, ContainerInterface $container, $tab, $locale)
    {
        if (!method_exists($this, 'getContent')) {
            return null;
        }

        /** @var \Thelia\Model\Content $content */
        $content = $this->getContent();

        $breadcrumb = $this->getBaseBreadcrumb($router, $container, $content->getDefaultFolderId());

        $content->setLocale($locale);

        $breadcrumb[$content->getTitle()] = sprintf(
            '%s?current_tab=%s',
            $router->generate(
                'admin.content.update',
                ['content_id' => $content->getId()],
                Router::ABSOLUTE_URL
            ),
            $tab
        );

        return $breadcrumb;
    }
}
