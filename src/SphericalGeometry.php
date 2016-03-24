<?php

namespace Xoeoro\SphericalGeometry;

/**
 * Class SphericalGeometry
 * @package Xoeoro\SphericalGeometry
 *
 * Static class SphericalGeometry
 * Utility functions for computing geodesic angles, distances and areas.
 */
class SphericalGeometry
{
    const EQUALS_MARGIN_ERROR = 1.0E-9;

    // Earth's radius (at the Ecuator) of 6378137 meters.
    const EARTH_RADIUS = 6378137;


    public static function getEarthRadius()
    {
        return self::EARTH_RADIUS;
    }

    /**
     * Computes a bounding rectangle (LatLngBounds instance) from a point and a given radius.
     * Reference: http://www.movable-type.co.uk/scripts/latlong-db.html
     *
     *  -------------NE
     * |              |
     * |        radius|
     * |       o------|
     * |              |
     * |              |
     * SW-------------
     *
     * @param object $LatLng
     * @param int|float $radius (In meters)
     */
    public static function computeBounds($LatLng, $radius)
    {
        $latRadiansDistance = $radius / self::EARTH_RADIUS;
        $latDegreesDistance = rad2deg($latRadiansDistance);
        $lngDegreesDistance = rad2deg($latRadiansDistance / cos(deg2rad($LatLng->getLat())));

        // SW point
        $swLat = $LatLng->getLat() - $latDegreesDistance;
        $swLng = $LatLng->getLng() - $lngDegreesDistance;
        $sw = new LatLng($swLat, $swLng);

        // NE point
        $neLat = $LatLng->getLat() + $latDegreesDistance;
        $neLng = $LatLng->getLng() + $lngDegreesDistance;
        $ne = new LatLng($neLat, $neLng);

        return new LatLngBounds($sw, $ne);
    }

    public static function computeHeading($fromLatLng, $toLatLng)
    {
        $fromLat = deg2rad($fromLatLng->getLat());
        $toLat = deg2rad($toLatLng->getLat());
        $lng = deg2rad($toLatLng->getLng()) - deg2rad($fromLatLng->getLng());

        return self::wrapLongitude(rad2deg(atan2(sin($lng) * cos($toLat), cos($fromLat)
            * sin($toLat) - sin($fromLat) * cos($toLat) * cos($lng))));
    }

    public static function computeOffset($fromLatLng, $distance, $heading)
    {
        $distance /= self::EARTH_RADIUS;
        $heading = deg2rad($heading);
        $fromLat = deg2rad($fromLatLng->getLat());
        $cosDistance = cos($distance);
        $sinDistance = sin($distance);
        $sinFromLat = sin($fromLat);
        $cosFromLat = cos($fromLat);
        $sc = $cosDistance * $sinFromLat + $sinDistance * $cosFromLat * cos($heading);

        $lat = rad2deg(asin($sc));
        $lng = rad2deg(deg2rad($fromLatLng->getLng()) + atan2($sinDistance * $cosFromLat
                * sin($heading), $cosDistance - $sinFromLat * $sc));

        return new LatLng($lat, $lng);
    }

    public static function interpolate($fromLatLng, $toLatLng, $fraction)
    {
        $radFromLat = deg2rad($fromLatLng->getLat());
        $radFromLng = deg2rad($fromLatLng->getLng());
        $radToLat = deg2rad($toLatLng->getLat());
        $radToLng = deg2rad($toLatLng->getLng());
        $cosFromLat = cos($radFromLat);
        $cosToLat = cos($radToLat);
        $radDist = self::_computeDistanceInRadiansBetween($fromLatLng, $toLatLng);
        $sinRadDist = sin($radDist);

        if ($sinRadDist < 1.0E-6)
        {
            return new LatLng($fromLatLng->getLat(), $fromLatLng->getLng());
        }

        $a = sin((1 - $fraction) * $radDist) / $sinRadDist;
        $b = sin($fraction * $radDist) / $sinRadDist;
        $c = $a * $cosFromLat * cos($radFromLng) + $b * $cosToLat * cos($radToLng);
        $d = $a * $cosFromLat * sin($radFromLng) + $b * $cosToLat * sin($radToLng);

        $lat = rad2deg(atan2($a * sin($radFromLat) + $b * sin($radToLat), sqrt(pow($c,2) + pow($d,2))));
        $lng = rad2deg(atan2($d, $c));

        return new LatLng($lat, $lng);
    }

