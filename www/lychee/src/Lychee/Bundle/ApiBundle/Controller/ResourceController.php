<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Module\ContentManagement\Entity\ExpressionPackage;
use Lychee\Module\ContentManagement\ExpressionManagement;
use Lychee\Module\ContentManagement\StickerManagement;
use Lychee\Module\ContentManagement\Entity\Sticker;
use Lychee\Module\ContentManagement\Entity\Expression;

class ResourceController extends Controller {

    /**
     * @Route("/resource/version")
     * @Method("GET")
     * @ApiDoc(
     *   section="resource",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function getResourceVersion(Request $request) {
        $this->requireAuth($request);
        /** @var ExpressionManagement $em */
        $em = $this->get('lychee.module.content_management.expression');
        return $this->dataResponse(array(
            'paster' => $this->sticker()->getStickerVersion(),
            'expression' => $em->getExpressionPackageVersion()
        ));
    }

    /**
     * @Route("/paster/all")
     * @Method("GET")
     * @ApiDoc(
     *   section="resource",
     *   parameters={
     *
     *   }
     * )
     */
    public function allPasterAction() {
        $json = $this->sticker()->getStickersWithJson();

        return new Response(
            $json, 200,
            array(
                'Content-Type' => 'application/json'
            )
        );
    }

    /**
     * @Route("/paster/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="resource",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false, "description"="默认20, 最大50"}
     *   }
     * )
     */
    public function listPasterAction(Request $request) {
        $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        /** @var Sticker[] $stickers */
        $stickers = $this->sticker()->fetchStickers($cursor + 1, $count);

        $result = array();
        foreach ($stickers as $sticker) {
            $result[] = array(
                'package_id' => $sticker->id,
                'name' => $sticker->name,
                'thumbnail_url' => $sticker->thumbnailUrl,
                'is_new' => (string)$sticker->isNew,
                'url' => $sticker->url,
                'local_folder_name' => 'biaoqing_' . $sticker->id,
                'stickers' => array(),
                'last_modified' => $sticker->lastModifiedTime ? $sticker->lastModifiedTime->getTimestamp() : null
            );
        }
        if (count($stickers) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + 1;
        }

        return $this->arrayResponse('pasters', $result, $nextCursor);
    }

    /**
     * @Route("/expression/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="resource",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false, "description"="默认20, 最大50"}
     *   }
     * )
     */
    public function listExpressionAction(Request $request) {
        $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        /** @var ExpressionManagement $em */
        $em = $this->get('lychee.module.content_management.expression');
        /** @var ExpressionPackage[] $eps */
        $eps = $em->fetchAllEnabledExpressionPackages($cursor + 1, $count);
        $packageIds = array_map(function($ep){return $ep->getId();}, $eps);
        $expressions = $em->fetchExpressionsByPackageIds($packageIds);

        $result = array();
        foreach ($eps as $ep) {
            $result[] = array(
                'package_id' => $ep->getId(),
                'name' => $ep->getName(),
                'thumbnail_url' => $ep->getCoverImage(),
                'url' => $ep->getDownloadUrl(),
                'last_modified' => $ep->getLastModifiedTime() ? $ep->getLastModifiedTime()->getTimestamp() : null,
                'expressions' => array_map(function($e){
                    /** @var Expression $e */
                    return array(
                        'id' => $e->getId(),
                        'name' => $e->getName(),
                        'url' => $e->getImageUrl(),
                        'filename' => $e->getFilename()

                    );
                }, $expressions[$ep->getId()])
            );
        }
        if (count($eps) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + 1;
        }

        return $this->arrayResponse('expressions', $result, $nextCursor);
    }

}