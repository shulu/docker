<?php
namespace Lychee\Component\Task;

interface Task {
    /**
     * @return string
     */
    public function getName();

    /**
     * @return integer
     */
    public function getDefaultInterval();

    /**
     * @return void
     */
    public function run();
}