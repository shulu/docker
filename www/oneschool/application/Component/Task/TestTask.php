<?php
namespace Lychee\Component\Task;

class TestTask implements Task {
    /**
     * @return string
     */
    public function getName() {
        return "test_task";
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 3600 * 4;
    }

    /**
     * @return void
     */
    public function run() {
        echo "just a test~\n";
    }

} 