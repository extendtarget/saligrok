<?php

namespace Modules\ThermalPrinter\Entities;

use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class Escpos
{
    public $printer;

    public function load($connector_type, $connector_descriptor, $connector_port = 9100)
    {
        switch (strtolower($connector_type)) {
            case 'cups':
                $connector = new CupsPrintConnector($connector_descriptor);
                break;
            case 'windows':
                $connector = new WindowsPrintConnector($connector_descriptor);
                break;
            case 'network':
                set_time_limit(30);
                $connector = new NetworkPrintConnector($connector_descriptor);
                break;
        }

        if ($connector) {
            // Load simple printer profile
            $profile = CapabilityProfile::load('default');
            // Connect to printer
            $this->printer = new Printer($connector, $profile);
        } else {
            throw new \Exception('Invalid printer connector type. Accepted values are: cups');
        }
    }
}
