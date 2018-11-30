<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Module\Voting\Exception\InvalidOptionException;
use Lychee\Module\Voting\Exception\InvalidVotingException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Voting\VotingService;

class VotingController extends Controller {

    /**
     * @return VotingService
     */
    private function votingService() {
        return $this->get('lychee.module.voting');
    }

    /**
     * @Route("/voting/vote")
     * @Method("post")
     * @ApiDoc(
     *   section="voting",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="voting", "dataType"="integer", "required"=true},
     *     {"name"="option", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function vote(Request $request) {
        $account = $this->requireAuth($request);
        $votingId = $this->requireId($request->request, 'voting');
        $option = $this->requireId($request->request, 'option');

        $service = $this->votingService();
        try {
            $service->vote($account->id, $votingId, $option);
        } catch (InvalidVotingException $e) {
            return $this->errorsResponse(CommonError::ParameterInvalid('voting', $votingId));
        } catch (InvalidOptionException $e) {
            return $this->errorsResponse(CommonError::ParameterInvalid('option', $option));
        }

        return $this->sucessResponse();
    }

    /**
     * @Route("/voting/option_voters")
     * @Method("GET")
     * @ApiDoc(
     *   section="voting",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="voting", "dataType"="integer", "required"=true},
     *     {"name"="option", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getVoters(Request $request) {
        $account = $this->requireAuth($request);
        $votingId = $this->requireId($request->query, 'voting');
        $option = $this->requireId($request->query, 'option');
        list($cursor, $count) = $this->getStringCursorAndCount($request->query, 20, 50);

        $service = $this->votingService();
        $voterIds = $service->getOptionVoters($votingId, $option, $cursor, $count, $nextCursor);

        $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer($voterIds, $account->id);
        return $this->arrayResponse('users', $synthesizer->synthesizeAll(), $nextCursor);
    }

}