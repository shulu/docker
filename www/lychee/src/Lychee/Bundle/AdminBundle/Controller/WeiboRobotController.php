<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 11/25/15
 * Time: 7:16 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class WeiboRobotController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/weibo_robot")
 */
class WeiboRobotController extends BaseController {

    public function getTitle() {
        return '微博机器人';
    }

    /**
     * @Route("/")
     * @Template
     * @return array
     */
    public function indexAction() {
        $auth = new \SaeTOAuthV2($this->getParameter('sinaweibo_client_key'), $this->getParameter('sinaweibo_client_secret'));
        $conn = $this->getDoctrine()->getConnection('weibo_robot');
        $stmt = $conn->prepare('SELECT * FROM authorization LIMIT 1');
        $stmt->execute();
        $result = $stmt->fetch();

        return $this->response('账号设置', [
            'result' => $result,
            'authorizeUrl' => $auth->getAuthorizeURL($this->getParameter('weibo_callback_url')),
        ]);
    }

//    public function statusAction() {
//
//    }

    /**
     * @Route("/conversation")
     * @Template
     * @return array
     */
    public function conversationAction() {
        $conn = $this->getDoctrine()->getConnection('weibo_robot');
        $stmt = $conn->prepare('SELECT * FROM conversation ORDER BY `order`');
        $stmt->execute();
        $conversations = $stmt->fetchAll();

        return $this->response('对话管理', [
            'conversations' => $conversations
        ]);
    }

    /**
     * @Route("/conversation/set")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function setConversationAction(Request $request) {
        $conversationId = $request->request->get('conversation_id');
        $order = $request->request->get('order');
        $content = $request->request->get('content');
        $conn = $this->getDoctrine()->getConnection('weibo_robot');
        if (!$conversationId) {
            $stmt = $conn->prepare('INSERT INTO conversation(content, `order`) VALUE(:content, :order)');
            $stmt->bindValue(':content', $content);
            $stmt->bindValue(':order', $order);
        } else {
            $stmt = $conn->prepare('INSERT INTO conversation(id, content, `order`) VALUE(:id, :content, :order)
                                    ON DUPLICATE KEY UPDATE content=:content, `order`=:order');
            $stmt->bindValue(':id', $conversationId);
            $stmt->bindValue(':content', $content);
            $stmt->bindValue(':order', $order);
        }
        $stmt->execute();

        return $this->redirect($this->generateUrl('lychee_admin_weiborobot_conversation'));
    }

    /**
     * @Route("/conversation/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeConversationAction(Request $request) {
        $id = $request->request->get('id');
        if ($id) {
            $conn = $this->getDoctrine()->getConnection('weibo_robot');
            $stmt = $conn->prepare('DELETE FROM conversation WHERE id=:id');
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $stmt = $conn->prepare('SELECT * FROM conversation ORDER BY `order`');
            $stmt->execute();
            $conversions = $stmt->fetchAll();
            $i = 1;
            $stmt = $conn->prepare('UPDATE conversation SET `order`=:order WHERE id=:id');
            foreach ($conversions as $c) {
                $stmt->bindValue(':order', $i);
                $stmt->bindValue(':id', $c['id']);
                $stmt->execute();
                $i += 1;
            }
        }

        return $this->redirect($this->generateUrl('lychee_admin_weiborobot_conversation'));
    }

}