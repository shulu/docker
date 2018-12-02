<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorInfoFileFinder;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorInfoFile;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\Loader\XmlLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Bundle\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @Route("/dev")
 */
class DevelopController extends Controller {

    /**
     * @Route("/errors")
     * @Template()
     */
    public function errorCodeAction() {
        $sections = XmlLoader::create()->load();

        return array('sections' => $sections);
    }

    /**
     * @Route("/upload_image")
     * @Template()
     */
    public function uploadImageAction(Request $request) {
        $accessKey = '41vFZF9HylpQ_vZSwJ7zoV83mqS8ExAi3ZmkQeRO';
        $secretKey = 'gyP2jUJf5AJha5I5VFKBDYU4-rkPCUjArHr76vgb';
        $bucket = 'lychee';

        if ($request->isMethod('POST')) {
            /** @var UploadedFile $image */
            $image = $request->files->get('image');
            \Qiniu_SetKeys($accessKey, $secretKey);
            $putPolicy = new \Qiniu_RS_PutPolicy($bucket);
            $putPolicy->MimeLimit = 'image/*';
            $upToken = $putPolicy->Token(null);
            $putExtra = new \Qiniu_PutExtra();
            $putExtra->Crc32 = 1;
            list($ret, $err) = Qiniu_PutFile($upToken, null, $image->getPathname(), $putExtra);
            if ($err !== null) {
                return array('error' => $err['Err']);
            } else {
                $url = 'http://'.$bucket.'.qiniudn.com/'.$ret['key'];
                return array('url' => $url);
            }
        } else {
            return array();
        }
    }

}