<?php
/**
 * @auth  Xyj2156 || Jief
 * @email 506907958@qq.com
 * @date  2019年1月15日 10:08:08
 */

namespace Xyj2156\WeixinParam;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\Exception\InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;


class Provider extends AbstractProvider
{

    protected $stateless = true;

    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'WEIXINPARAM';

    /**
     * @var string
     */
    protected $openId;

    /**
     * set Open Id.
     *
     * @param string $openId
     */
    public function setOpenId ($openId)
    {
        $this -> openId = $openId;
    }

    /**
     * {@inheritdoc}.
     * 微信小程序登陆 不需要跳转，小程序中可以直接获取 code
     */
    protected function getAuthUrl ($state)
    {
        return null;
    }

    /**
     * 小程序不需要通过跳转 获取 code
     * {@inheritdoc}.
     */
    protected function buildAuthUrlFromBase ($url, $state)
    {
        return '';
    }

    /**
     * {@inheritdoc}.
     * 小程序登陆 不需要和获取code 相关的代码
     */
    protected function getCodeFields ($state = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenUrl ()
    {
        return 'https://api.weixin.qq.com/sns/jscode2session';
    }

    /**
     * {@inheritdoc}.
     */
    protected function getUserByToken ($token)
    {
        $params = $this -> request -> validate([
            'encryptedData' => 'required',
            'iv'            => 'required',
        ]);
//        解密数据
        $data = $this -> decryptData($token, $params['iv'], $params['encryptedData']);
        if (!$data) {
            throw new InvalidStateException('数据解密失败');
        }

        return $data;
    }

    /**
     * {@inheritdoc}.
     */
    protected function mapUserToObject (array $user)
    {
        return ( new User() ) -> setRaw($user) -> map([
            'id'       => Arr ::get($user, 'openId'),
            'unionid'  => Arr ::get($user, 'unionId'),
            'nickname' => $user['nickName'],
            'avatar'   => $user['avatarUrl'],
            'name'     => null,
            'email'    => null,
        ]);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getTokenFields ($code)
    {
        return [
            'appid'      => $this -> clientId,
            'secret'     => $this -> clientSecret,
            'js_code'    => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * 通过小程序获取的 code 从微信服务器 获取 session_key
     */
    public function getAccessTokenResponse ($code)
    {
        $response                        = $this -> getHttpClient() -> get($this -> getTokenUrl(), [
            'query' => $this -> getTokenFields($code),
        ]);
        $this -> credentialsResponseBody = json_decode($response -> getBody(), true);
        $this -> openId                  = $this -> credentialsResponseBody['openid'];
        //return $this->parseAccessToken($response->getBody());
        return $this -> credentialsResponseBody;
    }

    /**
     * 暂时不需要使用这个
     *
     * @Author   Xyj2156
     * @DateTime 2019-01-15T10:26:01+0800
     * @return array
     */
    public static function additionalConfigKeys ()
    {
        return [];
    }

    /**
     * 解密小程序数据
     *
     * @param $session_key
     * @param $iv
     * @param $encryptedData
     *
     * @return bool|mixed
     * @author Jief
     * @date   2019/1/11
     * @time   19:12
     */
    protected function decryptData ($session_key, $iv, $encryptedData)
    {
        $aesKey    = base64_decode(trim($session_key));
        $aesIV     = base64_decode(trim($iv));
        $aesCipher = base64_decode(trim($encryptedData));
        $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        return json_decode($result, true);
    }

    /**
     * 获取 refresh_token 的值
     *
     * @param string $body
     *
     * @author Jief
     * @date   2019/1/14
     * @time   15:18
     *
     * @return string
     */
    protected function parseRefreshToken ($body)
    {
        return '';
    }

    /**
     * 获取 access_token 的过期时间
     *
     * @param string $body
     *
     * @author Jief
     * @date   2019/1/14
     * @time   15:18
     *
     * @return int|string
     */
    protected function parseExpiresIn ($body)
    {
        return 0;
    }

    /**
     * 重写 跳转方法，小程序本身不需要跳转获取 code
     * @author Jief
     * @date   2019/1/15
     * @time   11:10
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function redirect ()
    {
        $msg = '小程序不需要跳转获取code';
        if($this -> request -> ajax()){
            $msg = json_encode([
                'status' => 1000001,
                'message' => $msg,
            ]);
        }
        throw new InvalidArgumentException($msg);
    }
}