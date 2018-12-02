<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/2/16
 * Time: 4:16 PM
 */

namespace Lychee\Module\Recommendation\Task;


interface Observer {

    public function getName();

    public function doActor();

}