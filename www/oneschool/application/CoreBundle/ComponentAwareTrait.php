<?php
namespace Lychee\Bundle\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Lychee\Component\Security\SensitiveWordChecker\SensitiveWordChecker;

trait ComponentAwareTrait {
    use ContainerAwareTrait;

    /**
     * @return SensitiveWordChecker
     */
    public function sensitiveWordChecker() {
        return $this->container()->get('lychee_core.sensitive_word_checker');
    }
} 