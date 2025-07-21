<?php

namespace Modules\Wazone\Http\Controllers;

use App\Order;
use App\User;
use Illuminate\Routing\Controller;
use Modules\Wazone\Http\Controllers\WazoneController;

class MessageController extends Controller {

    public function testMessage($phone) {
        $message = 'Test Message from Wazone Settings';
        $con = new WazoneController();
        $response = $con->wazone($phone, $message);
        if (empty($response['success'])) {
            return redirect()->back()->with(['message' => 'Send message failed!']);
        } else {
            return redirect()->back()->with(['success' => 'Message sent successfully!']);
        }
    }

    public function send($target, $phone, $param) {
        $con = new WazoneController();
        $message = $this->process($target, $param);
        $response = $con->wazone($phone, $message);
        return $response;
    }

    private function process($target, $param) {
        $filename = \Module::getModulePath('Wazone') . 'message_templates.json';
        if (!file_exists($filename)) {
            exit('file message_templates.json not found');
        }

        $data = json_decode(file_get_contents($filename), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            exit('Invalid JSON in message_templates.json');
        }

        $message = '';
        switch ($target) {
            case 'OTP':
                $msg = $data['msg_otp'] ?? '';
                $message = str_replace('{otp}', $param['otp'] ?? '', $msg);
                return $message;
            case 'STATUS2':
            case 'STATUS3':
            case 'STATUS4':
            case 'STATUS5':
            case 'STATUS6':
            case 'STATUS7':
            case 'STATUS8':
            case 'STATUS9':
            case 'STATUS10':
            case 'STATUS11':
                $is_guest = isset($param['customer_email']) && str_ends_with($param['customer_email'], '@app.sali-star.com');
                
                if ($target === 'STATUS4' && $is_guest && isset($data['msg_guest_status4'])) {
                    $msg = $data['msg_guest_status4'];
                } elseif ($target === 'STATUS5' && $is_guest && isset($data['msg_guest_status5'])) {
                    $msg = $data['msg_guest_status5'];
                } else {
                    $msg = $data['msg_' . strtolower($target)] ?? '';
                }

                // Handle payment-related parameters
                if (in_array($target, ['STATUS4']) && isset($param['payment_mode'])) {
                    $total = floatval($param['total'] ?? 0);
                    $payment_mode = strtoupper($param['payment_mode']);
                    $wallet_amount = floatval($param['wallet_amount'] ?? 0);

                    // Detect partial payment based on wallet_amount
                    if ($wallet_amount > 0 && $wallet_amount <= $total) {
                        // Partial payment detected
                        $effective_payment_mode = 'PARTIAL';
                        if (!isset($param['wallet_balance']) || $param['wallet_balance'] === '') {
                            $user = isset($param['customer_id']) ? User::find($param['customer_id']) : null;
                            $wallet_balance = $user ? $user->balanceFloat : 0;
                        } else {
                            $wallet_balance = floatval($param['wallet_balance'] ?? 0);
                        }
                        $remaining_amount = $total - $wallet_amount;

                        // Format values to two decimal places
                        $param['wallet_amount'] = number_format($wallet_amount, 2, '.', '');
                        $param['total'] = number_format($total, 2, '.', '');
                        $param['wallet_balance'] = number_format($wallet_balance, 2, '.', '');
                        $param['remaining_amount'] = number_format($remaining_amount, 2, '.', '');
                        $param['payment_mode'] = $effective_payment_mode;
                        $param['payment_details'] = "تم خصم {$param['wallet_amount']} ل.س من محفظتكم. الرصيد المتبقي في محفظتكم: {$param['wallet_balance']} ل.س.\r\nالمبلغ المتبقي عند الاستلام: {$param['remaining_amount']} ل.س.";
                    } elseif ($payment_mode === 'WALLET' && $wallet_amount >= $total) {
                        // Full wallet payment
                        if (!isset($param['wallet_balance']) || $param['wallet_balance'] === '') {
                            $user = isset($param['customer_id']) ? User::find($param['customer_id']) : null;
                            $wallet_balance = $user ? $user->balanceFloat : 0;
                        } else {
                            $wallet_balance = floatval($param['wallet_balance'] ?? 0);
                        }
                        $remaining_amount = 0;

                        // Format values
                        $param['wallet_amount'] = number_format($wallet_amount, 2, '.', '');
                        $param['total'] = number_format($total, 2, '.', '');
                        $param['wallet_balance'] = number_format($wallet_balance, 2, '.', '');
                        $param['remaining_amount'] = number_format($remaining_amount, 2, '.', '');
                        $param['payment_details'] = "تم خصم {$param['wallet_amount']} ل.س من محفظتكم. الرصيد المتبقي في محفظتكم: {$param['wallet_balance']} ل.س.\r\nالمبلغ المتبقي عند الاستلام: {$param['remaining_amount']} ل.س.";
                    } elseif ($payment_mode === 'COD' || $wallet_amount == 0) {
                        // COD or no wallet usage
                        $param['wallet_amount'] = '0.00';
                        $param['wallet_balance'] = '0.00';
                        $param['remaining_amount'] = number_format($total, 2, '.', '');
                        $param['payment_mode'] = 'COD';
                        $param['payment_details'] = "المبلغ المتبقي عند الاستلام: {$param['remaining_amount']} ل.س.";
                    } else {
                        // Default fallback
                        $param['wallet_amount'] = '0.00';
                        $param['wallet_balance'] = '0.00';
                        $param['remaining_amount'] = number_format($total, 2, '.', '');
                        $param['payment_details'] = "المبلغ المتبقي عند الاستلام: {$param['remaining_amount']} ل.س.";
                    }
                } elseif ($target === 'STATUS5') {
                    // STATUS5 does not include payment details
                    $param['total'] = number_format(floatval($param['total'] ?? 0), 2, '.', '');
                    $param['wallet_amount'] = '0.00';
                    $param['wallet_balance'] = '0.00';
                    $param['remaining_amount'] = '0.00';
                    $param['payment_details'] = '';
                } else {
                    // Default values for other statuses
                    $param['wallet_amount'] = '0.00';
                    $param['wallet_balance'] = '0.00';
                    $param['remaining_amount'] = number_format(floatval($param['total'] ?? 0), 2, '.', '');
                    $param['payment_details'] = '';
                }

                $message = str_replace($this->find($param), array_values($param), $msg);
                return $message;
            case 'ADMIN_STATUS4':
                $msg = $data['msg_admin_status4'] ?? '';
                $message = str_replace($this->find($param), array_values($param), $msg);
                return $message;
            case 'OWNER':
            case 'DRIVER':
            case 'STORE':
            case 'CUSTOMER':
            case 'ADMIN':
            case 'CANCEL':
                $msg = $data['msg_' . strtolower($target)] ?? '';
                $message = str_replace($this->find($param), array_values($param), $msg);
                break;
            default:
                return '';
        }

        if (!empty(strpos($message, '{items}'))) {
            $items = $this->processItems($param['unique_order_id'] ?? '');
            $message = str_replace(['{target}', '{items}'], [$target, $items], $message);
        } else {
            $message = str_replace('{target}', $target, $message);
        }
        return $message;
    }

    private function processItems($unique_order_id) {
        $msgItems = "";
        $order = Order::where('unique_order_id', $unique_order_id)->first();
        if ($order) {
            $pivotItems = $order->orderitems()->where('order_id', $order->id)->get();
            foreach ($pivotItems as $pI) {
                $msgItems .= "(" . $pI->quantity . ") " . $pI->name . " @" . $pI->price . " = " . config('setting.currencyFormat') . ($pI->quantity * $pI->price) . "\r\n";
                $pivotAddons = $pI->order_item_addons()->where('orderitem_id', $pI->id)->get();
                if ($pivotAddons->isNotEmpty()) {
                    foreach ($pivotAddons as $pA) {
                        $msgItems .= $pA->addon_category_name . "> " . $pA->addon_name . " @" . $pA->addon_price . " = " . config('setting.currencyFormat') . ($pI->quantity * $pA->addon_price) . "\r\n";
                    }
                }
            }
        }
        return $msgItems;
    }

    private function find($param) {
        $placeholders = [];
        foreach ($param as $key => $val) {
            $placeholders['{'.$key.'}'] = $val;
        }
        return array_keys($placeholders);
    }
}