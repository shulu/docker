<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\IMGroupSynthesizerBuilder;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\IMError;
use Lychee\Bundle\ApiBundle\Error\TopicError;
use Lychee\Module\IM\Exceptions\GroupNonExistException;
use Lychee\Module\IM\Exceptions\GroupPermissionDeniedException;
use Lychee\Module\IM\Exceptions\JoinTooMuchGroupInTopic;
use Lychee\Module\IM\Exceptions\MemberExceedLimitException;
use Lychee\Module\IM\Exceptions\YouHavaBeenKickedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\IM\GroupService;
use Lychee\Module\IM\Group;
use Symfony\Component\HttpFoundation\Response;

class IMController extends Controller {

    /**
     * @return GroupService
     */
    private function groupService() {
        return $this->container->get('lychee.module.im.group');
    }

    /**
     * @param Group $group
     * @return Response
     */
    private function buildGroupResponse($group) {
        $userSynthesizer = $this->getSynthesizerBuilder()->buildSimpleUserSynthesizer($group->memberIds);

        $data = array(
            'id' => $group->id,
            'name' => $group->name,
            'icon' => $group->icon,
            'description' => $group->description,
            'members' => $userSynthesizer->synthesizeAll()
        );
        if ($group->createTime) {
            $data['create_time'] = $group->createTime->getTimestamp();
        }
        if ($group->postId) {
            $postSynthesizer = $this->getSynthesizerBuilder()
                ->buildListPostSynthesizer(array($group->postId), 0);
            $data['post'] = $postSynthesizer->synthesizeOne($group->postId);
        }
        if ($group->topicId) {
            $topicSynthesizer = $this->getSynthesizerBuilder()
                ->buildBasicTopicSynthesizer(array($group->topicId), 0);
            $data['topic'] = $topicSynthesizer->synthesizeOne($group->topicId);
        }
        if ($group->noDisturb !== null) {
            $data['no_disturb'] = $group->noDisturb;
        }
        return $this->dataResponse($data);
    }

