<?php

namespace Modules\ThermalPrinter\Entities;

use App\Order;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Mike42\Escpos\Printer;
use Modules\ThermalPrinter\Entities\Escpos;
use Modules\ThermalPrinter\Entities\PrinterSetting;

class ThermalPrinter extends Exception
{
    /**
     * @param $order_id
     */
    public function printInvoice($order_id)
    {
        //admin data used for special cases
        $adminSettings = PrinterSetting::where('user_id', '1')->first();
        $adminData = json_decode($adminSettings->data);

        $printerSetting = PrinterSetting::where('user_id', Auth::user()->id)->first();

        if ($printerSetting) {
            //if data exists take auth user data...
            $data = json_decode($printerSetting->data);
        } else {
            //else take admin data...
            $data = json_decode($adminSettings->data);
        }

        //48 for 3 inch, 30 for 2 inch
        if ($data->print_width == 3) {
            $char_per_line = 48;
        } else {
            $char_per_line = 30;
        }

        try {
            $main = new Escpos();
            $main->load($data->connector_type, $data->connector_descriptor);
        } catch (Exception $e) {
            throw new \Exception('Printing Failed. Connection could not be established.');
        }

        $order = Order::where('unique_order_id', $order_id)->firstOrFail();

        $store_name = $order->restaurant->name;
        $store_address = $order->restaurant->address;
        $order_id = $order->unique_order_id;

        //init store header
        $main->printer->setJustification(Printer::JUSTIFY_CENTER);

        if (!empty($adminData->invoice_title)) {
            $main->printer->feed();
            $main->printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $main->printer->text($adminData->invoice_title);
            $main->printer->selectPrintMode();
            $main->printer->feed();
        }

        if (!empty($adminData->invoice_subtitle)) {
            $main->printer->selectPrintMode(Printer::MODE_FONT_B);
            $main->printer->text($adminData->invoice_subtitle);
            $main->printer->selectPrintMode();
            $main->printer->feed();
        }

        if (!empty($adminData->invoice_title) || !empty($adminData->invoice_subtitle)) {
            $main->printer->text($this->drawLine($char_per_line));
        }

        $main->printer->feed();

        if (!empty($data->show_store_name)) {
            $main->printer->setEmphasis(true);
            $main->printer->setUnderline(1);
            $main->printer->text($store_name);
            $main->printer->setUnderline(0);
            $main->printer->setEmphasis(false);
            $main->printer->feed();
        }

        if (!empty($data->show_store_address)) {
            $main->printer->text($store_address);
            $main->printer->feed();
        }

        if (!empty($data->show_order_id)) {
            $main->printer->text(!empty($data->order_id_label) ? $data->order_id_label . ' ' . $order_id : 'Order ID: ' . $order_id);
            $main->printer->feed();
        }

        if (!empty($data->show_order_date)) {
            $main->printer->text(!empty($data->order_date_label) ? $data->order_date_label . ' ' . $order->created_at->format('Y-m-d h:i A') : 'Ordered Date: ' . $order->created_at->format('Y-m-d h:i A'));
            $main->printer->feed();
        }

        $main->printer->feed();

        /* Customer Details */
        if (!empty($data->customer_details_title)) {
            $main->printer->setEmphasis(true);
            $main->printer->setUnderline(1);
            $main->printer->text('Customer Details');
            $main->printer->setUnderline(0);
            $main->printer->setEmphasis(false);
            $main->printer->feed();
        }

        if (!empty($data->show_customer_name)) {
            $main->printer->text($order->user->name);
            $main->printer->feed();
        }

        if (!empty($data->show_customer_phone)) {
            $main->printer->text($order->user->phone);
            $main->printer->feed();
        }

        if (!empty($data->show_delivery_type)) {
            $main->printer->setEmphasis(true);
            //delivery order
            if ($order->delivery_type == 1) {
                $main->printer->text(empty($data->delivery_label) ? 'DELIVERY' : $data->delivery_label);
            } else {
                //selfpickup order
                $main->printer->text(empty($data->selfpickup_label) ? 'SELFPICKUP' : $data->selfpickup_label);
            }
            $main->printer->feed();
        }

        if (!empty($data->show_delivery_address) && $order->delivery_type == 1) {
            $main->printer->text($order->address);
            $main->printer->feed();
        }

        $main->printer->setEmphasis(false);
        $main->printer->feed();
        /* END Customer Details */

        $main->printer->setJustification();

        $main->printer->setJustification(Printer::JUSTIFY_LEFT);

        //bill item header
        $main->printer->text($this->drawLine($char_per_line));
        $string = $this->columnify($this->columnify($this->columnify(!empty($data->quantity_label) ? $data->quantity_label : 'QTY', ' ' . !empty($data->item_label) ? $data->item_label : 'ITEMS', 12, 40, 0, 0, $char_per_line), !empty($data->price_label) ? $data->price_label : 'PRICE', 55, 20, 0, 0, $char_per_line), ' ' . !empty($data->total_label) ? $data->total_label : 'TOTAL', 75, 25, 0, 0, $char_per_line);
        $main->printer->setEmphasis(true);
        $main->printer->text(rtrim($string));
        $main->printer->feed();
        $main->printer->setEmphasis(false);
        $main->printer->text($this->drawLine($char_per_line));

        foreach ($order->orderitems as $orderitem) {

            //calculating item total
            $itemTotal = ($orderitem->price + $this->calculateAddonTotal($orderitem->order_item_addons)) * $orderitem->quantity;

            //get addons and add to orderitem->addon_name
            $orderItemAddons = count($orderitem->order_item_addons);
            if ($orderItemAddons > 0) {
                $addons = '';
                foreach ($orderitem->order_item_addons as $addon) {
                    $addons .= $addon->addon_name . ', ';
                }
                $addons = rtrim($addons, ', ');
                $orderitem->addon_name = $addons;
            }

            //print products/items
            if ($orderItemAddons > 0) {
                $string = rtrim($this->columnify($this->columnify($this->columnify($orderitem->quantity, $orderitem->name . ' (' . $orderitem->addon_name . ')', 12, 40, 0, 0, $char_per_line), floatval($orderitem->price), 55, 20, 0, 0, $char_per_line), floatval($itemTotal), 75, 25, 0, 0, $char_per_line));
            } else {
                $string = rtrim($this->columnify($this->columnify($this->columnify($orderitem->quantity, $orderitem->name, 12, 40, 0, 0, $char_per_line), floatval($orderitem->price), 55, 20, 0, 0, $char_per_line), floatval($itemTotal), 75, 25, 0, 0, $char_per_line));
            }

            $main->printer->text($string);
            $main->printer->feed(1);

        }

        $main->printer->feed();
        $main->printer->text($this->drawLine($char_per_line));

        $main->printer->setJustification(Printer::JUSTIFY_LEFT);

        //coupon
        if ($order->coupon_name != null) {
            $coupon = $this->columnify($data->coupon_label . ' ', $order->coupon_name, 75, 25, 0, 0, $char_per_line);
            $main->printer->text(rtrim($coupon));
            $main->printer->feed();
        }

        //store charge
        $storeCharge = $this->columnify($data->store_charge_label . ' ', floatval($order->restaurant_charge), 75, 25, 0, 0, $char_per_line);
        $main->printer->text(rtrim($storeCharge));
        $main->printer->feed();

        //delivery charge
        $deliveryCharge = $this->columnify($data->delivery_charge_label . ' ', floatval($order->delivery_charge), 75, 25, 0, 0, $char_per_line);
        $main->printer->text(rtrim($deliveryCharge));
        $main->printer->feed();

        //Tax
        if ($order->tax != null) {
            $tax = $this->columnify($data->tax_label . ' ', $order->tax . '%', 75, 25, 0, 0, $char_per_line);
            $main->printer->text(rtrim($tax));
            $main->printer->feed();
        }

        //Order Total

        $main->printer->setJustification(Printer::JUSTIFY_CENTER);
        $main->printer->text($this->drawLine($char_per_line));
        $main->printer->setJustification();

        $orderTotal = $this->columnify($data->total_label . ' ', floatval($order->total), 75, 25, 0, 0, $char_per_line);
        $main->printer->setEmphasis(true);
        $main->printer->text(rtrim($orderTotal));
        $main->printer->setEmphasis(false);
        $main->printer->feed();

        $main->printer->setJustification();

        $main->printer->setJustification(Printer::JUSTIFY_CENTER);
        $main->printer->text($this->drawLine($char_per_line));
        $main->printer->setJustification();

        //admin footer
        if (!empty($adminData->footer_title)) {
            $main->printer->setJustification(Printer::JUSTIFY_CENTER);
            $main->printer->feed();
            $main->printer->setUnderline(1);
            $main->printer->text($adminData->footer_title);
            $main->printer->setUnderline(0);
            $main->printer->feed();
            $main->printer->setJustification();
        }

        if (!empty($adminData->footer_sub_title)) {
            //break lines in new array
            $subFooters = preg_split("/\r\n|\n|\r/", $adminData->footer_sub_title);

            $main->printer->setJustification(Printer::JUSTIFY_LEFT);
            $main->printer->feed();
            foreach ($subFooters as $subFooter) {
                $main->printer->text($subFooter);
                $main->printer->feed();
            }
            $main->printer->setJustification();
        }

        $main->printer->feed();

        //store footer
        if (!empty($data->store_footer_title)) {
            $main->printer->setJustification(Printer::JUSTIFY_CENTER);
            $main->printer->feed();
            $main->printer->setUnderline(1);
            $main->printer->text($data->store_footer_title);
            $main->printer->setUnderline(0);
            $main->printer->feed();
            $main->printer->setJustification();
        }

        if (!empty($data->store_footer_subtitle)) {
            //break lines in new array
            $subFooters = preg_split("/\r\n|\n|\r/", $data->store_footer_subtitle);

            $main->printer->setJustification(Printer::JUSTIFY_LEFT);
            $main->printer->feed();
            foreach ($subFooters as $subFooter) {
                $main->printer->text($subFooter);
                $main->printer->feed();
            }
            $main->printer->setJustification();
        }

        $main->printer->feed();

        //cut and close connection for printing
        $main->printer->cut();
        $main->printer->close();

    }

