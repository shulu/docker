<?php
namespace Lychee\Module\IM;

use Httpful\Mime;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Httpful\Request;
use Httpful\Response;

class GroupService {

    private $imHost;
    private $imPort;
    private $apiToken;

    /**
     * @param string $imHost
     * @param int $imPort
     * @param string $apiToken
     */
    public function __construct($imHost, $imPort, $apiToken) {
        $this->imHost = $imHost;
        $this->imPort = $imPort;
        $this->apiToken = $apiToken;
    }

    private function uri($path) {
        return 'http://'.$this->imHost.':'.$this->imPort.$path;
    }

    private function buildGroup($resBody) {
        if (isset($resBody->id)) {
            $group = new Group();
            $group->id = $resBody->id;
            $group->name = isset($resBody->name) ? $resBody->name : null;
            $group->icon = isset($resBody->icon) ? $resBody->icon : null;
            $group->description = isset($resBody->description) ? $resBody->description : null;
            $group->topicId = isset($resBody->topicId) ? $resBody->topicId : null;
            $group->postId = isset($resBody->postId) ? $resBody->postId : null;
            $group->createTime = isset($resBody->createTime) ? new \DateTime($resBody->createTime) : null;
            if (isset($resBody->members)) {
                $group->memberIds = $resBody->members;
                $group->memberCount = count($resBody->members);
            } else {
                $group->memberCount = isset($resBody->memberCount) ? $resBody->memberCount : 0;
            }
            if (isset($resBody->noDisturb)) {
                $group->noDisturb = $resBody->noDisturb;
            }

            return $group;
        } else {
            return null;
        }
    }

