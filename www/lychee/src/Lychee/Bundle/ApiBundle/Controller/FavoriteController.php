<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\PostError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class FavoriteController extends Controller {
    /**
     * @Route("/favorite/posts/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="favorite",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function getFavoritePostsAction(Request $request) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getArrayCursorAndCount($request->query, 20, 50);

        $favoriteService = $this->get('lychee.module.favorite');
        $postIds = $favoriteService->userListFavoritePost($account->id, $cursor, $count, $nextCursor);

        $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account->id);
        return $this->arrayResponse('posts', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/favorite/posts/add")
     * @Method("POST")
     * @ApiDoc(
     *   section="favorite",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function addFavoritePostAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $favoriteService = $this->get('lychee.module.favorite');
        $favoriteService->userAddFavoritePost($account->id, $post->id);
        return $this->sucessResponse();
    }

    /**
     * @Route("/favorite/posts/remove")
     * @Method("POST")
     * @ApiDoc(
     *   section="favorite",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="pid", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function removeFavoritePostAction(Request $request) {
        $account = $this->requireAuth($request);
        $postId = $this->requireId($request->request, 'pid');

        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $favoriteService = $this->get('lychee.module.favorite');
        $favoriteService->userRemoveFavoritePost($account->id, $postId);

        return $this->sucessResponse();
    }

    /**
     * @Route("/favorite/posts/{postId}", requirements={"postId": "\d+"})
     * @Method("POST")
     * @ApiDoc(
     *   section="favorite",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function hasFavoritedPostAction(Request $request, $postId) {
        $account = $this->requireAuth($request);

        $post = $this->post()->fetchOne($postId);
        if ($post === null) {
            return $this->errorsResponse(PostError::PostNotExist($postId));
        }

        $favoriteService = $this->get('lychee.module.favorite');
        $favorited = $favoriteService->userHasFavoritedPost($account->id, $postId);

        return $this->dataResponse(['result' => $favorited]);
    }
}