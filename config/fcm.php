<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FCM (Firebase Cloud Messaging) v1
    |--------------------------------------------------------------------------
    | Project IDFirebase yang dipakai FCM HTTP v1. Default = project
    | development: siakad-development (konsisten dengan Firebase project ID
    | di firebase.json / FlutterFire options).
    */
    'project_id' => env('FCM_PROJECT_ID', 'siakad-development'),

    /*
    |--------------------------------------------------------------------------
    | Service Account (untuk OAuth2 FCM v1)
    |--------------------------------------------------------------------------
    | PATH ke file service account JSON dari Firebase console (dipasang
    | MANUAL oleh tim operasional — tidak terisi otomatis, tidak diekspos
    | ke response API). Default: storage/app/fcm_service_account.json
    | (storage diabaikan git).
    */
    'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH', storage_path('app/fcm_service_account.json')),
];
