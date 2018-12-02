<?php
namespace Lyhcee\Module\Voting\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\Voting\Entity\VotingOption;
use Lychee\Module\Voting\VotingService;

class VotingTest extends ModuleAwareTestCase {

    public function test() {
        $service = new VotingService($this->container()->get('doctrine'));
//        $voting = $service->create(1, 'test', 'test desc', array(
//            new VotingOption('option 1'), new VotingOption('option 2')
//        ));
//        var_dump($voting);
//        $service->vote(31722, 1, 1);
//        $r = $service->buildVoteResolver(31728, array(1,2));
//        var_dump($r);
//        $voters = $service->getOptionVoters(1, 2, 0, 10);
//        var_dump($voters);
        $r = $service->getLatestVotersPerOption(1, 10);
        var_dump($r);
    }
    
}