<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\FoodomaaNews;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Artisan;
use App\Setting;

class FoodomaaNewsController extends Controller
{
    public function getFoodomaaNews()
    {
        $currentTime = Carbon::now()->timestamp;
        $lastCheckTime = Carbon::createFromTimestamp(config('setting.lastFoodomaaNewsFetchTime'));
        $diff = Carbon::createFromTimestamp($currentTime)->diffInMinutes($lastCheckTime);

        if (config('setting.lastFoodomaaNewsFetchTime') == null || $diff >= 60) {
            $url = "https://raw.githubusercontent.com/a-ssassi-n/news/main/news.json";
            $newsNotifications = Curl::to($url)->asJson()->get();

            if (count($newsNotifications) > 0) {
                $newsIdArray = [];

                foreach ($newsNotifications as $newsNotification) {
                    array_push($newsIdArray, $newsNotification->news_id);
                }

                $dataFromDb = FoodomaaNews::whereIn('news_id', $newsIdArray)->get(['news_id'])->pluck('news_id')->toArray();
                $notInDb = array_diff($newsIdArray, $dataFromDb);

                foreach ($newsNotifications as $newsNotification) {
                    if (in_array($newsNotification->news_id, $notInDb)) {
                        $foodomaaNews = new FoodomaaNews();

                        $foodomaaNews->news_id = $newsNotification->news_id;
                        $foodomaaNews->title = $newsNotification->title;
                        $foodomaaNews->content = $newsNotification->content;
                        $foodomaaNews->image = $newsNotification->image;
                        $foodomaaNews->link = $newsNotification->link;
                        $foodomaaNews->save();
                    }
                }
            }

            $setting = Setting::where('key', 'lastFoodomaaNewsFetchTime')->first();
            $setting->value = $currentTime;
            $setting->save();
            Artisan::call('cache:clear');
        }

        $foodomaaNews = FoodomaaNews::orderBy('id', 'DESC')->get()->take(10);
        $nonReadCount = 0;
        foreach ($foodomaaNews as $news) {
            if (!$news->is_read) {
                $nonReadCount++;
            }
        }

        $newsRender = view('admin.partials.dashboardFoodomaaNews', [
            'foodomaaNews' => $foodomaaNews,
            'nonReadCount' => $nonReadCount,
        ])->render();

        $response = [
            'success' => true,
            'data' => $newsRender,
        ];

        return response()->json($response);
    }

    public function makeFoodomaaNewsRead(Request $request)
    {
        $foodomaaNews = FoodomaaNews::where('id', $request->id)->first();

        if ($foodomaaNews) {
            if ($foodomaaNews->is_read) {
                return response()->json(['success' => true, 'was_already_read' => true]);
            }
            $foodomaaNews->is_read = true;
            $foodomaaNews->save();
            return response()->json(['success' => true, 'was_already_read' => false]);
        }
    }
}
