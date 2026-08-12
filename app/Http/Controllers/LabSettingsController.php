<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLabSettingsRequest;
use App\Models\LabSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LabSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/lab', [
            'settings' => LabSetting::current(),
        ]);
    }

    public function update(UpdateLabSettingsRequest $request): RedirectResponse
    {
        $settings = LabSetting::current();
        $data = $request->safe()->except(['remove_logo', 'logo']);

        // Logo: replace when a new data URI is sent, clear when asked, else keep.
        if ($request->boolean('remove_logo')) {
            $data['logo'] = null;
        } elseif ($request->filled('logo')) {
            $data['logo'] = $request->input('logo');
        }

        $settings->update($data);

        return back()->with('success', 'Lab branding updated.');
    }
}
