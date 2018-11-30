<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/3/13
 * Time: 下午4:32
 */

namespace Lychee\Bundle\WebsiteBundle\Controller;


use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Module\ExtraMessage\Entity\Plays;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ExtraMessageController extends Controller
{
    /**
     * @Route("/extramessage/{id}", requirements={"id": "\d+"})
     * @Route("/extramessage/{id}", requirements={"id": "\d+"}, host="extramessage.test")
     * @Route("/extramessage/{id}", requirements={"id": "\d+"}, host="192.168.2.39")
     * @Template
     */

    public function extramessageAction($id = 0) {
        $item = null;
        /** @var Plays $item */
        $item = $this->extraMessageService()->getPlayById($id);
        if (!$item) {
            $item = $this->extraMessageService()->findFirstPlay();
        }
        if ($item) {
            if (!$item->type) {
                if ($item->next) {
                    $item = $this->extraMessageService()->getPlayById($item->next);
                }
                else {
                    $item = null;
                }
            }
        }
        $host = 'https://api.ciyo.cn';
        $env = $this->get('kernel')->getEnvironment();
        if ($env == 'dev') {
            $host = 'http://192.168.2.39:8300';
        }
        return array(
            'data' => json_encode($item),
            'host' => $host
        );
    }

    /**
     * @Route("/extramessage/plays")
     * @Template
     */
    public function extramessagePlays() {
        $plays = $this->extraMessageService()->getAllPlays();

        return array(
            'plays' => $plays
        );
    }

    /**
     * @Route("/extramessage/manage/{id}", requirements={"id": "\d+"})
     * @param Request $request
     * @Template
     */
    public function extramessagePlaysManageAction(Request $request, $id = 0) {
        $item = null;
        $currentPlay = null;
        if (!$id) {
            $item = $this->extraMessageService()->findFirstPlay();
        }
        else {
            /** @var Plays $item */
            $item = $this->extraMessageService()->getPlayById($id);
            if (!$item) {
                $item = $this->extraMessageService()->findFirstPlay();
            }
        }

        if ($item) {
            if (!$item->type) {
                $currentPlay = $item;
            }
            else {
                $currentPlay = $this->extraMessageService()->findCurrentPlay($item->id);
            }
        }
        return array(
            'currentPlay' => $currentPlay,
            'data' => json_encode($item),
            'manageId' => $id
        );
    }

    /**
     * @Route("/extramessage/manage/add_play")
     * @param Request $request
     * @Method("POST")
     */
    public function addPlayAction(Request $request) {
        $playName = $request->request->get('play_name');
        $this->extraMessageService()->setANewPlay($playName);
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplays'));
    }

    /**
     * @Route("/extramessage/manage/add_system_message")
     * @param Request $request
     * @Method("POST")
     */
    public function addSystemMessageAction(Request $request) {
        $systemMessage = $request->request->get('system_message');
        $currentId = $request->request->get('current_id');
        $currentNextId = $request->request->get('current_next_id');
        $currentType = $request->request->get('current_type');
        $manageId = $currentId;
        $this->extraMessageService()->addOneSystemMessage($systemMessage);
        $latestItem = $this->extraMessageService()->getLatestPlayItem();
        if ($latestItem) {
            $latestId = $latestItem->id;
            if ($currentType == 3) {
                if (!$currentNextId) {
                    $this->extraMessageService()->setNextOnOption($currentId, $latestId);
                }
                else {
                    $this->extraMessageService()->setNextOnOption($currentId, $latestId);
                    $this->extraMessageService()->setNext($latestId, $currentNextId);
                }
                $optionItem = $this->extraMessageService()->getOptionById($currentId);
                $manageId = $optionItem->optionId;
            }
            else {
                if (!$currentNextId) {

                    $this->extraMessageService()->setNext($currentId, $latestId);
                }
                else {
                    $this->extraMessageService()->setNext($currentId, $latestId);
                    $this->extraMessageService()->setNext($latestId, $currentNextId);
                }
            }
        }
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id' => $manageId)));

    }

    /**
     * @Route("/extramessage/manage/add_somebody_message")
     * @param Request $request
     * @Method("POST")
     */
    public function addSomebodyMessage(Request $request) {
        $somebodyMessage = $request->request->get('somebody_message');
        $currentId = $request->request->get('current_id');
        $currentNextId = $request->request->get('current_next_id');
        $currentType = $request->request->get('current_type');
        $manageId = $currentId;
        $this->extraMessageService()->addOneSomebodyMessage($somebodyMessage);
        $latestItem = $this->extraMessageService()->getLatestPlayItem();
        if ($latestItem) {
            $latestId = $latestItem->id;
            if ($currentType == 3) {
                if (!$currentNextId) {
                    $this->extraMessageService()->setNextOnOption($currentId, $latestId);
                }
                else {
                    $this->extraMessageService()->setNextOnOption($currentId, $latestId);
                    $this->extraMessageService()->setNext($latestId, $currentNextId);
                }
                $optionItem = $this->extraMessageService()->getOptionById($currentId);
                $manageId = $optionItem->optionId;
            } 
            else {
                if (!$currentNextId) {

                    $this->extraMessageService()->setNext($currentId, $latestId);
                }
                else {
                    $this->extraMessageService()->setNext($currentId, $latestId);
                    $this->extraMessageService()->setNext($latestId, $currentNextId);
                }
            }
        }
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id' => $manageId)));
    }
    /**
     * @Route("/extramessage/manage/add_options")
     * @param Request $request
     * @Method("POST")
     */
    public function addoptionsAction(Request $request) {
        $options = $request->request->get('options');
        $currentId = $request->request->get('current_id');
        $currentNextId = $request->request->get('current_next_id');
        $currentType = $request->request->get('current_type');
        $manageId = $currentId;
        $this->extraMessageService()->addOneOptionToPlay();
        $latestItem = $this->extraMessageService()->getLatestPlayItem();
        if ($latestItem) {
            $latestId = $latestItem->id;
            if ($currentType == 3) {
                $this->extraMessageService()->setNextOnOption($currentId, $latestId);
                $this->extraMessageService()->addOptionsToOptions($latestId, $options);
                $optionItem = $this->extraMessageService()->getOptionById($currentId);
                $manageId = $optionItem->optionId;
            }
            else {
                $this->extraMessageService()->setNext($currentId, $latestId);
                $this->extraMessageService()->addOptionsToOptions($latestId, $options);
            }
        }

        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id'=>$manageId)));
    }

    /**
     * @Route("/extramessage/manage/get_play/{id}", requirements={"id": "\d+"})
     *
     * @Method("GET")
     */
    public function getPlay($id=0) {
        $item = $this->extraMessageService()->getPlayById($id);

        return new JsonResponse($item);
    }

    /**
     * @Route("/extramessage/manage/get_options/{id}", requirements={"id": "\d+"})
     *
     * @Method("GET")
     */
    public function getOptions($id=0) {
        $options = $this->extraMessageService()->getoptionsByOptionId($id);

        return new JsonResponse($options);
    }

    /**
     * @Route("/extramessage/manage/edit_option")
     * @param Request $request
     * @Method("POST")
     */
    public function editOptionAction(Request $request) {
        $currentId = $request->request->get('current_id');
        $optionsNext = $request->request->get('option_next');
        $optionStr = $request->request->get('option_str');
        $manageId = 0;
        $optionItem = $this->extraMessageService()->getOptionById($currentId);
        if ($optionItem) {
            $manageId = $optionItem->optionId;
        }
        $this->extraMessageService()->editOption($currentId, $optionsNext, $optionStr);
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id'=>$manageId)));
    }
    /**
     * @Route("/extramessage/manage/edit_message")
     * @param Request $request
     * @Method("POST")
     */
    public function editMessageAction(Request $request) {
        $currentId = $request->request->get('current_id');
        $subtitleline = $request->request->get('subtitleline');
        $messageNextId = $request->request->get('message_next_id');
        $manageId = $currentId;
        $this->extraMessageService()->editMessage($currentId, $subtitleline, $messageNextId);
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id'=>$manageId)));
    }

    /**
     * @Route("/extramessage/manage/remove_play_head")
     * @param Request $request
     * @Method("POST")
     */
    public function removeplayhead(Request $request) {
        $currentId = $request->request->get('current_id');
        $this->extraMessageService()->removePlayById($currentId);

        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplays'));
    }

    /**
     * @Route("/extramessage/manage/remove_message")
     * @param Request $request
     * @Method("POST")
     */
    public function removeMessage(Request $request) {
        $currentId = $request->request->get('current_id');
        $manageId = 0;
        $deleteItem = $this->extraMessageService()->getPlayById($currentId);
        if ($deleteItem) {
            $prevItem = $this->extraMessageService()->getPlayByNextId($deleteItem->id);
            if ($prevItem) {
                $manageId = $prevItem->id;
                $nextId = null;
                if ($deleteItem->next) {
                    $nextId = $deleteItem->next;
                }
                $this->extraMessageService()->setNext($prevItem->id, $nextId);
            }
            else {
                $optionItems = $this->extraMessageService()->getOptionsByNextId($deleteItem->id);
                if ($optionItems) {
                    foreach ($optionItems as $item) {
                        $nextId = null;
                        $this->extraMessageService()->setNextOnOption($item->id, $nextId);
                    }
                    $manageId = $optionItems[0]->optionId;
                }
            }
            $this->extraMessageService()->removePlayById($deleteItem->id);
        }
        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id'=>$manageId)));
    }

    /**
     * @Route("/extramessage/manage/remove_options")
     * @param Request $request
     * @Method("POST")
     */
    public function removeOptions(Request $request) {
        $optionId = $request->request->get('option_id');
        $manageId = 0;
        $prevItem = $this->extraMessageService()->getPlayByNextId($optionId);
        if ($prevItem) {
            $manageId = $prevItem->id;
            $next = null;
            $this->extraMessageService()->setNext($prevItem->id, $next);
        }
        $this->extraMessageService()->removeOptions($optionId);

        return $this->redirect($this->generateUrl('lychee_website_extramessage_extramessageplaysmanage', array('id'=>$manageId)));
    }
}