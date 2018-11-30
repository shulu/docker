<?php
namespace Lychee\Bundle\CoreBundle\Geo;

use Geocoder\Exception\QuotaExceededException;
use Geocoder\Exception\RuntimeException;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\ProviderInterface;
use Geocoder\HttpAdapter\HttpAdapterInterface;
use Geocoder\Exception\InvalidCredentialsException;
use Geocoder\Exception\NoResultException;
use Geocoder\Exception\UnsupportedException;

class BaiduProvider extends AbstractProvider implements ProviderInterface {

    const IP_ENDPOINT_URL = 'http://api.map.baidu.com/location/ip?ak=%s&coor=bd09ll&ip=%s';

    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'http://api.map.baidu.com/geocoder?output=json&key=%s&address=%s';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'http://api.map.baidu.com/geocoder?output=json&key=%s&location=%F,%F';

    /**
     * @var string
     */
    private $apiKey = null;

    /**
     * @param HttpAdapterInterface $adapter An HTTP adapter.
     * @param string               $apiKey  An API key.
     */
    public function __construct(HttpAdapterInterface $adapter, $apiKey) {
        parent::__construct($adapter);

        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address) {
        if (null === $this->apiKey) {
            throw new InvalidCredentialsException('No API Key provided');
        }

        // This API doesn't handle IPs
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            $query = sprintf(self::IP_ENDPOINT_URL, $this->apiKey, rawurlencode($address));
            return $this->executeIpQuery($query);
        } else {
            $query = sprintf(self::GEOCODE_ENDPOINT_URL, $this->apiKey, rawurlencode($address));
            return $this->executeAddressQuery($query);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getReversedData(array $coordinates) {
        if (null === $this->apiKey) {
            throw new InvalidCredentialsException('No API Key provided');
        }

        $query = sprintf(self::REVERSE_ENDPOINT_URL, $this->apiKey, $coordinates[0], $coordinates[1]);

        return $this->executeAddressQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getName() {
        return 'baidu';
    }

    /**
     * @param string $query
     *
     * @return array
     * @throws NoResultException
     * @throws InvalidCredentialsException
     * @throws RuntimeException
     */
    protected function executeAddressQuery($query) {
        $content = $this->getAdapter()->getContent($query);

        if (null === $content) {
            throw new NoResultException(sprintf('Could not execute query %s', $query));
        }

        $data = (array) json_decode($content, true);

        if (empty($data) || false === $data) {
            throw new NoResultException(sprintf('Could not execute query %s', $query));
        }

        if ('INVALID_KEY' === $data['status']) {
            throw new InvalidCredentialsException('API Key provided is not valid.');
        }

        return array(array_merge($this->getDefaults(), array(
            'latitude'     => isset($data['result']['location']['lat']) ? $data['result']['location']['lat'] : null,
            'longitude'    => isset($data['result']['location']['lng']) ? $data['result']['location']['lng'] : null,
            'streetNumber' => isset($data['result']['addressComponent']['street_number']) ? $data['result']['addressComponent']['street_number'] : null,
            'streetName'   => isset($data['result']['addressComponent']['street']) ? $data['result']['addressComponent']['street'] : null,
            'city'         => isset($data['result']['addressComponent']['city']) ? $data['result']['addressComponent']['city'] : null,
            'cityDistrict' => isset($data['result']['addressComponent']['district']) ? $data['result']['addressComponent']['district'] : null,
            'county'       => isset($data['result']['addressComponent']['province']) ? $data['result']['addressComponent']['province'] : null,
            'countyCode'   => isset($data['result']['cityCode']) ? $data['result']['cityCode'] : null,
        )));
    }

    protected function executeIpQuery($query) {
        $content = $this->getAdapter()->getContent($query);

        if (null === $content) {
            throw new NoResultException(sprintf('Could not execute query %s', $query));
        }

        $data = (array) json_decode($content, true);

        if (empty($data) || false === $data) {
            throw new NoResultException(sprintf('Could not execute query %s', $query));
        }

        if (0 !== $data['status']) {
            if ($data['status'] > 300) {
                throw new QuotaExceededException('quota exceeded limit');
            } else {
                throw new RuntimeException('can not locate the ip');
            }
        }

        return array(array_merge($this->getDefaults(), array(
            'latitude'     => isset($data['content']['point']['y']) ? $data['content']['point']['y'] : null,
            'longitude'    => isset($data['content']['point']['x']) ? $data['content']['point']['x'] : null,
            'streetNumber' => isset($data['content']['address_detail']['street_number']) ? $data['content']['address_detail']['street_number'] : null,
            'streetName'   => isset($data['content']['address_detail']['street']) ? $data['content']['address_detail']['street'] : null,
            'city'         => isset($data['content']['address_detail']['city']) ? $data['content']['address_detail']['city'] : null,
            'cityDistrict' => isset($data['content']['address_detail']['district']) ? $data['content']['address_detail']['district'] : null,
            'county'       => isset($data['content']['address_detail']['province']) ? $data['content']['address_detail']['province'] : null,
            'countyCode'   => isset($data['content']['address_detail']['city_code']) ? $data['content']['address_detail']['city_code'] : null,
        )));
    }
} 