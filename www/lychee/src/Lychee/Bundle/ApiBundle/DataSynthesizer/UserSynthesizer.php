<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\GraphStorage\FollowingResolver;
use Lychee\Component\GraphStorage\FollowingCounter;
use Lychee\Constant;
use Lychee\Module\Relation\BlackList\BlackListResolver;
use Lychee\Module\Topic\Following\Counter;
use Lychee\Module\Topic\CoreMember\CoreMemberTitleResolver;
use Lychee\Component\Foundation\ImageUtility;

class UserSynthesizer extends AbstractSynthesizer {

    /**
     * @var FollowingResolver
     */
    private $followingResolver;

    /**
     * @var FollowingCounter
     */
    private $followingCounter;

    /**
     * @var Counter
     */
    private $topicFollowingCounter;

    /**
     * @var Synthesizer
     */
    private $countingSynthesizer;

    /**
     * @var Synthesizer
     */
    private $profileSynthesizer;
    private $blackListResolver;

    private $coreMemberTitleResolver;
    private $vipSynthesizer;

    private $sensitiveWordCheckerService;
    private $favoriteService;

    private $isPersonal;

    /**
     * @param array $usersByIds
     * @param FollowingResolver|null $followingResolver
     * @param FollowingCounter|null $followingCounter
     * @param Counter|null $topicFollowingCounter
     * @param Synthesizer|null $countingSynthesizer
     * @param Synthesizer|null $profileSynthesizer
     * @param BlackListResolver|null $blackListResolver
     * @param CoreMemberTitleResolver|null $coreMemberTitleResolver
     * @param Synthesizer|null $vipSynthesizer
     * @param Service|null $sensitiveWordCheckerService
     * @param \Lychee\Module\Favorite\FavoriteService|null $favoriteService
     */
    public function __construct(
        $usersByIds,
        $followingResolver,
        $followingCounter,
        $topicFollowingCounter,
        $countingSynthesizer,
        $profileSynthesizer,
        $blackListResolver,
        $coreMemberTitleResolver,
        $vipSynthesizer,
        $sensitiveWordCheckerService=null,
        $favoriteService=null,
        $isPersonal=false
    ) {
        parent::__construct($usersByIds);
        $this->followingResolver = $followingResolver;
        $this->followingCounter = $followingCounter;
        $this->topicFollowingCounter = $topicFollowingCounter;
        $this->countingSynthesizer = $countingSynthesizer;
        $this->profileSynthesizer = $profileSynthesizer;
        $this->blackListResolver = $blackListResolver;
        $this->coreMemberTitleResolver = $coreMemberTitleResolver;
        $this->vipSynthesizer = $vipSynthesizer;
        $this->sensitiveWordCheckerService = $sensitiveWordCheckerService;
        $this->favoriteService = $favoriteService;
        $this->isPersonal = $isPersonal;
    }

    /**
     * @param User $user
     * @param int $topicId
     * @return array
     */
    protected function synthesize($user, $topicId = null) {
        $result = array(
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar_url' => $user->avatarUrl,
            'gender' => $user->gender == User::GENDER_MALE ? 'male':
                    ($user->gender == User::GENDER_FEMALE ? 'female':
                        null),
            'level' => $user->level,
            'signature' => $user->signature,
	        'ciyoCoin' => $user->ciyoCoin,
            'phone' => ''
        );

        if ($this->isPersonal) {
            $result['phone'] = $user->phone;
        }

        if ($this->sensitiveWordCheckerService) {
            $result['nickname'] = $this->sensitiveWordCheckerService->replaceSensitiveWords($result['nickname']);
        }

        if ($this->followingResolver) {
            $result['my_follower'] = $this->followingResolver->isFollower($user->id);
            $result['my_followee'] = $this->followingResolver->isFollowee($user->id);
        }
        if ($this->followingCounter) {
            if ($user->id == Constant::CIYUANJIANG_ID) {
                $result['followers_count'] = 233333;
                $result['followees_count'] = 0;
            } else {
                $result['followers_count'] = $this->followingCounter->countFollowers($user->id);
                $result['followees_count'] = $this->followingCounter->countFollowees($user->id);
            }
        }
        if ($this->topicFollowingCounter) {
            $result['following_topics_count'] = $this->topicFollowingCounter->getCount($user->id);
        }
        if ($this->countingSynthesizer) {
            $counting = $this->countingSynthesizer->synthesizeOne($user->id);
            if ($counting) {
                $result = array_merge($result, $counting);
            }
        }
        if ($this->profileSynthesizer) {
            $profile = $this->profileSynthesizer->synthesizeOne($user->id);
            $result = array_merge($result, $profile);
        }
        if ($this->blackListResolver && $this->blackListResolver->isBlocking($user->id)) {
            $result['isBlocked'] = true;
        }
        if ($topicId && $this->coreMemberTitleResolver) {
            $topicTitle = $this->coreMemberTitleResolver->resolve($topicId, $user->id);
            if ($topicTitle) {
                $result['topic_title'] = $topicTitle;
            }
        }
        if ($this->vipSynthesizer) {
            $vipInfo = $this->vipSynthesizer->synthesizeOne($user->id);
            if ($vipInfo) {
                $result = array_merge($result, $vipInfo);
            }
        }

        $result['favourites_count'] = 0;
        if ($this->favoriteService) {
            $result['favourites_count'] = $this->favoriteService->getCount($user->id);
        }

        return $result;
    }
}