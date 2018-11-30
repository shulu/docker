<?php
namespace Lychee\Bundle\AdminBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class PhoneMessageController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/phonemessage")
 */
class PhoneMessageController extends BaseController{

    public function getTitle()
    {
        return '短信后台';
    }

    /**
     * @Route("/")
     * @Template()
     * @return array
     */
    public function indexAction(Request $request)
    {
        $content = $request->request->get('content');
        $uploadReturn = [];
        if ($content){
            $uploadReturn = $this->sendAction($request);
        }

        //Array ( [0] => Array ( [status] => success [to] => 13570338511 [send_id] => 78764621e7679d335eaaf86148f74f8d [fee] => 2 [sms_credits] => 5903 [transactional_sms_credits] => 73086 ) [1] => Array ( [status] => success [to] => 13430118344 [send_id] => df031df93e65017d664def5643c7f8eb [fee] => 2 [sms_credits] => 5901 [transactional_sms_credits] => 73086 ) [2] => Array ( [status] => success [to] => 18565160432 [send_id] => 678d4d79027d0501a633a872f46bae69 [fee] => 2 [sms_credits] => 5899 [transactional_sms_credits] => 73086 ) )
        return $this->response("短信后台:批量发送短信给用户", array(
            'uploadReturn' => $uploadReturn,
            'content' => $content,
        ));
    }

    /**
     * @param Request $request
     * @return array
     */
    private function sendAction(Request $request){

        $content = $request->request->get('content');
        $file = $request->files->get('file');
//        print_r($file);
        $f = $file;

        $filePath = $f->getPathname();
        $sendMessageList = [];
        $formatErrorList = [];

        $csvfile = fopen($filePath,"r");
        while(!feof($csvfile)) {
            $list = fgetcsv($csvfile);
            $c = count($list);
            if ($c != 2) {
                $formatErrorList[] = json_encode($list);
            }
            $sendMessageList[] = ["to"=>$list[0],"vars"=>["nickname"=>$list[1]]];
        }
        fclose($csvfile);

//        print_r(json_encode($sendMessageList));
//        print_r($formatErrorList);

//        print_r($content);
        $phoneVerifier = $this->get('lychee.module.authentication.phone_verifier');
        $return = $phoneVerifier->sendMultiMessage($sendMessageList , $content);
        return $return;
    }
}