    /**
     * @param int $creatorId
     * @param string $name
     * @param string $icon
     * @param string $description
     * @param int|null $topicId
     * @param int|null $postId
     * @param int[]|null $memberIds
     *
     * @return Group|null
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws Exceptions\JoinTooMuchGroupInTopic
     */
    public function create($creatorId, $name, $icon, $description, $topicId = null, $postId = null, $memberIds = null) {
        $param = array('creator_id' => $creatorId, 'name' => $name, 'icon' => $icon, 'description' => $description);
        if (!empty($memberIds)) {
            $param['member_ids'] = implode(',', $memberIds);
        }
        if ($topicId > 0) {
            $param['topic_id'] = $topicId;
        }
        if ($postId > 0) {
            $param['post_id'] = $postId;
        }

        /** @var Response $response */
        $response = Request::put($this->uri('/api/groups'),
            $param,
            Mime::FORM)
            ->timeoutIn(5)
            ->expects(Mime::JSON)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            return $this->buildGroup($response->body);
        }
        $this->handleError($response);
        return null;
    }

    /**
     * @param int $gid
     * @param int $mid
     *
     * @return Group|null
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function get($gid, $mid = null) {
        /** @var Response $response */
        $response = Request::get($this->uri('/api/groups/'.$gid.($mid ? '?mid='.$mid : '')))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            return $this->buildGroup($response->body);
        } else {
            return null;
        }
    }

    /**
     * 批量返回group数据
     *
     * @param int[] $gids
     *
     * @return Group|null
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function multiGet($gids) {
        /** @var Response $response */
        $response = Request::get($this->uri('/api/groups?gids='.implode(',', $gids)))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            $groups = array();
            foreach ($response->body as $groupInfo) {
                $group = $this->buildGroup($groupInfo);
                if ($group) {
                    $groups[$group->id] = $group;
                }
            }
            return $groups;
        } else {
            return array();
        }
    }

    /**
     * @param int $topicId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return Group[]|null
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getGroupsByTopic($topicId, $cursor, $count, &$nextCursor) {
        /** @var Response $response */
        $response = Request::get($this->uri('/api/groups?topic_id='.$topicId
            .'&cursor='.$cursor.'&count='.$count))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            $groups = array();
            foreach ($response->body as $groupInfo) {
                $group = $this->buildGroup($groupInfo);
                if ($group) {
                    $groups[$group->id] = $group;
                }
            }
            $nextCursor = $response->headers->offsetGet('X-CY-Cursor');
            if ($nextCursor) {
                $nextCursor = intval($nextCursor);
            } else {
                $nextCursor = 0;
            }

            return $groups;
        } else {
            return array();
        }
    }

    /**
     * @param int $userId
     * @param int $topicId
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     *
     * @return Group[]|null
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getGroupsByUserInTopic($userId, $topicId, $cursor, $count, &$nextCursor) {
        /** @var Response $response */
        $response = Request::get($this->uri('/api/groups?topic_id='.$topicId.'&user_id='.$userId
            .'&cursor='.$cursor.'&count='.$count))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            $groups = array();
            foreach ($response->body as $groupInfo) {
                $group = $this->buildGroup($groupInfo);
                if ($group) {
                    $groups[$group->id] = $group;
                }
            }
            $nextCursor = $response->headers->offsetGet('X-CY-Cursor');
            if ($nextCursor) {
                $nextCursor = intval($nextCursor);
            } else {
                $nextCursor = 0;
            }

            return $groups;
        } else {
            return array();
        }
    }

    /**
     * @param Response $response
     *
     * @throws Exceptions\CanNotKickYourselfException
     * @throws Exceptions\GroupNonExistException
     * @throws Exceptions\GroupPermissionDeniedException
     * @throws Exceptions\MemberExceedLimitException
     * @throws Exceptions\YouHavaBeenKickedException
     * @throws \Exception
     */
    private function handleError($response) {
        if ($response->code == 404) {
            throw new Exceptions\GroupNonExistException();
        }
        if ($response->content_type == Mime::JSON && isset($response->body->error_code)) {
            $errorCode = $response->body->error_code;
            switch ($errorCode) {
                case 'group_member_exceed_limit':
                    throw new Exceptions\MemberExceedLimitException();
                case 'group_permission_denied':
                    throw new Exceptions\GroupPermissionDeniedException();
                case 'group_you_have_been_kicked':
                    throw new Exceptions\YouHavaBeenKickedException();
                case 'group_cannot_kick_yourself':
                    throw new Exceptions\CanNotKickYourselfException();
                case 'join_too_much_group_in_topic':
                    throw new Exceptions\JoinTooMuchGroupInTopic();
            }
        }
        throw new \Exception('unknow error from im group service'.$response->raw_body);
    }

    /**
     * @param int $gid
     * @param int $mid
     * @param bool $force
     *
     * @throws Exceptions\CanNotKickYourselfException
     * @throws Exceptions\GroupNonExistException
     * @throws Exceptions\GroupPermissionDeniedException
     * @throws Exceptions\MemberExceedLimitException
     * @throws Exceptions\YouHavaBeenKickedException
     * @throws Exceptions\JoinTooMuchGroupInTopic
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function join($gid, $mid, $force = false) {
        /** @var Response $response */
        $response = Request::put($this->uri('/api/groups/'.$gid.'/members/'.$mid.($force ? '?force' : '')))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int $gid
     * @param int $mid
     *
     * @throws Exceptions\CanNotKickYourselfException
     * @throws Exceptions\GroupNonExistException
     * @throws Exceptions\GroupPermissionDeniedException
     * @throws Exceptions\MemberExceedLimitException
     * @throws Exceptions\YouHavaBeenKickedException
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function leave($gid, $mid) {
        /** @var Response $response */
        $response = Request::delete($this->uri('/api/groups/'.$gid.'/members/'.$mid))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int $mid
     * @param int $topicId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function leaveGroupsOfTopic($mid, $topicId) {
        /** @var Response $response */
        $response = Request::delete($this->uri('/api/groups?tid='.$topicId.'&mid='.$mid))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int $gid
     * @param int $kickerId
     * @param int $kickeeId
     *
     * @throws Exceptions\CanNotKickYourselfException
     * @throws Exceptions\GroupNonExistException
     * @throws Exceptions\GroupPermissionDeniedException
     * @throws Exceptions\MemberExceedLimitException
     * @throws Exceptions\YouHavaBeenKickedException
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function kickout($gid, $kickerId, $kickeeId) {
        /** @var Response $response */
        $response = Request::post($this->uri('/api/groups/'.$gid.'/kickout'),
            array('kicker' => $kickerId, 'kickee' => $kickeeId))
            ->timeoutIn(5)
            ->contentType(Mime::FORM)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int $gid
     * @param int $disbanderId
     *
     * @throws Exceptions\GroupNonExistException
     * @throws Exceptions\GroupPermissionDeniedException
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function disband($gid, $disbanderId) {
        if ($disbanderId > 0) {
            $uri = '/api/groups/'.$gid.'?disbander='.$disbanderId;
        } else {
            $uri = '/api/groups/'.$gid;
        }
        /** @var Response $response */
        $response = Request::delete($this->uri($uri))
            ->timeoutIn(5)
            ->contentType(Mime::FORM)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int[] $gids
     * @return int[]
     *
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getMemberCounts($gids) {
        /** @var Response $response */
        $response = Request::get($this->uri('/api/groups/member_counts?gids='.implode(',', $gids)))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->withoutAutoParsing()
            ->send();
        if ($response->code == 200 && $response->content_type == Mime::JSON) {
            return json_decode($response->body, true);
        }
        $this->handleError($response);
        return 0;
    }

    /**
     * @param int $gid
     * @param int $mid
     *
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function setNoDisturb($gid, $mid) {
        /** @var Response $response */
        $response = Request::put($this->uri('/api/groups/'.$gid.'/members/'.$mid.'/no_disturb'))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }

    /**
     * @param int $gid
     * @param int $mid
     *
     * @throws \Exception
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function unsetNoDisturb($gid, $mid) {
        /** @var Response $response */
        $response = Request::delete($this->uri('/api/groups/'.$gid.'/members/'.$mid.'/no_disturb'))
            ->timeoutIn(5)
            ->addHeader('X-API-TOKEN', $this->apiToken)
            ->send();
        if ($response->code == 200) {
            return;
        }
        $this->handleError($response);
    }
}