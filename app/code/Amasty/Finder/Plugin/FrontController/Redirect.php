<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */


namespace Amasty\Finder\Plugin\FrontController;

class Redirect
{
    /**
     * @var \Amasty\Finder\Model\Session
     */
    protected $session;

    /**
     * @var \Amasty\Finder\Helper\Url
     */
    protected $urlHelper;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    public function __construct(
        \Amasty\Finder\Model\Session $session,
        \Amasty\Finder\Helper\Url $urlHelper,
        \Magento\Framework\App\Response\Http $response
    ) {
        $this->session = $session;
        $this->urlHelper = $urlHelper;
        $this->response = $response;
    }

    public function aroundDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $savedFinders = $this->session->getAllFindersData();
        /** @var \Magento\Framework\App\Request\Http $request */
        if (is_array($savedFinders)) {
            $baseUrl = rtrim($request->getDistroBaseUrl(), '/');
            $currentUrlWithoutGet = $baseUrl . $request->getRequestString();
            foreach ($savedFinders as $finder) {
                if (in_array($currentUrlWithoutGet, $finder['apply_url']) &&
                    strpos($request->getRequestUri(), $finder['url_param']) === false &&
                    !$this->urlHelper->hasFinderParamInUri($request->getRequestUri())
                ) {
                    $redirectUrl = $this->urlHelper->getUrlWithFinderParam(
                        $request->getOriginalPathInfo(),
                        $finder['url_param']
                    );
                    $redirectUrl = $baseUrl . $redirectUrl;

                    return $this->response
                        ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                        ->setRedirect($redirectUrl);
                }
            }
        }

        return $proceed($request);

    }
}