<?php
/**
 * Map helper model
 *
 * Handles requests made to /my-account/mapdata.
 *
 * @requires Zend_View_Helper_Abstract
 * @author   Jd Daniel <!@clearwire.com>
 */
class Domain_View_Helper_Map extends Zend_View_Helper_Abstract
{
    const DEFAULT_LAT               = 39.707187;
    const DEFAULT_LNG               = -96.064453;

    const DEFAULT_VIEW_FILE         = '_map.phtml';
    const DEFAULT_VIEW_FILE_DIALOG  = '_map-dialog.phtml';

    const ZOOM_LEVEL_MIN            = 4;  // view before prequal
    const ZOOM_LEVEL_CITY           = 12; // city icons
    const ZOOM_LEVEL_ZIP            = 13; // search by zip code
    const ZOOM_LEVEL_MAX            = 14; // view after prequal

    private $_prequalification;

    private $_params;
    private $_options = array(
        'width' => 0, // Setting these to 0 will cause the map to fall back to CSS-based width/height
        'height' => 0,
        'showPin' => false,
    );

    /**
     * Initializes the view and Domains the parameters and prequalification resources
     * 
     * @param Zend_View_Interface $view The new view
     * @return Domain_View_Helper_Map
     */
    public function setView(Zend_View_Interface $view)
    {
        parent::setView($view);
        $this->_params = null;
        $this->_prequalification = null;
        return $this;
    }

    /**
     * Sets arbitrary parameters into the _params array
     * 
     * @param array $params The parameters to set, currently supports:
     *      width (int)
     *      height (int)
     *      marker (bool)
     * @return Domain_View_Helper_Map
     */
    public function setOptions(array $params = array())
    {
        $width = (isset($params['width']) && $params['width'] > 0)
            ? (int) $params['width']
            : $this->_options['width'];

        $height = (isset($params['height']) && $params['height'] > 0)
            ? (int) $params['height']
            : $this->_options['height'];

        $showPin = (isset($params['showPin']))
            ? ($params['showPin'] == true)
            : $this->_options['showPin'];

        $this->_options = array(
            'width' => $width,
            'height' => $height,
            'showPin' => $showPin,
        );

        return $this;
    }

