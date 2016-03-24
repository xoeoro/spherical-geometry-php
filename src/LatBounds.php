<?php

namespace Xoeoro\SphericalGeometry;

/**
 * Class LatBounds
 * @package Xoeoro\SphericalGeometry
 * @internal
 */
class LatBounds
{
    protected $_swLat;
    protected $_neLat;

    public function __construct($swLat, $neLat)
    {
        $this->_swLat = $swLat;
        $this->_neLat = $neLat;
    }

    public function getSw()
    {
        return $this->_swLat;
    }

    public function getNe()
    {
        return $this->_neLat;
    }

    public function getMidpoint()
    {
        return ($this->_swLat + $this->_neLat) / 2;
    }

    public function isEmpty()
    {
        return $this->_swLat > $this->_neLat;
    }

    public function intersects($LatBounds)
    {
        return $this->_swLat <= $LatBounds->getSw()
            ? $LatBounds->getSw() <= $this->_neLat && $LatBounds->getSw() <= $LatBounds->getNe()
            : $this->_swLat <= $LatBounds->getNe() && $this->_swLat <= $this->_neLat;
    }

    public function equals($LatBounds)
    {
        return $this->isEmpty()
            ? $LatBounds->isEmpty()
            : abs($LatBounds->getSw() - $this->_swLat)
            + abs($this->_neLat - $LatBounds->getNe())
            <= SphericalGeometry::EQUALS_MARGIN_ERROR;
    }

    public function contains($lat)
    {
        return $lat >= $this->_swLat && $lat <= $this->_neLat;
    }

    public function extend($lat)
    {
        if ($this->isEmpty())
        {
            $this->_neLat = $this->_swLat = $lat;
        }
        else if ($lat < $this->_swLat)
        {
            $this->_swLat = $lat;
        }
        else if ($lat > $this->_neLat)
        {
            $this->_neLat = $lat;
        }
    }
}