    public static function computeDistanceBetween($LatLng1, $LatLng2)
    {
        return self::_computeDistanceInRadiansBetween($LatLng1, $LatLng2) * self::EARTH_RADIUS;
    }

    public static function computeLength($LatLngsArray)
    {
        $length = 0;

        for ($i = 0, $l = count($LatLngsArray) - 1; $i < $l; ++$i)
        {
            $length += self::computeDistanceBetween($LatLngsArray[$i], $LatLngsArray[$i + 1]);
        }

        return $length;
    }

    public static function computeArea($LatLngsArray)
    {
        return abs(self::computeSignedArea($LatLngsArray, false));
    }

    public static function computeSignedArea($LatLngsArray, $signed = true)
    {
        if (empty($LatLngsArray) || count($LatLngsArray) < 3) return 0;

        $e = 0;
        $r2 = pow(self::EARTH_RADIUS, 2);

        for ($i = 1, $l = count($LatLngsArray) - 1; $i < $l; ++$i)
        {
            $e += self::_computeSphericalExcess($LatLngsArray[0], $LatLngsArray[$i], $LatLngsArray[$i + 1], $signed);
        }

        return $e * $r2;
    }

    // Clamp latitude
    public static function clampLatitude($lat)
    {
        return min(max($lat, -90), 90);
    }

    // Wrap longitude
    public static function wrapLongitude($lng)
    {
        return $lng == 180 ? $lng : fmod((fmod(($lng - -180), 360) + 360), 360) + -180;
    }

    /**
     * Computes the great circle distance (in radians) between two points.
     * Uses the Haversine formula.
     */
    protected static function _computeDistanceInRadiansBetween($LatLng1, $LatLng2)
    {
        $p1RadLat = deg2rad($LatLng1->getLat());
        $p1RadLng = deg2rad($LatLng1->getLng());
        $p2RadLat = deg2rad($LatLng2->getLat());
        $p2RadLng = deg2rad($LatLng2->getLng());
        return 2 * asin(sqrt(pow(sin(($p1RadLat - $p2RadLat) / 2), 2) + cos($p1RadLat)
            * cos($p2RadLat) * pow(sin(($p1RadLng - $p2RadLng) / 2), 2)));
    }

    /**
     * Computes the spherical excess.
     * Uses L'Huilier's Theorem.
     */
    protected static function _computeSphericalExcess($LatLng1, $LatLng2, $LatLng3, $signed)
    {
        $latLngsArray = array($LatLng1, $LatLng2, $LatLng3, $LatLng1);
        $distances = array();
        $sumOfDistances = 0;

        for ($i = 0; $i < 3; ++$i)
        {
            $distances[$i] = self::_computeDistanceInRadiansBetween($latLngsArray[$i], $latLngsArray[$i + 1]);
            $sumOfDistances += $distances[$i];
        }

        $semiPerimeter = $sumOfDistances / 2;
        $tan = tan($semiPerimeter / 2);

        for ($i = 0; $i < 3; ++$i)
        {
            $tan *= tan(($semiPerimeter - $distances[$i]) / 2);
        }

        $sphericalExcess = 4 * atan(sqrt(abs($tan)));

        if (!$signed)
        {
            return $sphericalExcess;
        }

        // Negative or positive sign?
        array_pop($latLngsArray);

        $v = array();

        for ($i = 0; $i < 3; ++$i)
        {
            $LatLng = $latLngsArray[$i];
            $lat = deg2rad($LatLng->getLat());
            $lng = deg2rad($LatLng->getLng());

            $v[$i] = array();
            $v[$i][0] = cos($lat) * cos($lng);
            $v[$i][1] = cos($lat) * sin($lng);
            $v[$i][2] = sin($lat);
        }

        $sign = ($v[0][0] * $v[1][1] * $v[2][2]
            + $v[1][0] * $v[2][1] * $v[0][2]
            + $v[2][0] * $v[0][1] * $v[1][2]
            - $v[0][0] * $v[2][1] * $v[1][2]
            - $v[1][0] * $v[0][1] * $v[2][2]
            - $v[2][0] * $v[1][1] * $v[0][2]) > 0 ? 1 : -1;

        return $sphericalExcess * $sign;
    }
}