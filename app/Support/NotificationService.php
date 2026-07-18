<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    public static function notify(
        ?User $user,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        array $data = []
    ): ?UserNotification {
        if (!$user) {
            return null;
        }

        return UserNotification::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    public static function notifyMany(iterable $users, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): void
    {
        foreach ($users as $user) {
            self::notify($user, $type, $title, $message, $actionUrl, $data);
        }
    }

    public static function notifyPermission(int $organizationId, string $permission, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): void
    {
        $users = User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->with('customRole')
            ->get()
            ->filter(fn(User $user) => $user->hasPermission($permission));

        self::notifyMany($users, $type, $title, $message, $actionUrl, $data);
    }

    public static function forUser(?User $user, int $limit = 8): array
    {
        if (!$user) {
            return ['unread_count' => 0, 'items' => collect()];
        }

        $query = UserNotification::where('user_id', $user->id);

        return [
            'unread_count' => (clone $query)->unread()->count(),
            'items' => (clone $query)
                ->orderByRaw('read_at is null desc')
                ->latest()
                ->limit($limit)
                ->get(),
        ];
    }

    public static function markAllRead(User $user): int
    {
        return UserNotification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
