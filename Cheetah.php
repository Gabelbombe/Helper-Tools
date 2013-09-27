<?php
/**
 * Cheetah
 *
 * Wireless device diagnostics
 *
 * @requires ~
 * @author   Jd Daniel <!@clearwire.com>
 */

class Client_Cheetah
{
    const PROTOCOL_VERSION = 0x00;
    const BSOD = 0x01;
    const CENTER_FREQUENCY = 0x02;
    const BANDWIDTH = 0x03;
    const RSSI_REPORTED = 0x04;
    const RSSI_STD_DEV = 0x05;
    const CINR_REPORTED = 0x06;
    const CINR_STD_DEV = 0x07;
    const TRANSMIT_POWER = 0x08;
    const TRANSMIT_HEADROOM = 0x09;
    const SCANNED_BASE_STATIONS = 0x0A;
    const OPERATIONAL_MODE_TIME = 0x0B;
    const HARQ_RETRIES_TRANSMITTED = 0x0C;
    const HARQ_RETRIES_RECEIVED = 0x0D;
    const INITIAL_RANGING_SUCCESS = 0x0E;
    const INITIAL_RANGING_FAILURES = 0x0F;
    const PERIODIC_RANGING_SUCCESS = 0x10;
    const PERIODIC_RANGING_FAILURES = 0x11;
    const HANDOVER_SUCCESSES = 0x12;
    const HANDOVER_FAILURES = 0x13;
    const MAP_FRAMES_RECEIVED_DECODED = 0x14;
    const MAP_FRAMES_RECEIVED_ERRORS = 0x15;
    const MODULATION_CODING_DOWNLINK = 0x16;
    const MODULATION_CODING_UPLINK = 0x17;
    const TRAFFIC_RATE_DOWNLINK = 0x19;
    const TRAFFIC_RATE_UPLINK = 0x1A;
    const FRAMES_RECEIVED = 0x1B;
    const FRAMES_TRANSMITTED = 0x1C;
    const MAC_STATE = 0x1D;
    const PREAMBLE = 0x1E;
    const HO_LATENCY = 0x1F;
    const FRAME_RATIO = 0x20;
    const HANDOVER_ATTEMPTS = 0x21;
    const NETWORK_ENTRY_LATENCY = 0x22;
    const NETWORK_ENTRY_SUCCESS = 0x23;
    const NETWORK_ENTRY_FAILURES = 0x24;
    const NETWORK_ENTRY_ATTEMPTS = 0x25;
    const USER_ACCESS_TIME = 0x26;
    const RATE_LIMITER_STATS = 0x27;
    const AIRLINK_CONNECT_TIME_ACTIVE = 0x28;
    const AIRLINK_CONNECT_TIME_IDLE = 0x29;
    const AIRLINK_CONNECT_TIME_SLEEP = 0x2A;
    const REBOOT_POWER_DOWN = 0x2B;
    const DEVICE_TEMPERATURE = 0x2C;
    const DEVICE_POWER = 0x2D;
    const BATTERY_CAPACITY_REMAINING = 0x2E;
    const LATITUDE = 0x2F;
    const LONGITUDE = 0x30;
    const ELEVATION = 0x31;
    const ENABLE = 0x34;
    const MAC_ADDRESS_CONTROL_ENABLED = 0x35;
    const MAX_BIT_RATE = 0x36;
    const DUPLEX_MODE = 0x37;
    const LAN_ETHER_MAC_RESULTS = 0x38;
    const DEVICE_UPTIME = 0x39;
    const CQICH = 0x44;
    const DHCP_LEASES = 0x45;
    const PRIMARY_DNS = 0x46;
    const PORT_FORWARDING = 0x47;
    const SERVICE_FLOW = 0x48;
    const SUBNET_MASK = 0x49;
    const WIMAX_INTERFACE_IP_ADDR = 0x4A;
    const ARC_RETRIES_RECEIVED = 0x4B;
    const ARC_RETRIES_TRANSMITTED = 0x4C;
    const AIRLINK_SECURITY = 0x4D;
    const SECONDARY_DNS = 0x4F;
    const GATEWAY = 0x50;
    const CINR_REPORTED_INSTANT = 0x53;
    const RSSI_REPORTED_INSTANT = 0x54;
    const FIRMWARE_VERSION = 0x55;
    const WIMAX_CLIENT_STATE = 0x56;
    const WIMAX_REALM = 0x57;
    const MANUFACTURER = 0x58;
    const MODEL = 0x59;
    const ABNORMAL_DISCONNECTS = 0x5A;
    const PER_CARRIER_PAIR_HANDOFF_COUNTS = 0x5B;
    const LATEST_FIRMWARE_VERSION = 0x100;
    const LATEST_FIRMWARE_FLAG = 0x101;
    const CURRENT_IP_ADDRESS = 0x102;
    const TOWER_ID = 0x103;
    const TOWER_LATITUDE = 0x104;
    const TOWER_LONGITUDE = 0x105;
    const TAM = 0x106;
    const DRMD_CAPABLE = 0x108;

