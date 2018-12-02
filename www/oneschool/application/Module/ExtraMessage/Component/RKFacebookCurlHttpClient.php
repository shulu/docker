<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2018/2/5
 * Time: 下午5:54
 */

namespace Lychee\Module\ExtraMessage\Component;


use Facebook\HttpClients\FacebookCurlHttpClient;

class RKFacebookCurlHttpClient extends FacebookCurlHttpClient {

	public $proxy = [];

	public function openConnection( $url, $method, $body, array $headers, $timeOut ) {
		parent::openConnection( $url, $method, $body, $headers, $timeOut );

		if (!empty($this->proxy)) {
			$proxy_host = $this->proxy['host'];
			$proxy_port = $this->proxy['port'];
//			$this->facebookCurl->setopt(CURLOPT_PROXY, 'tcp://' . $proxy_host . ':' . $proxy_port);
			$this->facebookCurl->setopt(CURLOPT_PROXY, $proxy_host);
			$this->facebookCurl->setopt(CURLOPT_PROXYPORT, $proxy_port);
		}
	}

}