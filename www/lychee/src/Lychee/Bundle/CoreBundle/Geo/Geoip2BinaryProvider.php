<?php
namespace Lychee\Bundle\CoreBundle\Geo;


use Geocoder\Exception\RuntimeException;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\ProviderInterface;
use Geocoder\Exception\InvalidArgumentException;
use Geocoder\Exception\NoResultException;
use Geocoder\Exception\UnsupportedException;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

class Geoip2BinaryProvider extends AbstractProvider implements ProviderInterface {
    /**
     * @var string
     */
    protected $datFile;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var int|null
     */
    protected $openFlag;

    /**
     * @param string   $datFile
     *
     * @throws InvalidArgumentException If dat file is not correct (optional).
     */
    public function __construct($datFile) {
        if (false === is_file($datFile)) {
            throw new InvalidArgumentException(sprintf('Given MaxMind dat file "%s" does not exist.', $datFile));
        }

        if (false === is_readable($datFile)) {
            throw new InvalidArgumentException(sprintf('Given MaxMind dat file "%s" does not readable.', $datFile));
        }

        $this->datFile  = $datFile;
        $this->reader = new Reader($datFile, array('zh-cn'));
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address)
    {
        if (false === filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedException('The MaxMindBinaryProvider does not support street addresses.');
        }
        try {
            $record = $this->reader->city($address);
        } catch (AddressNotFoundException $e) {
            throw new NoResultException('not found address for ip '. $address);
        }

//        return array($this->fixEncoding(array_merge($this->getDefaults(), array(
//            'countryCode' => $geoIpRecord->country_code,
//            'country'     => $geoIpRecord->country_name,
//            'region'      => $geoIpRecord->region,
//            'city'        => $geoIpRecord->city,
//            'latitude'    => $geoIpRecord->latitude,
//            'longitude'   => $geoIpRecord->longitude,
//        ))));

        return array(array_merge($this->getDefaults(), array(
            'latitude' => $record->location->latitude,
            'longitude' => $record->location->longitude,
            'city' => $record->city->name,
        )));
    }

    /**
     * {@inheritDoc}
     */
    public function getReversedData(array $coordinates)
    {
        throw new UnsupportedException('The Geoip2BinaryProvider is not able to do reverse geocoding.');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'geoip2';
    }
}