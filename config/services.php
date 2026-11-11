<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'list_id' => env('BREVO_LIST_ID'),
        'hvco_template_id' => env('BREVO_HVCO_TEMPLATE_ID'),
        'hvco_guide_url' => env('BREVO_HVCO_GUIDE_URL'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
        'sender_email' => env('BREVO_SENDER_EMAIL'),
        'sender_name' => env('BREVO_SENDER_NAME', 'MondoDiVino'),
        'base_url' => env('BREVO_BASE_URL', 'https://api.brevo.com/v3'),
        'attributes' => [
            'first_name' => 'FIRSTNAME',
            'zip' => 'ZIP',
            'funnel_status' => 'FUNNEL_STATUS',
            'utm_source' => 'UTM_SOURCE',
            'utm_medium' => 'UTM_MEDIUM',
            'utm_campaign' => 'UTM_CAMPAIGN',
            'utm_content' => 'UTM_CONTENT',
            'utm_term' => 'UTM_TERM',
            'referrer' => 'REFERRER',
            'landing_variant' => 'LANDING_VARIANT',
            'consent_at' => 'CONSENT_AT',
            'locale' => 'LOCALE',
        ],
        'profile_attributes' => [
            'first_name' => 'FIRSTNAME',
            'funnel_status' => 'FUNNEL_STATUS',
            'state' => 'STATE',
            'adventure_level' => 'ADVENTURE_LEVEL',
            'usual_bottle_spend' => 'BOTTLE_SPEND_RANGE',
            'set_budget' => 'SET_BUDGET_RANGE',
            'desired_timing' => 'DESIRED_TIMING',
            'completed_at' => 'PROFILE_COMPLETED_AT',
        ],
        'booking_attributes' => [
            'funnel_status' => 'FUNNEL_STATUS',
            'booking_status' => 'BOOKING_STATUS',
            'call_start_utc' => 'CALL_START_UTC',
            'call_timezone' => 'CALL_TIMEZONE',
            'calcom_booking_uid' => 'CALCOM_BOOKING_UID',
        ],
    ],

    'calcom' => [
        'embed_url' => env('CALCOM_EMBED_URL'),
        'event_type_id' => env('CALCOM_EVENT_TYPE_ID'),
        'event_type_slug' => env('CALCOM_EVENT_TYPE_SLUG'),
        'username' => env('CALCOM_USERNAME'),
        'team_slug' => env('CALCOM_TEAM_SLUG'),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
        'api_version' => env('CALCOM_API_VERSION', '2021-10-20'),
        'support_email' => env('CALCOM_SUPPORT_EMAIL'),
        'booking_duration' => env('CALCOM_BOOKING_DURATION'),
    ],

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'access_token' => env('META_CONVERSIONS_API_TOKEN'),
        'graph_api_version' => env('META_GRAPH_API_VERSION', 'v26.0'),
        'graph_base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
        'privacy_policy_url' => env('META_PRIVACY_POLICY_URL'),
        'consent_cookie' => env('META_CONSENT_COOKIE', 'mdv_meta_consent'),
        'consent_lifetime_days' => (int) env('META_CONSENT_LIFETIME_DAYS', 180),
        'data_processing_options' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('META_DATA_PROCESSING_OPTIONS', ''))
        ))),
        'data_processing_options_country' => (int) env('META_DATA_PROCESSING_OPTIONS_COUNTRY', 0),
        'data_processing_options_state' => (int) env('META_DATA_PROCESSING_OPTIONS_STATE', 0),
    ],

];
