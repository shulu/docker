<?php

namespace Lychee\Bundle\ApiBundle\DataSynthesizer;


interface Synthesizer {
    /**
     * @param mixed $info
     * @return array
     */
    public function synthesizeAll($info = null);

    /**
     * @param int $id
     * @param mixed $info
     *
     * @return array
     */
    public function synthesizeOne($id, $info = null);

    /**
     * @param int[] $ids
     * @param mixed $info
     *
     * @return array
     */
    public function synthesizeMany($ids, $info = null);
} 