<?php
namespace Lychee\Module\ContentAudit\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Module\ContentAudit\Entity\ImageReviewAuditConfig;
use Lychee\Module\ContentAudit\Entity\ImageReview;

/**
 * @group \Lychee\Module\ContentAudit\ImageReviewService
 */
class ImageReviewServiceTest extends ModuleAwareTestCase {

    public function getService() {
        return $this->container->get('lychee.module.image_review');
    }

    /**
     * 确定色情阈值调整测试
     *
     * @covers ::isRejectAudit
     */
    public function testIsRejectAuditWithSurePorn() {
        $service =  $this->getService();
        $service->updateAuditConfigs([
            ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID=>50,
            ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID=>101,
            ]);

        $imageReview = new ImageReview();
        $imageReview->label = ImageReview::LABEL_PORN;
        $imageReview->review = false;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertTrue($r);

        $imageReview->rate = 0.4;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->review = true;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_SEXY;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_LEGAL;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);
    }



    /**
     * 疑似色情阈值调整测试
     *
     * @covers ::isRejectAudit
     */
    public function testIsRejectAuditWithUnSurePorn() {
        $service =  $this->getService();
        $service->updateAuditConfigs([
            ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID=>50,
            ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID=>101,
        ]);

        $imageReview = new ImageReview();
        $imageReview->label = ImageReview::LABEL_PORN;
        $imageReview->review = true;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertTrue($r);

        $imageReview->rate = 0.4;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->review = false;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_SEXY;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_LEGAL;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);
    }

    /**
     * 确定性感阈值调整测试
     *
     * @covers ::isRejectAudit
     */
    public function testIsRejectAuditWithSureSexy() {
        $service =  $this->getService();
        $service->updateAuditConfigs([
            ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID=>50,
            ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID=>101,
        ]);

        $imageReview = new ImageReview();
        $imageReview->label = ImageReview::LABEL_SEXY;
        $imageReview->review = false;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertTrue($r);

        $imageReview->rate = 0.4;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->review = true;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_PORN;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_LEGAL;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);
    }

    /**
     * 疑似性感阈值调整测试
     *
     * @covers ::isRejectAudit
     */
    public function testIsRejectAuditWithUnSureSexy() {
        $service =  $this->getService();
        $service->updateAuditConfigs([
            ImageReviewAuditConfig::TRASH_PORN_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_PORN_UNSURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_SURE_MIN_RATE_ID=>101,
            ImageReviewAuditConfig::TRASH_SEXY_UNSURE_MIN_RATE_ID=>50,
        ]);

        $imageReview = new ImageReview();
        $imageReview->label = ImageReview::LABEL_SEXY;
        $imageReview->review = true;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertTrue($r);

        $imageReview->rate = 0.4;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->review = false;
        $imageReview->rate = 0.5;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_PORN;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);

        $imageReview->label = ImageReview::LABEL_LEGAL;
        $r = $service->isRejectAudit($imageReview);
        $this->assertFalse($r);
    }



    /**
     * 获取配置
     *
     * @covers ::getAuditConfigs
     */
    public function testGetAuditConfigs() {
        $defaultConfigs = ImageReviewAuditConfig::getDefaultConfigs();
        $configIds = array_keys($defaultConfigs);
        $configs =  $this->getService()->getAuditConfigs($configIds);
        foreach ($configIds as $configId) {
            $this->assertArrayHasKey($configId, $configs);
        }
    }


}