<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tool;

use Pimcore\Model\Document;
use Pimcore\Model\Site;

class Frontend
{
    /**
     * Returns the Website-Config
     *
     * @return \Pimcore\Config\Config
     *
     * @deprecated
     */
    public static function getWebsiteConfig()
    {
        return \Pimcore\Config::getWebsiteConfig();
    }

    /**
     * @param Site $site
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getSiteKey(Site $site = null)
    {
        // check for site
        if (!$site) {
            if (Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
            } else {
                $site = false;
            }
        }

        if ($site) {
            $siteKey = 'site_' . $site->getId();
        } else {
            $siteKey = 'default';
        }

        return $siteKey;
    }

    /**
     * @param Site $site
     * @param Document $document
     *
     * @return bool
     */
    public static function isDocumentInSite($site, $document)
    {
        $inSite = true;

        if ($site && $site->getRootDocument() instanceof Document\Page) {
            if (strpos($document->getRealFullPath(), $site->getRootDocument()->getRealFullPath() . '/') !== 0) {
                $inSite = false;
            }
        }

        return $inSite;
    }

    /**
     * @param Document $document
     *
     * @return bool
     */
    public static function isDocumentInCurrentSite($document)
    {
        if (Site::isSiteRequest()) {
            $site = Site::getCurrentSite();
            if ($site instanceof Site) {
                return self::isDocumentInSite($site, $document);
            }
        }

        return true;
    }

    /**
     * @param Document $document
     *
     * @return Site
     */
    public static function getSiteForDocument($document)
    {
        $cacheKey = 'sites_full_list';
        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $sites = \Pimcore\Cache\Runtime::get($cacheKey);
        } else {
            $sites = new Site\Listing();
            $sites->setOrderKey('(SELECT LENGTH(path) FROM documents WHERE documents.id = sites.rootId) DESC', false);
            $sites = $sites->load();
            \Pimcore\Cache\Runtime::set($cacheKey, $sites);
        }

        foreach ($sites as $site) {
            if (strpos($document->getRealFullPath(), $site->getRootPath() . '/') === 0 || $site->getRootDocument()->getId() == $document->getId()) {
                return $site;
            }
        }

        return;
    }

    /**
     * @return array|bool
     */
    public static function isOutputCacheEnabled()
    {
        $container = \Pimcore::getContainer();

        $serviceId = 'pimcore.event_listener.frontend.full_page_cache';
        if (!$container->has($serviceId)) {
            return false;
        }

        $cacheService = $container->get($serviceId);
        if ($cacheService && $cacheService->isEnabled()) {
            return [
                'enabled' => true,
                'lifetime' => $cacheService->getLifetime()
            ];
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function hasWebpSupport()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['thumbnails']['webp_auto_support'];
        if ($config) {
            try {
                $requestHelper = \Pimcore::getContainer()->get('Pimcore\Http\RequestHelper');
                $documentStack = \Pimcore::getContainer()->get('Pimcore\Document\DocumentStack');

                // if a parent is from one of the below types, no WebP images should be rendered
                // e.g. when an email is sent from within a document, the email shouldn't contain WebP images, even when the
                // triggering client (browser) has WebP support, because the email client (e.g. Outlook) might not support WebP
                $hasUnsupportedTypeInStack = $documentStack->findOneBy(function ($doc) {
                    if (in_array($doc->getType(), ['email', 'newsletter', 'printpage', 'printcontainer'])) {
                        return true;
                    }

                    return false;
                });

                if ($hasUnsupportedTypeInStack) {
                    return false;
                }

                if ($requestHelper->hasMasterRequest()) {
                    $contentTypes = $requestHelper->getMasterRequest()->getAcceptableContentTypes();
                    if (in_array('image/webp', $contentTypes)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // nothing to do
            }
        }

        return false;
    }
}