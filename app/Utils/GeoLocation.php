<?php

namespace App\Utils;

use GeoIp2\Database\Reader;

class GeoLocation
{
    protected $reader;

    public function __construct()
    {
        $this->reader = new Reader(storage_path('app/geoip/GeoLite2-City.mmdb'));
    }

    public function getLocation(string $ip): array
    {
        try {
            $record = $this->reader->city($ip);

            return [
                'country' => $record->country->name,
                'city'    => $record->city->name,
                'lat'     => $record->location->latitude,
                'lon'     => $record->location->longitude,
            ];
        } catch (\Exception $e) {
            return [
                'country' => null,
                'city'    => null,
                'lat'     => null,
                'lon'     => null,
            ];
        }
    }
}
