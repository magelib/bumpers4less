<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */


namespace Amasty\Finder\Plugin\Mirasvit;

class PagingPlugin
{
    /**
     * @var \Amasty\Finder\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(
        \Amasty\Finder\Model\Session $session,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->session = $session;
        $this->request = $request;
    }

    /**
     * \Mirasvit\Seo\Model\Paging::createLinks loads collection before a finder applied
     * so it should be restricted on the pages which have an active finder.
     *
     * @param \Mirasvit\Seo\Model\Paging $subject
     * @param \Closure $proceed
     * @return \Mirasvit\Seo\Model\Paging
     */
    public function aroundCreateLinks(
        \Mirasvit\Seo\Model\Paging $subject,
        \Closure $proceed
    ) {
        $savedFinders = $this->session->getAllFindersData();
        $allowProceed = true;

        if (is_array($savedFinders)) {
            $baseUrl = rtrim($this->request->getDistroBaseUrl(), '/');
            $currentUrlWithoutGet = $baseUrl . $this->request->getRequestString();
            foreach($savedFinders as $finder) {
                if(in_array($currentUrlWithoutGet, $finder['apply_url'])) {
                    $allowProceed = false;
                    break;
                }
            }
        }

        if($allowProceed) {
            return $proceed();
        }
        return $subject;
    }
}
