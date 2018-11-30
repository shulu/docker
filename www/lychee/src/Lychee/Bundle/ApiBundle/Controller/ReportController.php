<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Module\Recommendation\Post\PredefineGroup;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Account\AccountService;

class ReportController extends Controller {
    /**
     * @Route("/report/{type}", requirements={"type": "(post|comment)"})
     * @Method("POST")
     * @ApiDoc(
     *   section="misc",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="post_id", "dataType"="integer", "required"=false},
     *     {"name"="comment_id", "dataType"="integer", "required"=false},
     *   }
     * )
     */
    public function reportAction(Request $request, $type) {
        $account = $this->requireAuth($request);

        if ($type == 'post') {
            $postId = $this->requireId($request->request, 'post_id');

            $this->report()->reportPost($account->id, $postId);

            $recGroupService = $this->get('lychee.module.recommendation.group_posts');
            $inJingXuan = empty($recGroupService->filterNoInGroupPosts(PredefineGroup::ID_JINGXUAN, [$postId]));
            if ($inJingXuan) {
                $postReportedCount = $this->report()->countPostReports($postId);
                if ($postReportedCount >= 2) {
                    $recGroupService->deletePostIdsInGroup(PredefineGroup::ID_JINGXUAN, [$postId]);
                }
            }

            if ($this->account()->isAdmin($account->id)) {
                $this->post()->delete($postId);
            }
        } else {
            $commentId = $this->requireId($request->request, 'comment_id');

            $this->report()->reportComment($account->id, $commentId);

            if ($this->account()->isAdmin($account->id)) {
                $this->comment()->delete($commentId);
            }
        }

        return $this->sucessResponse();
    }
} 