<?php

return [
    'name' => 'Notification',

    'per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Notification Types Registry
    |--------------------------------------------------------------------------
    | Add new notification types here (or from other modules via config merge).
    | Each type defines a human label and the channels it may be dispatched on.
    | The 'data' payload shape is documented per type but enforced at send-time.
    |
    | To register a type from another module's ServiceProvider:
    |   config(['notification-module.notification_types.my.type' => [...]])
    */
    'notification_types' => [
        'user.welcome' => [
            'label'    => 'Welcome',
            'channels' => ['database', 'email'],
        ],
        'user.password_reset' => [
            'label'    => 'Password Reset',
            'channels' => ['email'],
        ],
        'order.placed' => [
            'label'    => 'Order Placed',
            'channels' => ['database', 'email', 'sms'],
        ],
        'order.status_changed' => [
            'label'    => 'Order Status Changed',
            'channels' => ['database', 'email'],
        ],
        'comment.received' => [
            'label'    => 'Comment Received',
            'channels' => ['database'],
        ],
        'system.announcement' => [
            'label'    => 'System Announcement',
            'channels' => ['database', 'email', 'push'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph Map
    |--------------------------------------------------------------------------
    | Register every model that can BE a notifiable (receive notifications).
    | Other modules add their models here when they use HasNotifications trait.
    */
    'morph_map' => [
        'user' => \Modules\Auth\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transformer Map
    |--------------------------------------------------------------------------
    | Maps each notifiable model class to its Fractal transformer so the
    | notifiable relationship can be rendered correctly in API responses.
    */
    'transformer_map' => [
        \Modules\Auth\Models\User::class => \Modules\Auth\Http\Resources\Transformers\UserTransformer::class,
    ],
];
