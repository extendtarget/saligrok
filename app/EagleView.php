<?php

namespace App;

class EagleView
{
    public function getViewOrderSemiEagleViewData()
    {
        $url = request()->getSchemeAndHttpHost() . "/delivery-google-services.json";
        $data = \Ixudra\Curl\Facades\Curl::to($url)->asJson()->get();
        if ($data) {
            $data = ["project_number" => $data->project_info->project_number, "firebase_url" => $data->project_info->firebase_url, "project_id" => $data->project_info->project_id, "storage_bucket" => $data->project_info->storage_bucket];
            return $data;
        }
        return NULL;
    }
}
