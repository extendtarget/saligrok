<?php

namespace Modules\ThermalPrinter\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\ThermalPrinter\Entities\PrinterSetting;

class SeedAdminSettingsForThermalPrinterTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        if (\Schema::hasTable('printer_settings')) {
            $adminSettingCheck = PrinterSetting::first();

            //if no record exists, then insert the admin setting json for thermal printer
            if (!$adminSettingCheck) {
                $printerSetting = new PrinterSetting();
                $printerSetting->user_id = 1;
                $printerSetting->data = '{"connector_type":"network","connector_descriptor":"192.168.1.2","print_width":"3","print_kot":"false","automatic_printing":"OFF","invoice_title":"Foodomaa!!!","invoice_subtitle":"Order Food Online","show_store_name":"true","show_store_address":"true","show_order_id":"true","show_order_date":"true","order_id_label":"Order ID:","order_date_label":"Order Date:","customer_details_title":"Customer Details","show_customer_name":"true","show_customer_phone":"true","show_delivery_type":"true","delivery_label":"Delivery Order","selfpickup_label":"TakeAway Order","show_delivery_address":"true","quantity_label":"QTY","item_label":"ITEMS","price_label":"PRICE","total_label":"TOTAL","store_charge_label":"Store Charges:","delivery_charge_label":"Delivery Charges:","tax_label":"Tax","coupon_label":"Coupon","footer_title":"Terms and Conditions","footer_sub_title":"1. Have a nice day.\r\n2. Take Care.\r\n3. Bye Bye."}';
                $printerSetting->save();
            }
        }
    }
}
