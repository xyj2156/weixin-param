<?php
/**
 * @auth Xyj2156
 * @date 2019年1月15日 10:04:57
 */


namespace Xyj2156\WeixinParam;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinParamExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'weixinparam', __NAMESPACE__.'\Provider'
        );
    }
}