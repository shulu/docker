<?php
namespace Lychee\Bundle\WebsiteBundle\Controller;

use Lychee\Bundle\CoreBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller {
    /**
     * @Route("/download/redirect")
     * @Route("/download/app/{platform}", name="download_app")
     */
    public function redirectDownloadAction(Request $request, $platform = null) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');

        $iphoneLink = 'https://itunes.apple.com/cn/app/ci-yuan-er-ci-yuan-zhong-tu/id896119899?l=en&mt=8';
        $wechatLink = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.infothinker.erciyuan';

        $downloadUrl = null;
        if ($platform !== null) {
            if ($platform === 'iphone') {
                $downloadUrl = $iphoneLink;
            } else if ($platform === 'wechat') {
                $downloadUrl = $wechatLink;
            } else if ($platform === 'android') {
                $downloadUrl = $this->getAndroidLink($request);
            }
        } else {
            if (stripos($userAgent, 'MicroMessenger') !== false) {
                $downloadUrl = $wechatLink;
            } else if (stripos($userAgent, 'Android') !== false) {
                $downloadUrl = $this->getAndroidLink($request);
            } else if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPod') !== false) {
                $downloadUrl = $iphoneLink;
            } else {
                $downloadUrl = $this->generateUrl('homepage');
            }
        }
        if ($downloadUrl == null) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($downloadUrl);
    }

    private function getAndroidLink(Request $request, $packageName = null) {
        $packages = array(
            'mianxiaoya' => 'http://qn.ciyocon.com/android/mianxiaoya-1_6_1.apk',
            'kennys' => 'http://qn.ciyocon.com/android/kennys-1_6_1.apk',
            'shouxiaotu' => 'http://qn.ciyocon.com/android/shouxiaotu.apk',
            'zeye' => 'http://qn.ciyocon.com/android/ciyuan/zeye.apk',
            'meizi' => 'http://qn.ciyocon.com/android/meizi.apk',
            'ziyuan' => 'http://qn.ciyocon.com/android/ziyuan.apk',
            'xiaozi' => 'http://qn.ciyocon.com/android/xiaozi.apk',
            'yuge' => 'http://qn.ciyocon.com/android/yuge.apk',
            'hajiang' => 'http://qn.ciyocon.com/android/hajiang.apk',
            'mingxi' => 'http://qn.ciyocon.com/android/mingxi.apk',
            'xiaonai' => 'http://qn.ciyocon.com/android/xiaonai.apk',
            '8' => 'http://qn.ciyocon.com/android/8.apk',
            '13' => 'http://qn.ciyocon.com/android/13.apk',
            'dujia' => 'http://qn.ciyocon.com/android/dujia.apk',
            'yueliao' => 'http://qn.ciyocon.com/android/yueliao.apk',
            'biaoqing' => 'http://qn.ciyocon.com/android/biaoqing.apk',
            'fanzu' => 'http://qn.ciyocon.com/android/fanzu.apk',
            'source' => 'http://qn.ciyocon.com/android/source.apk',
            'shenqi' => 'http://qn.ciyocon.com/android/shenqi.apk',
            'acg1' => 'http://qn.ciyocon.com/android/acg1.apk',
            'acg2' => 'http://qn.ciyocon.com/android/acg2.apk',
            'fuli1' => 'http://qn.ciyocon.com/android/fuli1.apk',
            'fuli2' => 'http://qn.ciyocon.com/android/fuli2.apk',
            'fuli3' => 'http://qn.ciyocon.com/android/fuli3.apk',
            'fuli4' => 'http://qn.ciyocon.com/android/fuli4.apk',
            'fuli5' => 'http://qn.ciyocon.com/android/fuli5.apk',
            'fuli6' => 'http://qn.ciyocon.com/android/fuli6.apk',
            'fuli7' => 'http://qn.ciyocon.com/android/fuli7.apk',
            'fuli8' => 'http://qn.ciyocon.com/android/fuli8.apk',
            'fuli9' => 'http://qn.ciyocon.com/android/fuli9.apk',
            'fuli10' => 'http://qn.ciyocon.com/android/fuli10.apk',
            'fuli11' => 'http://qn.ciyocon.com/android/fuli11.apk',
            'fuli12' => 'http://qn.ciyocon.com/android/fuli12.apk',
            'fuli13' => 'http://qn.ciyocon.com/android/fuli13.apk',
            'fuli14' => 'http://qn.ciyocon.com/android/fuli14.apk',
            'fuli15' => 'http://qn.ciyocon.com/android/fuli15.apk',
            'momo' => 'http://qn.ciyocon.com/android/momo.apk',
            'firefly' => 'http://qn.ciyocon.com/android/firefly.apk',
            'momo1' => 'http://qn.ciyocon.com/android/momo1.apk',
            'momo2' => 'http://qn.ciyocon.com/android/momo2.apk',
            'momo3' => 'http://qn.ciyocon.com/android/momo3.apk',
            'momo4' => 'http://qn.ciyocon.com/android/momo4.apk',
            'momo5' => 'http://qn.ciyocon.com/android/momo5.apk',
            //异次元通讯
            'shaonv' => 'http://qn.ciyocon.com/unknownmessage/shaonv.apk',
            'jk' => 'http://qn.ciyocon.com/unknownmessage/jk.apk',
            'miko' => 'http://qn.ciyocon.com/unknownmessage/miko.apk',
            'miko2' => 'http://qn.ciyocon.com/unknownmessage/miko2.apk',
        );
        if ($packageName == null) {
            $packageName = $request->query->get('n');
        }
        if ($packageName == null) {
            $packageName = $request->query->get('android_package');
        }
        if ($packageName && isset($packages[$packageName])) {
            return $packages[$packageName];
        } else {
            return $this->contentManagement()->androidDownloadLink();
        }
    }

    private function getRouteName($package) {
        if (in_array($package, array('shaonv', 'jk', 'miko'))) {
            return 'unknownmessage_download_app';
        } else {
            return 'download_app';
        }
    }

    /**
     * @Route("/download/channel/{title}")
     * @Route("/download/channels/{name}/{titleKey}/{pageKey}", defaults={"titleKey":null, "pageKey":null})
     */
    public function channelDownload(Request $request,
                                    $title = null,
                                    $name = null,
                                    $titleKey = null,
                                    $pageKey = null) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $titles = array(
            'cy1' => '次元社 — 最高能的二次元应用，大家都在玩！',
            'cy2' => '妹汁最多的二次元app，请轻戳',
            'cy3' => '约聊神器，快速勾搭小伙伴',
            'cy4' => '众多独家绅士资源，只在这里',
            'cy5' => '宝宝最爱的番组资源app，表番里番禁番收费番样样俱全',
            'cy6' => '众多独家资源，全覆盖全免费！',
            'cy7' => '斗图必备！最新最全的表情包神器！',
            'cy8' => '宅男资源搜索神器',
            'cy9' => '资源搜索神器',
            'cy10' => '二次元网红生成器',

            'nm1' => '异次元通讯',
            'nm2' => '微博最热门推理游戏',
            'nm3' => '拯救JK少女',
        );

        $pages = array(
            'p1' => 'LycheeWebsiteBundle:Download:channel_download.html.twig',
            'p2' => 'LycheeWebsiteBundle:Download:channel_download2.html.twig',
            'p3' => 'LycheeWebsiteBundle:Download:channel_download3.html.twig',
            'p4' => 'LycheeWebsiteBundle:Download:channel_download4.html.twig',

            'n1' => 'LycheeWebsiteBundle:Download:channel_download5.html.twig',
        );

        if ($titleKey == null) {
            $titleKey = $request->query->get('t');
        }
        if ($titleKey && isset($titles[$titleKey])) {
            $title = $titles[$titleKey];
        } else {
            $title = '次元社 — 最高能的二次元应用，大家都在玩！';
        }

        if ($pageKey == null) {
            $pageKey = $request->query->get('p');
        }
        if ($pageKey && isset($pages[$pageKey])) {
            $page = $pages[$pageKey];
        } else {
            $page = $pages['p1'];
        }

        $routeName = $this->getRouteName($name);

        $links = array(
            'android_link' => $this->getAndroidLink($request, $name),
            'wechat_link' => $this->generateUrl($routeName, array('platform' => 'wechat'),
                UrlGeneratorInterface::ABSOLUTE_URL),
            'iphone_link' => $this->generateUrl($routeName, array('platform' => 'iphone'),
                UrlGeneratorInterface::ABSOLUTE_URL)
        );
        return $this->render($page, array_merge($links, array('title' => $title, 'package' => $name)));
    }
}
