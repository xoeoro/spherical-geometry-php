<?php

namespace Xoeoro\SphericalGeometry;

/**
 * Class LatLng
 * @package Xoeoro\SphericalGeometry
 */
class LatLng
{
    protected $_lat;
    protected $_lng;

    public function __construct($lat, $lng, $noWrap = false)
    {
        $lat = (float) $lat;
        $lng = (float) $lng;

        if (is_nan($lat) || is_nan($lng))
        {
            trigger_error('LatLng class -> Invalid float numbers: ('. $lat .', '. $lng .')', E_USER_ERROR);
        }

        if ($noWrap === false)
        {
            $lat = SphericalGeometry::clampLatitude($lat);
            $lng = SphericalGeometry::wrapLongitude($lng);
        }

        $this->_lat = $lat;
        $this->_lng = $lng;
    }

    public function getLat()
    {
        return $this->_lat;
    }

    public function getLng()
    {
        return $this->_lng;
    }

    public function equals($LatLng)
    {
        if (!is_object($LatLng) || !($LatLng instanceof self))
        {
            return false;
        }

        return abs($this->_lat - $LatLng->getLat()) <= SphericalGeometry::EQUALS_MARGIN_ERROR
        && abs($this->_lng - $LatLng->getLng()) <= SphericalGeometry::EQUALS_MARGIN_ERROR;
    }

    public function toString()
    {
        return '('. $this->_lat .', '. $this->_lng .')';
    }

    public function toUrlValue($precision = 6)
    {
        $precision = (int) $precision;
        return round($this->_lat, $precision) .','. round($this->_lng, $precision);
    }
}