    /**
     * @var array
     */
    protected static $_enumsToFriendlyNames;

    /**
     * Returns diagnostic data for a given MAC ID and search timeframe
     *
     * @param  string               $macAddress
     * @param  Zend_Date            $beginningTime
     * @param  Zend_Date            $endingTime
     * @param  array                $metricKeys    OPTIONAL
     * @return mixed [array | null]
     */
    public static function getDiagnosticsData(
        $macAddress,
        Zend_Date $beginningTime,
        Zend_Date $endingTime,
        array $metricKeys = null
    )
    {
        $configs = Domain_Configuration::getInstance();

#       $macAddress = '00:1E:31:1A:79:2F'; // mac override for testing
        $soapClient = Base_Soap_Client::getSoapClient(
            $configs->getEnvironmentValues('cheetah_wsdl'), // pre-production, use overrider for testing
            null,
            array(
                'soap_version'  => SOAP_1_1,
                'login'         => $configs->getEnvironmentValues('cheetah_username'),
                'password'      => $configs->getEnvironmentValues('cheetah_password'),
            )
        );

        //format mac address
        if (preg_match("/^[a-zA-Z0-9]+$/", $macAddress)) {
            while (strlen($macAddress) > 0)
            {
                $sub = substr($macAddress,0,2);
                $new .= $sub.':';
                $macAddress = substr($macAddress,2,strlen($macAddress));
            }
            $macAddress = substr($new,0,strlen($new)-1);
        }

        $request = array(
            'macAddress'    => $macAddress,
            'startTime'     => $beginningTime->toString('y-MM-dd HH:mm:ss'),
            'endTime'       => $endingTime->toString('y-MM-dd HH:mm:ss'),
        );

        if (!isset(self::$_enumsToFriendlyNames)) {
            self::$_enumsToFriendlyNames = array_flip(Client_Cheetah_Enumerations::$_propertyMapping);
        }

        if (isset($metricKeys)) {
            foreach ($metricKeys as $index => $metricKey) {
                /**
                 * NOTE: The class constants representing all the available metric keys are defined in hexadecimal,
                 *       which PHP normalizes to a numeric data type. The API requires the metric keys to be expressed
                 *       as hexadecimal values in string format.
                 */
                $metricKeys[$index] = dechex(Client_Cheetah_Enumerations::$_propertyMapping[$metricKey]);
            }

            $request['metricKeys'] = implode(',', $metricKeys);
        }

        try {
            $response = Domain_Utility_Array::objectToArray(
                $soapClient->getDiagnosticsData($request)
            );

            $convertedResponse = null;

            if (!isset($response['diagnosticsDataResponse']['errorMessage'])) {
                Base_Logger::getInstance(__CLASS__)->debug("Success Contacting: ". $wsdl);
                Base_Logger::getInstance(__CLASS__)->debug("MAC: {$macAddress}\nSucceeded with response: "
                                                            . print_r($response, 
                                                              true)
                                                           );

                foreach ($response['diagnosticsDataResponse']['metricElement'] as $metricElement) {
                    if (isset($metricElement['metricData'])) {
                        $metricsIterator = new RecursiveArrayIterator($metricElement['metricData']);
                        while ($metricsIterator->valid()) {
                            if ($metricsIterator->hasChildren()) {
                                foreach ($multiMetrics = $metricsIterator->getChildren() as $key => $value) {
                                    if ($dttm = strtotime($multiMetrics['dttm'])) {
                                        $friendlyKeyName = self::$_enumsToFriendlyNames[hexdec($metricElement['key'])];
                                        if (isset($friendlyKeyName)) {
                                            $convertedResponse[$dttm][$friendlyKeyName] = $multiMetrics['value'];
                                        }
                                    }
                                }
                            } else { break; }
                            $metricsIterator->next();
                        }

                        $dttm = strtotime($metricElement['metricData']['dttm']);

                        if ($dttm) {
                            $friendlyKeyName = self::$_enumsToFriendlyNames[hexdec($metricElement['key'])];

                            if (isset($friendlyKeyName)) {
                                $convertedResponse[$dttm][$friendlyKeyName] = $metricElement['metricData']['value'];
                            }
                        }
                    }
                }
            } else {
                Base_Logger::getInstance(__CLASS__)->debug("Error Contacting: ". $wsdl);
                Base_Logger::getInstance(__CLASS__)->debug("MAC: {$macAddress}\nFailed with error: " 
                                                            . print_r($response['diagnosticsDataResponse']['errorMessage'], 
                                                              true)
                                                          );
            }

            return $convertedResponse;
        } catch (Base_Soap_Exception $exception) {
            Base_Logger::getInstance(__CLASS__)->err($exception->getMessage());
            $response = null;
        }
        return $response;
    }
}
