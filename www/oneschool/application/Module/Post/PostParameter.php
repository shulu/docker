<?php
namespace app\module\post;

class PostParameter
{

    private $parameters = array();

    private function getValueOrNull($name) {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            return null;
        }
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setPostId($id) {
        $this->parameters['id'] = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPostId() {
        return $this->getValueOrNull('id');
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setAuthorId($userId) {
        $this->parameters['author'] = $userId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAuthorId() {
        return $this->getValueOrNull('author');
    }

    /**
     * @param int $topicId
     *
     * @return $this
     */
    public function setTopicId($topicId) {
        $this->parameters['topic'] = $topicId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTopicId() {
        return $this->getValueOrNull('topic');
    }

    /**
     * @param string $content
     * @param string $title
     *
     * @return $this
     */
    public function setContent($content) {
        $this->parameters['content'] = $content;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent() {
        return $this->getValueOrNull('content');
    }

    /**
     * @param string $imageUrl
     * @param string $videoUrl
     * @param string $audioUrl
     * @param string $siteUrl
     *
     * @return $this
     */
    public function setResource($imageUrl, $videoUrl = null, $audioUrl = null, $siteUrl = null) {
        $this->parameters['image'] = $imageUrl;
        $this->parameters['video'] = $videoUrl;
        $this->parameters['audio'] = $audioUrl;
        $this->parameters['site'] = $siteUrl;
        return $this;
    }

	/**
	 * @param $videoUrl
	 *
	 * @return $this
	 */
    public function setVideo($videoUrl) {
    	$this->parameters['video'] = $videoUrl;
	    return $this;
    }

    /**
     * @return string|null
     */
    public function getImageUrl() {
        return $this->getValueOrNull('image');
    }

    /**
     * @return string|null
     */
    public function getVideoUrl() {
        return $this->getValueOrNull('video');
    }

    /**
     * @return string|null
     */
    public function getAudioUrl() {
        return $this->getValueOrNull('audio');
    }

    /**
     * @return string|null
     */
    public function getSiteUrl() {
        return $this->getValueOrNull('site');
    }

    /**
     * @param float $longitude
     * @param float $latitude
     * @param string $address
     *
     * @return $this
     */
    public function setGeo($longitude, $latitude, $address) {
        $this->parameters['longitude'] = $longitude;
        $this->parameters['latitude'] = $latitude;
        $this->parameters['address'] = $address;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude() {
        return $this->getValueOrNull('longitude');
    }

    /**
     * @return float
     */
    public function getLatitude() {
        return $this->getValueOrNull('latitude');
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->getValueOrNull('address');
    }

    /**
     * @param string $annotation
     *
     * @return $this
     */
    public function setAnnotation($annotation) {
        $this->parameters['annotation'] = $annotation;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnnotation() {
        return $this->getValueOrNull('annotation');
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType($type) {
        $this->parameters['type'] = $type;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getType() {
        return $this->getValueOrNull('type');
    }

    /**
     * @param int $groupId
     *
     * @return $this
     */
    public function setImGroupId($groupId) {
        $this->parameters['im_group'] = $groupId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getImGroupId() {
        return $this->getValueOrNull('im_group');
    }

    /**
     * @param int $scheduleId
     *
     * @return $this
     */
    public function setScheduleId($scheduleId) {
        $this->parameters['schedule'] = $scheduleId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getScheduleId() {
        return $this->getValueOrNull('schedule');
    }

    /**
     * @param int $votingId
     *
     * @return $this
     */
    public function setVotingId($votingId) {
        $this->parameters['voting'] = $votingId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getVotingId() {
        return $this->getValueOrNull('voting');
    }

    /**
     * @param int cityId
     *
     * @return $this
     */
    public function setCityId($cityId){
    	$this->parameters['cityId'] = $cityId;
    	return $this;
    }


    /**
     * @return int|null
     */

    public function getCityId(){
    	return $this->getValueOrNull('cityId');
    }

	/**
	 * @param int authorLevel
	 *
	 * @return $this
	 */
	public function setAuthorLevel($authorLevel){
		$this->parameters['authorLevel'] = $authorLevel;
		return $this;
	}


	/**
	 * @return int|null
	 */

	public function getAuthorLevel(){
		return $this->getValueOrNull('authorLevel');
	}

	/**
	 * @param int isVip
	 *
	 * @return $this
	 */
	public function setIsVip($isVip){
		$this->parameters['isVip'] = $isVip;
		return $this;
	}


	/**
	 * @return int|null
	 */

	public function getIsVip(){
		return $this->getValueOrNull('isVip');
	}


    /**
     * @param int bgmId
     *
     * @return $this
     */
    public function setBgmId($bgmId){
        $this->parameters['bgmId'] = $bgmId;
        return $this;
    }


    /**
     * @return int|null
     */
    public function getBgmId(){
        return $this->getValueOrNull('bgmId');
    }

    /**
     * @param int svId
     *
     * @return $this
     */
    public function setSvId($svId){
        $this->parameters['svId'] = $svId;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getSVId(){
        return $this->getValueOrNull('svId');
    }

}