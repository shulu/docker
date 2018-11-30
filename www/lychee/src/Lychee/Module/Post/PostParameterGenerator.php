<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 27/10/2016
 * Time: 2:43 PM
 */

namespace Lychee\Module\Post;


use GuzzleHttp\Client;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\IdGenerator\IdGenerator;
use Lychee\Component\Storage\StorageInterface;
use Lychee\Module\IM\GroupService;
use Lychee\Module\Schedule\ScheduleService;
use Lychee\Module\Voting\Entity\VotingOption;
use Lychee\Module\Voting\VotingService;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class PostParameterGenerator {

	private $annotation = [];

	public function __construct(
		PostParameter $postParameter,
		StorageInterface $imageStorage,
		$type = null,
		$topicId = null,
		$authorId = null
	) {
		$this->postParameter = $postParameter;
		$this->imageStorage = $imageStorage;
		$this->postTypes = $this->filterPostTypes();
		if (null !== $type && $this->isPostTypeValid($type)) {
			$this->postParameter->setType($type);
		}
		$this->postParameter->setAuthorId($authorId);
		$this->postParameter->setTopicId($topicId);
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	private function isPostTypeValid($type) {
		return in_array($type, $this->postTypes);
	}

	private function filterPostTypes() {
		$postReflection = new \ReflectionClass(Post::class);
		$constants = $postReflection->getConstants();
		$constantsName = array_keys($constants);
		$prefix = 'TYPE_';
		$postTypes = [];
		foreach ($constantsName as $name) {
			if (0 === strncmp($prefix, $name, strlen($prefix))) {
				$postTypes[$name] = $constants[$name];
			}
		}

		return $postTypes;
	}

	public function setType($type) {
		if ($this->isPostTypeValid($type)) {
			throw new InvalidPostTypeException($type, $this->postTypes);
		}
		$this->postParameter->setType($type);

		return $this;
	}

	public function setContent($content) {
		$this->postParameter->setContent($content);

		return $this;
	}

	public function setTopicId($topicId) {
		$this->postParameter->setTopicId($topicId);

		return $this;
	}

	public function setAuthorId($authorId) {
		$this->postParameter->setAuthorId($authorId);

		return $this;
	}

	public function setResourcePostParameter($resourceUrl) {
		$originalUrlKey = PostAnnotation::ORIGINAL_URL;
		$resourceImage = '';
		if (isset($this->annotation[$originalUrlKey])) {
			$resourceImage = $this->annotation[$originalUrlKey];
		}
		do {
			if ($resourceUrl) {
				$client = new Client();
				try {
					$res = $client->get($resourceUrl);
					if ($res->getStatusCode() == 200) {
						$html = $res->getBody()->getContents();
						$crawler = new Crawler($html);
						$titleNode = $crawler->filter('head > title');
						if ($titleNode->count()) {
							$title = $titleNode->first()->text();
							if ($title) {
								$resourceTitle = $title;
								break;
							}
						}
					}
				} catch (\Exception $e) {

				}
			}
			$resourceTitle = '';
		} while (0);
		$resourceAnnotation = PostAnnotation::setResource($resourceUrl, $resourceTitle, $resourceImage);
		$this->mergeAnnotation($resourceAnnotation);

		return $this;
	}

	/**
	 * @param $annotation
	 *
	 * @return array
	 */
	private function mergeAnnotation($annotation) {
		$this->annotation = array_merge($this->annotation, $annotation);

		return $this->annotation;
	}

	public function setDefaultPostParameter($images, $videoUrl = null, $audioUrl = null, $siteUrl = null, $misc = []) {
		$annotation = [];
		$imageUrl = null;
		$HDPictureUrls = [];
		$pictureUrls = [];
		$imgTypeArray = [];
		$gifIndex= [];
		if (is_array($images)) {
			foreach ($images as $pic) {
				if ($pic) {
					$imgType = exif_imagetype($pic);
					if ($imgType === IMAGETYPE_JPEG || $imgType === IMAGETYPE_PNG) {
						$img = ImageUtility::compressImg($this->imageStorage, $pic, $imgType);
						if (false !== $img) {
							$HDPictureUrls[] = $this->imageStorage->put($pic);
							$pictureUrls[] = $img;
							$imgTypeArray[] = $imgType;
						}
					} elseif ($imgType === IMAGETYPE_GIF) {
						$img = $this->imageStorage->put($pic);
						$pictureUrls[] = $img;
						$HDPictureUrls[] = $img;
						$imgTypeArray[] = $imgType;
					}

				}
			}
		}
		if (count($imgTypeArray)) {
			foreach ($imgTypeArray as $index => $value) {
				if ($value === 1) {
					$gifIndex[] = $index;
				}
			}
		}
		$picCount = count($pictureUrls);
		$imageUrl = null;
		if ($picCount > 0) {
			foreach ($images as $img) {
				list($width[],$height[]) = getimagesize($img);
			}
			if ($picCount === 1) {
				$annotation = PostAnnotation::setSinglePhoto(
					isset($HDPictureUrls[0])? $HDPictureUrls[0]:null,
					$pictureUrls[0],
					$width,
					$height,
					$gifIndex
				);
			} else {
				$annotation = PostAnnotation::setMultiPhotos($HDPictureUrls, $pictureUrls, $width, $height, $gifIndex);
			}
			$imageUrl = $pictureUrls[0];
		}
		$this->postParameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);

		$annotation = array_merge($annotation, $misc);
		$this->annotation = array_merge($this->annotation, $annotation);

		return $this;
	}

	/**
	 * @param $videoUrl
	 * @param null $videoCover
	 * @param null $newVideoCoverFile
	 *
	 * @return $this
	 */
	public function setVideoPostParameter($videoUrl, $videoCover = null, $newVideoCoverFile = null) {
		$this->postParameter->setVideo($videoUrl);
		if (null !== $newVideoCoverFile) {
			$newCover = ImageUtility::compressImg($this->imageStorage, $newVideoCoverFile);
			if ($newCover) {
				$videoCover = $newCover;
			}
		}
		if ($videoCover) {
			list($width, $height) = getimagesize($videoCover);
			$misc = [
				'video_cover' => $videoCover,
				'video_cover_width' => $width,
				'video_cover_height' => $height,
			];
		} else {
			$misc = [];
		}
		$this->mergeAnnotation($misc);

		return $this;
	}

	public function setSchedulePostParameter(
		IdGenerator $idGenerator,
		ScheduleService $scheduleService,
		$scheduleTitle,
		$scheduleDesc,
		$scheduleAddress,
		$scheduleStartTime,
		$scheduleEndTime
	) {
		$postId = $idGenerator->generate();
		$schedule = $scheduleService->create(
			$this->postParameter->getAuthorId(),
			$this->postParameter->getTopicId(),
			$postId,
			$scheduleTitle,
			$scheduleDesc,
			$scheduleAddress,
			null,
			null,
			null,
			new \DateTime($scheduleStartTime),
			new \DateTime($scheduleEndTime)
		);
		$scheduleService->join($this->postParameter->getAuthorId(), $schedule->id);
		$this->postParameter->setScheduleId($schedule->id);
		$this->postParameter->setPostId($postId);

		return $this;
	}

	public function setVotingPostParameter(
		$votingTitle,
		$content,
		$votingOptionTitles,
		IdGenerator $idGenerator,
		VotingService $votingService
	) {
		if (mb_strlen($votingTitle, 'utf8') > 60) {
			throw new \Exception('标题不能多于60个字');
		}
		$options = [];
		foreach ($votingOptionTitles as $title) {
			$opt = new VotingOption();
			$opt->title = $title;
			$options[] = $opt;
		}
		if (count($options) < 2) {
			throw new \Exception('投票选项必须多于2个');
		}
		$postId = $idGenerator->generate();
		$voting = $votingService->create($postId, $votingTitle, $content, $options);
		$this->postParameter->setVotingId($voting->id);
		$this->postParameter->setPostId($postId);

		return $this;
	}

	public function setGroupChatPostParameter(
		$content,
		$chatGroupName,
		IdGenerator $idGenerator,
		GroupService $groupService
	) {
		if (mb_strlen($chatGroupName, 'utf8') > 20) {
			throw new \Exception('群聊组名不能多于20个字');
		}
		$postId = $idGenerator->generate();
		$this->postParameter->setPostId($postId);
		$group = $groupService->create(
			$this->postParameter->getAuthorId(),
			$chatGroupName,
			null,
			$content,
			$this->postParameter->getTopicId(),
			$postId
		);
		if ($group == null) {
			throw new \Exception(CommonError::SystemBusy());
		}
		$this->postParameter->setImGroupId($group->id);
		$this->postParameter->setPostId($postId);

		return $this;
	}

	/**
	 * @return PostParameter
	 */
	public function genPostParameter() {
		if (empty($this->annotation)) {
			$annotation = json_encode($this->annotation, JSON_FORCE_OBJECT);
		} else {
			$annotation = json_encode($this->annotation);
		}
		$this->postParameter->setAnnotation($annotation);

		return $this->postParameter;
	}
}

class InvalidPostTypeException extends \Exception {

	/**
	 * InvalidPostTypeException constructor.
	 *
	 * @param string $postType
	 * @param array $postTypes
	 */
	public function __construct($postType, $postTypes) {
		$this->message = sprintf('非法的帖子类型: %s not in [%s]', $postType, implode(', ', $postTypes));
		parent::__construct($this->message);
	}

}