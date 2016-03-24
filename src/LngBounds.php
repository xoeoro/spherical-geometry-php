<?php

namespace Xoeoro\SphericalGeometry;

/**
 * Class LngBounds
 * @package Xoeoro\SphericalGeometry
 * @internal
 */
class LngBounds
{
    protected $_swLng;
    protected $_neLng;

    public function __construct($swLng, $neLng)
    {
        $swLng = $swLng == -180 && $neLng != 180 ? 180 : $swLng;
        $neLng = $neLng == -180 && $swLng != 180 ? 180 : $neLng;

        $this->_swLng = $swLng;
        $this->_neLng = $neLng;
    }

    public function getSw()
    {
        return $this->_swLng;
    }

    public function getNe()
    {
        return $this->_neLng;
    }

    public function getMidpoint()
    {
        $midPoint = ($this->_swLng + $this->_neLng) / 2;

        if ($this->_swLng > $this->_neLng)
        {
            $midPoint = SphericalGeometry::wrapLongitude($midPoint + 180);
        }

        return $midPoint;
    }

    public function isEmpty()
    {
        return $this->_swLng - $this->_neLng == 360;
    }

    public function intersects($LngBounds)
    {
        if ($this->isEmpty() || $LngBounds->isEmpty())
        {
            return false;
        }
        else if ($this->_swLng > $this->_neLng)
        {
            return $LngBounds->getSw() > $LngBounds->getNe()
            || $LngBounds->getSw() <= $this->_neLng
            || $LngBounds->getNe() >= $this->_swLng;
        }
        else if ($LngBounds->getSw() > $LngBounds->getNe())
        {
            return $LngBounds->getSw() <= $this->_neLng || $LngBounds->getNe() >= $this->_swLng;
        }
        else
        {
            return $LngBounds->getSw() <= $this->_neLng && $LngBounds->getNe() >= $this->_swLng;
        }
    }

    public function equals($LngBounds)
    {
        return $this->isEmpty()
            ? $LngBounds->isEmpty()
            : fmod(abs($LngBounds->getSw() - $this->_swLng), 360)
            + fmod(abs($LngBounds->getNe() - $this->_neLng), 360)
            <= SphericalGeometry::EQUALS_MARGIN_ERROR;
    }

    public function contains($lng)
    {
        $lng = $lng == -180 ? 180 : $lng;

        return $this->_swLng > $this->_neLng
            ? ($lng >= $this->_swLng || $lng <= $this->_neLng) && !$this->isEmpty()
            : $lng >= $this->_swLng && $lng <= $this->_neLng;
    }

    public function extend($lng)
    {
        if ($this->contains($lng))
        {
            return;
        }

        if ($this->isEmpty())
        {
            $this->_swLng = $this->_neLng = $lng;
        }
        else
        {
            $swLng = $this->_swLng - $lng;
            $swLng = $swLng >= 0 ? $swLng : $this->_swLng + 180 - ($lng - 180);
            $neLng = $lng - $this->_neLng;
            $neLng = $neLng >= 0 ? $neLng : $lng + 180 - ($this->_neLng - 180);

            if ($swLng < $neLng)
            {
                $this->_swLng = $lng;
            }
            else
            {
                $this->_neLng = $lng;
            }
        }
    }
}