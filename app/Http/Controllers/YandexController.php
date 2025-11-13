<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\YandexSetting;
use App\Services\YandexMapsService;

class YandexController extends Controller
{
    public function __construct(
        private YandexMapsService $yandexService
    ) {}

    public function saveSetting(Request $request)
    {
        $request->validate([
            'yandex_url' => 'required|url'
        ]);

        $setting = YandexSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['yandex_url' => $request->yandex_url]
        );

        return response()->json($setting);
    }

    public function getSetting(Request $request)
    {
        $setting = YandexSetting::where('user_id', $request->user()->id)->first();
        return response()->json($setting);
    }

    public function fetchReviews(Request $request)
    {
        try {
            $setting = YandexSetting::where('user_id', $request->user()->id)->firstOrFail();

            $data = $this->yandexService->parseReviews($setting->yandex_url);
            $this->yandexService->saveData($request->user()->id, $data);

            return response()->json($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch reviews',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCachedData(Request $request)
    {
        $data = $this->yandexService->getCachedData($request->user()->id);

        if (!$data) {
            return response()->json([
                'rating' => 0,
                'review_count' => 0,
                'reviews' => [],
                'fetched_at' => null
            ]);
        }

        return response()->json($data);
    }
}

