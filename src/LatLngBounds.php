<?php

namespace Xoeoro\SphericalGeometry;

/**
 * Class LatLngBounds
 * @package Xoeoro\SphericalGeometry
 */
class LatLngBounds
{
    protected $_LatBounds;
    protected $_LngBounds;

    /**
     * $LatLngSw South West LatLng object
     * $LatLngNe North East LatLng object
     */
    public function __construct($LatLngSw = null, $LatLngNe = null)
    {
        if ((!is_null($LatLngSw) && !($LatLngSw instanceof LatLng))
            || (!is_null($LatLngNe) && !($LatLngNe instanceof LatLng)))
        {
            trigger_error('LatLngBounds class -> Invalid LatLng object.', E_USER_ERROR);
        }

        if ($LatLngSw)
        {
            $LatLngNe = !$LatLngNe ? $LatLngSw : $LatLngNe;
            $sw = SphericalGeometry::clampLatitude($LatLngSw->getLat());
            $ne = SphericalGeometry::clampLatitude($LatLngNe->getLat());
            $this->_LatBounds = new LatBounds($sw, $ne);

            $sw = $LatLngSw->getLng();
            $ne = $LatLngNe->getLng();

            if (360 <= $ne - $sw)
            {
                $this->_LngBounds = new LngBounds(-180, 180);
            }
            else
            {
                $sw = SphericalGeometry::wrapLongitude($sw);
                $ne = SphericalGeometry::wrapLongitude($ne);
                $this->_LngBounds = new LngBounds($sw, $ne);
            }
        }
        else
        {
            $this->_LatBounds = new LatBounds(1, -1);
            $this->_LngBounds = new LngBounds(180, -180);
        }
    }

    public function getLatBounds()
    {
        return $this->_LatBounds;
    }

    public function getLngBounds()
    {
        return $this->_LngBounds;
    }

    public function getCenter()
    {
        return new LatLng($this->_LatBounds->getMidpoint(), $this->_LngBounds->getMidpoint());
    }

    public function isEmpty()
    {
        return $this->_LatBounds->isEmpty() || $this->_LngBounds->isEmpty();
    }

    public function getSouthWest()
    {
        return new LatLng($this->_LatBounds->getSw(), $this->_LngBounds->getSw(), true);
    }

    public function getNorthEast()
    {
        return new LatLng($this->_LatBounds->getNe(), $this->_LngBounds->getNe(), true);
    }

    public function toSpan()
    {
        $lat = $this->_LatBounds->isEmpty() ? 0 : $this->_LatBounds->getNe() - $this->_LatBounds->getSw();
        $lng = $this->_LngBounds->isEmpty()
            ? 0
            : ($this->_LngBounds->getSw() > $this->_LngBounds->getNe()
                ? 360 - ($this->_LngBounds->getSw() - $this->_LngBounds->getNe())
                : $this->_LngBounds->getNe() - $this->_LngBounds->getSw());

        return new LatLng($lat, $lng, true);
    }

    public function toString()
    {
        return '('. $this->getSouthWest()->toString() .', '. $this->getNorthEast()->toString() .')';
    }

    public function toUrlValue($precision = 6)
    {
        return $this->getSouthWest()->toUrlValue($precision) .','.
        $this->getNorthEast()->toUrlValue($precision);
    }

    public function equals($LatLngBounds)
    {
        return !$LatLngBounds
            ? false
            : $this->_LatBounds->equals($LatLngBounds->getLatBounds())
            && $this->_LngBounds->equals($LatLngBounds->getLngBounds());
    }

    public function intersects($LatLngBounds)
    {
        return $this->_LatBounds->intersects($LatLngBounds->getLatBounds())
        && $this->_LngBounds->intersects($LatLngBounds->getLngBounds());
    }

    public function union($LatLngBounds)
    {
        $this->extend($LatLngBounds->getSouthWest());
        $this->extend($LatLngBounds->getNorthEast());
        return $this;
    }

    public function contains($LatLng)
    {
        return $this->_LatBounds->contains($LatLng->getLat())
        && $this->_LngBounds->contains($LatLng->getLng());
    }

    public function extend($LatLng)
    {
        $this->_LatBounds->extend($LatLng->getLat());
        $this->_LngBounds->extend($LatLng->getLng());
        return $this;
    }
}