    /**
     * @param $char_per_line
     * @return mixed
     */
    public function drawLine($char_per_line)
    {
        $new = '';
        for ($i = 1; $i < $char_per_line; $i++) {
            $new .= '-';
        }
        return $new . "\n";
    }

    /**
     * @param $addons
     * @return mixed
     */
    public function calculateAddonTotal($addons)
    {
        $total = 0;
        foreach ($addons as $addon) {
            $total += $addon->addon_price;
        }
        return $total;
    }

    /**
     * @param $leftCol
     * @param $rightCol
     * @param $leftWidthPercent
     * @param $rightWidthPercent
     * @param $space
     * @param $remove_for_space
     * @param $char_per_line
     */
    public function columnify($leftCol, $rightCol, $leftWidthPercent, $rightWidthPercent, $space = 2, $remove_for_space = 0, $char_per_line)
    {
        $char_per_line = $char_per_line - $remove_for_space;

        $leftWidth = $char_per_line * $leftWidthPercent / 100;
        $rightWidth = $char_per_line * $rightWidthPercent / 100;

        $leftWrapped = wordwrap($leftCol, $leftWidth, "\n", true);
        $rightWrapped = wordwrap($rightCol, $rightWidth, "\n", true);

        $leftLines = explode("\n", $leftWrapped);
        $rightLines = explode("\n", $rightWrapped);
        $allLines = array();
        for ($i = 0; $i < max(count($leftLines), count($rightLines)); $i++) {
            $leftPart = str_pad(isset($leftLines[$i]) ? $leftLines[$i] : '', $leftWidth, ' ');
            $rightPart = str_pad(isset($rightLines[$i]) ? $rightLines[$i] : '', $rightWidth, ' ');
            $allLines[] = $leftPart . str_repeat(' ', $space) . $rightPart;
        }
        return implode($allLines, "\n") . "\n";
    }

}
