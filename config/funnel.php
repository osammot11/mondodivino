<?php

return [
    'landing_variant' => env('HVCO_LANDING_VARIANT', 'main'),
    'collect_zip' => (bool) env('HVCO_COLLECT_ZIP', false),
    'consent_text' => env(
        'HVCO_CONSENT_TEXT',
        "Free instant access. We'll also send occasional Italian wine insights and offers. Unsubscribe anytime."
    ),
    'guide_mockup' => env('HVCO_GUIDE_MOCKUP'),
    'taste_profile_link_days' => (int) env('HVCO_TASTE_PROFILE_LINK_DAYS', 30),
    'profile_consent_text' => env(
        'HVCO_PROFILE_CONSENT_TEXT',
        'I agree that MondoDiVino may use these answers to prepare my consultation and contact me about it. I have read the Privacy Policy.'
    ),
    'sms_consent_text' => env(
        'HVCO_SMS_CONSENT_TEXT',
        'I agree to receive optional text messages from MondoDiVino. Message and data rates may apply. Reply STOP to opt out.'
    ),
    'serviceable_states' => array_values(array_filter(array_map('trim', explode(',', (string) env('HVCO_SERVICEABLE_STATES', ''))))),
    'minimum_set_budget' => env('HVCO_MINIMUM_SET_BUDGET'),
    'states' => [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
        'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
        'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
        'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
        'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
        'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
        'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
        'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
        'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
        'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
        'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
        'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ],
    'profile_options' => [
        'wine_styles' => ['red', 'white', 'sparkling', 'rose', 'dessert_fortified', 'exploring'],
        'taste_descriptions' => ['light_fresh', 'silky_elegant', 'rich_full', 'bold_structured', 'crisp_mineral', 'aromatic_floral', 'fruity_generous', 'savory_earthy', 'complex_age_worthy', 'dry_sparkling'],
        'avoidances' => ['high_tannin', 'high_acidity', 'oaky', 'sweetness', 'very_light', 'very_powerful', 'funky', 'nothing', 'other'],
        'adventure_levels' => ['familiar_discoveries', 'balanced', 'hidden_italy'],
        'occasions' => ['everyday_dinners', 'hosting', 'special_occasions', 'gifts', 'cellar', 'learning'],
        'preferred_mixes' => ['mostly_reds', 'mostly_whites', 'balanced_mix', 'include_sparkling', 'luca_recommendation'],
        'bottle_spend' => ['under_30', '30_50', '50_80', '80_125', '125_plus'],
        'set_budgets' => ['under_300', '300_499', '500_749', '750_999', '1000_plus', 'guidance'],
        'desired_timings' => ['asap', 'within_30_days', 'within_2_3_months', 'specific_date', 'exploring'],
    ],
];