    /**
     * Gets this object ready to render - it sets map options and parameters, and prepares the object for the
     * eventual call to __toString.  Usage:  echo $this->map(...);
     * 
     * @param array $displayTileTypes (optional) Which layers to include in this map
     * @param Base_Prequalification (optional) $prequalification The prequal object - our address, lat/long, etc
     * @param int $zoom (optional) The default zoom level
     * @return Domain_View_Helper_Map
     */
    public function map(array $displayTileTypes = array(), Base_Prequalification $prequalification = null, $zoom = null)
    {
        if (isset($this->_params)) {
            if (func_num_args()) {
                Base_Logger::getInstance(__CLASS__)->err('Map parameters may only be set once');
            }
            return $this;
        }
        $address = null;
        $marketName = null;

        $latitude = self::DEFAULT_LAT;
        $longitude = self::DEFAULT_LNG;

        if (isset($prequalification)) {
            $this->_prequalification = $prequalification;
            $address = $prequalification->getAddress();
            $market = new Market($prequalification->getMarketId());
            $marketName = $market->getMarketName();

            if ($address->getLatitude() && $address->getLongitude()) {
                $latitude = $address->getLatitude();
                $longitude = $address->getLongitude();
            } else if ($prequalification->getLatitude() && $prequalification->getLongitude()) {
                $latitude = $prequalification->getLatitude();
                $longitude = $prequalification->getLongitude();
            }
        }

        $tileServers = explode(',', cstCOV_TILE_SERVER_NAME);
        $tileServer0 = 'http://' . trim($tileServers[0]);
        $tileServer1 = 'http://' . trim((isset($tileServers[1]) ? $tileServers[1] : $tileServers[0]));
 
        if (!isset($zoom)) {
            $zoom = isset($address) ? self::ZOOM_LEVEL_MAX : self::ZOOM_LEVEL_MIN;
        }

        $secure = Zend_Controller_Front::getInstance()->getRequest()->isSecure();

        $this->_params = array(
            'sensor'    => false,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'zoom'      => $zoom,
            'minZoom'   => self::ZOOM_LEVEL_MIN,
            'maxZoom'   => self::ZOOM_LEVEL_MAX,
            'tiles'     => array(
                'home' => array(
                    'url'       => ($secure
                        ? '/coverage/tiles/home'
                        : "{$tileServer0}/CovCurHomeMap/RESTService/getTile?name=CovCurHome"
                    ),
                    'opacity'   => 0.6,
                ),
                'mobile' => array(
                    'url'       => ($secure
                        ? '/coverage/tiles/mobile'
                        : "{$tileServer1}/CovCurHomeMap/RESTService/getTile?name=CovCurMobile"
                    ),
                    'opacity'   => 0.4,
                ),
                'threeG' => array(
                    'url'       => ($secure
                        ? '/coverage/tiles/roaming'
                        : "{$tileServer0}/RoamingMap/RESTService/getTile?name=Roaming"
                    ),
                    'opacity'   =>  0.4,
                ),
                'future' => array(
                    'url'       => ($secure
                        ? '/coverage/tiles/future'
                        : "{$tileServer0}/CovFutureMap/RESTService/getTile?name=CovFuture"
                    ),
                    'opacity'   => 0.4,
                ),
                'tower' => array(
                    'url'       => ($secure
                        ? '/coverage/tiles/tower'
                        : "{$tileServer1}/SitesMap/RESTService/getTile?name=Sites"
                    ),
                    'opacity'   => 1,
                    'minZoom'   => self::ZOOM_LEVEL_CITY,
                ),
            ),
            'markers'   => array(
                'prequal'   => '/public/images/coverage-map/pointer-prequal.png',
                'pointer'   => '/public/images/coverage-map/pointer.png',
            ),
            'text'      => array(
                'copyright' => 'Coverage data &amp; imagery &copy; ' . date('Y') . ' Domainwire',
            ),
            'overlays'  => $displayTileTypes,
        );

        if (isset($marketName)) {
            $host = Domain_Configuration::getInstance()->getEnvironmentValues('test_environment')
                ? Domain_Configuration::getApplication() . '.' . Domain_Configuration::getDomain()
                : HOST_DOMAIN;

            $this->_params['tiles']['sectors'] = array(
                'kml' => 'https://' . $host . '/my-account/mapdata/kml-gateway' .
                    '?location=' . urlencode($marketName) . '&style=sectors',
            );
        }

        return $this;
    }

    public function __toString()
    {
        $this->view->headLink()->appendStylesheet('/public/css/coverage.css', 'all');
        $this->view->headScript()->appendFile('https://www.google.com/jsapi');
        $this->view->headScript()->appendFile('/coverage/jsapi');
        $this->view->headScript()->appendScript('Map.setParams(' . Zend_Json::encode($this->_params) . ');');

        // Figure out if we're at a zip-only prequal zoom level
        $searchZip = null;
        if (isset($this->_prequalification)) {
            $address = $this->_prequalification->getAddress();
            if (!$address->getStreet()) {
                $searchZip = $address->getZip();
            }
        }

        // Render and return the partial
        return $this->view->partial(
            self::DEFAULT_VIEW_FILE,
            array(
                'searchZip' => isset($searchZip) ? Zend_Json::encode($searchZip) : 'void(0)',
                'latitude' => Zend_Json::encode($this->_params['latitude']),
                'longitude' => Zend_Json::encode($this->_params['longitude']),
                'width' => Zend_Json::encode($this->_options['width']),
                'height' => Zend_Json::encode($this->_options['height']),
                'showPin' => Zend_Json::encode($this->_options['showPin']),
                'zoomLevelCity' => Zend_Json::encode(self::ZOOM_LEVEL_CITY),
                'zoomLevelZip' => Zend_Json::encode(self::ZOOM_LEVEL_ZIP),
                'zoomLevelStreet' => Zend_Json::encode(self::ZOOM_LEVEL_MAX),
                'mapCoverage' => MarketMetadata::getInstance()->getCoverage(),
                'dialogWindow' => Zend_Json::encode($this->view->partial(self::DEFAULT_VIEW_FILE_DIALOG)),
            )
        );
    }
}