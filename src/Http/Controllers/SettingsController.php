<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Image;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\User;
use RuntimeException;
use Storage;
use Throwable;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $notificationHelper = app(NotificationHelper::class);

        $notificationSettings = $user->settings['notifications'] ?? $notificationHelper->getDefaultUserNotificationSettings();

        return view('pages.settings', [
            'notificationHelper' => $notificationHelper,
            'notificationSettings' => $notificationSettings,
        ]);
    }

    public function postIndex(Request $request)
    {
        if ($newAvatar = $request->file('account_avatar')) {
            try {
                $this->handleAvatarUpload($newAvatar);

            } catch (Throwable $e) {
                $request->session()->flash('alert-danger', $e->getMessage());
                return redirect()->back();
            }
        }

        $this->updateNotifications($request->input());
//        $this->updateNotificationSettings($request->input());

        $request->session()->flash('alert-success', 'Your settings have been updated.');
        return redirect()->route('settings');
    }

    protected function handleAvatarUpload(UploadedFile $file)
    {
        /** @var User $user */
        $user = Auth::user();

        // Convert image
        $image = Image::make($file)
            ->fit(200, 200)
            ->encode('png');

        $data = (string)$image;
        $path = 'uploads/avatars';
        $fileName = (str_slug($user->display_name) . '.png');

        if (!Storage::disk('public')->put(($path . '/' . $fileName), $data)) {
            throw new RuntimeException('Failed to upload avatar');
            // todo: notify bugsnag
        }

        $user->avatar = $fileName;
        $user->save();
    }

    protected function updateNotifications(array $data)
    {
        if (!isset($data['notifications']) || empty($data['notifications'])) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $newNotifications = [];

        foreach ($data['notifications'] as $key => $types) {
            foreach ($types as $type => $channels) {
                foreach ($channels as $channel => $enabled) {
                    if ($enabled === 'on') {
                        array_set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        $settings = ($user->settings ?? []);
        $settings['notifications'] = $newNotifications;

        $user->settings = $settings;
        $user->save();
    }

    protected function updateNotificationSettings(array $data)
    {
        /** @var User $user */
        $user = Auth::user();

        $settings = ($user->settings ?? []);
        $settings['notification_digest'] = $data['notification_digest'];

        $user->settings = $settings;
        $user->save();
    }
}
