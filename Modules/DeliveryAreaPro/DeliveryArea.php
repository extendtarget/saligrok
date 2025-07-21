<?php

namespace Modules\DeliveryAreaPro;

class DeliveryArea
{

    /**
     * @param $userLat
     * @param $userLng
     * @param $delivery_areas
     * @return boolean
     */
    public function checkArea($userLat, $userLng, $delivery_areas)
    {
        $userLocation = new \Location\Coordinate($userLat, $userLng);

        $check = false;
        foreach ($delivery_areas as $delivery_area) {
            $polygonCount = count(json_decode($delivery_area->geojson)->features);

            for ($i = 0; $i < $polygonCount; $i++) {
                $coordinatesOfSingle = json_decode($delivery_area->geojson)->features[$i]->geometry->coordinates[0];
                $geofence = new \Location\Polygon();
                foreach ($coordinatesOfSingle as $latLng) {
                    $geofence->addPoint(new \Location\Coordinate($latLng[1], $latLng[0]));
                }

                if ($geofence->contains($userLocation)) {
                    $check = true;
                }
            }
        }

        return $check;
    }
}