    /**
     * @Route("/im/group/create")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="name", "dataType"="string", "required"=false, "description"="名称，最多20字符"},
     *     {"name"="icon", "dataType"="string", "required"=false, "description"="icon url"},
     *     {"name"="description", "dataType"="string", "required"=false, "description"="描述，最多400字符"}
     *   }
     * )
     */
    public function create(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->request, 'topic_id');
        $name = $request->request->get('name');
        $icon = $request->request->get('icon');
        $description = $request->request->get('description');
        if ($name && mb_strlen($name, 'utf8') > 20) {
            return $this->errorsResponse(CommonError::ParameterInvalid('name', $name));
        }
        if ($description && mb_strlen($description, 'utf8') > 400) {
            return $this->errorsResponse(CommonError::ParameterInvalid('description', $name));
        }
        if ($icon && strlen($icon) > 2083) {
            return $this->errorsResponse(CommonError::ParameterInvalid('icon', $name));
        }

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }

        try {
            $group = $this->groupService()->create($account->id, $name, $icon, $description, $topicId, null, array());
        } catch (JoinTooMuchGroupInTopic $e) {
            return $this->errorsResponse(IMError::JoinTooMuchGroupInTopic());
        }

        return $this->buildGroupResponse($group);
    }

    /**
     * @Route("/im/group/get")
     * @Method("GET")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function getGroup(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->query, 'group_id');
        try {
            $group = $this->groupService()->get($gid, $account->id);
        } catch (GroupNonExistException $e) {
            return $this->errorsResponse(IMError::GroupNonExist());
        }

        return $this->buildGroupResponse($group);
    }

    /**
     * @Route("/im/group/list_by_topic")
     * @Method("GET")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *          "description"="每次返回的数据，默认20，最多不超过50"},
     *   }
     * )
     */
    public function listByTopic(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'topic_id');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topic = $this->topic()->fetchOne($topicId);
        if ($topic == null || $topic->deleted) {
            return $this->errorsResponse(TopicError::TopicNotExist($topicId));
        }
        if ($topic->private && $this->topicFollowing()->isFollowing($account->id, $topicId) == false) {
            return $this->errorsResponse(TopicError::RequireFollow());
        }
        
        $groups = $this->groupService()->getGroupsByTopic($topicId, $cursor, $count, $nextCursor);
        $synthesizerBuilder = new IMGroupSynthesizerBuilder();
        $synthesizerBuilder->setContainer($this->container());
        $synthesizer = $synthesizerBuilder->build($groups, $account->id);
        return $this->arrayResponse('groups', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/im/group/list_by_me_in_topic")
     * @Method("GET")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic_id", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *          "description"="每次返回的数据，默认20，最多不超过50"},
     *   }
     * )
     */
    public function listByMeInTopic(Request $request) {
        $account = $this->requireAuth($request);
        $topicId = $this->requireId($request->query, 'topic_id');
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $groups = $this->groupService()->getGroupsByUserInTopic($account->id, $topicId, $cursor, $count, $nextCursor);
        $synthesizerBuilder = new IMGroupSynthesizerBuilder();
        $synthesizerBuilder->setContainer($this->container());
        $synthesizer = $synthesizerBuilder->build($groups, $account->id);
        return $this->arrayResponse('groups', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/im/group/join")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function join(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');
        
        $group = $this->groupService()->get($gid);
        if ($group == null) {
            return $this->errorsResponse(IMError::GroupNonExist());
        }

        $force = false;
        if ($group->topicId) {
            if ($this->topicFollowing()->isFollowing($account->id, $group->topicId) == false) {
                return $this->errorsResponse(TopicError::RequireFollow());
            }
            $topic = $this->topic()->fetchOne($group->topicId);
            if ($topic && $topic->managerId == $account->id) {
                $force = true;
            }

        }

        try {
            $this->groupService()->join($gid, $account->id, $force);
        } catch (GroupNonExistException $e) {
            return $this->errorsResponse(IMError::GroupNonExist());
        } catch (MemberExceedLimitException $e) {
            return $this->errorsResponse(IMError::MemberExceedLimit());
        } catch (YouHavaBeenKickedException $e) {
            return $this->errorsResponse(IMError::YouHaveBeenKicked());
        } catch (JoinTooMuchGroupInTopic $e) {
            return $this->errorsResponse(IMError::JoinTooMuchGroupInTopic());
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/im/group/leave")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function leave(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');

        try {
            $this->groupService()->leave($gid, $account->id);
        } catch (GroupNonExistException $e) {
            return $this->errorsResponse(IMError::GroupNonExist());
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/im/group/kick")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true},
     *     {"name"="member_id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function kick(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');
        $mid = $this->requireId($request->request, 'member_id');

        try {
            $this->groupService()->kickout($gid, $account->id, $mid);
        } catch (GroupNonExistException $e) {
            return $this->errorsResponse(IMError::GroupNonExist());
        } catch (GroupPermissionDeniedException $e) {
            return $this->errorsResponse(IMError::PermissionDenied());
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/im/group/disband")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function disband(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');

        $group = $this->groupService()->get($gid);
        if ($group == null) {
            return $this->errorsResponse(IMError::GroupNonExist());
        }

        if ($group->topicId == null) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }
        $topic = $this->topic()->fetchOne($group->topicId);
        if ($topic->managerId != $account->id) {
            return $this->errorsResponse(TopicError::YouAreNotManager());
        }

        try {
            $this->groupService()->disband($gid, $account->id);
        } catch (GroupNonExistException $e) {
            return $this->errorsResponse(IMError::GroupNonExist());
        } catch (GroupPermissionDeniedException $e) {
            return $this->errorsResponse(IMError::PermissionDenied());
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/im/group/setup_no_disturb")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function setupNoPush(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');

        $this->groupService()->setNoDisturb($gid, $account->id);

        return $this->sucessResponse();
    }

    /**
     * @Route("/im/group/unset_no_disturb")
     * @Method("POST")
     * @ApiDoc(
     *   section="im",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="group_id", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function cancelNoPush(Request $request) {
        $account = $this->requireAuth($request);
        $gid = $this->requireId($request->request, 'group_id');

        $this->groupService()->unsetNoDisturb($gid, $account->id);

        return $this->sucessResponse();
    }

}