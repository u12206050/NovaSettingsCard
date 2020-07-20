<?php

namespace Day4\SettingsCard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Nova\Http\Requests\NovaRequest;

class SettingsCardController extends Controller
{
    /**
     * @var mixed
     */
    protected $disks;

    /**
     * @param NovaRequest $request
     */
    public function saveSettings(NovaRequest $request)
    {

        $fields = collect($request->except('disks'))->reject(function ($value, $key) {
            return Str::contains($key, 'DraftId');
        });

        $this->disks = collect(json_decode($request->get('disks')));

        $before = setting()->all();
        $fields->each(function ($value, $key) use ($request) {
            $value = $this->getRequestValue($request, $key, $value);

            if (!$value && $value !== false && $value !== 0) {
                setting()->forget($key);
            }
            if ($value) {
                setting([$key => $value]);
            }
        });

        setting()->save();

        $cards = \Laravel\Nova\Nova::availableCards($request);
        foreach ($cards as $card) {
            if (get_class($card) == \Day4\SettingsCard\SettingsCard::class) {
                if (is_callable($card->onSave)) {
                    call_user_func($card->onSave, $before, setting()->all());
                }
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * @param $request
     * @param $key
     * @param $setting
     * @return mixed
     */
    private function getRequestValue($request, $key, $value)
    {
        if ($value instanceof UploadedFile) {
            $disk = 'public';

            if ($this->disks->has($key)) {
                $disk = $this->disks->get($key);
            }

            $this->deletePreviousImage($key, $disk);

            $fileName = sha1($value->getClientOriginalName()).'.'.$value->getClientOriginalExtension();

            $path = $request->{$key}->storeAs('', $fileName, $disk);

            return json_encode([
                'path' => $path,
                'url'  => Storage::disk($disk)->url($path),
                'size' => $request->{$key}->getSize(),
                'name' => $request->{$key}->getClientOriginalName(),
            ]);
        }

        return $value;
    }

    /**
     * @param $key
     */
    private function deletePreviousImage($key, $disk)
    {
        $data = setting($key, false);
        if ($data !== false) {
            $data = json_decode($data);
            Storage::disk($disk)->delete($data->path);
        }
    }
}
