<?php

return [
    /*
     * The brief names the first two milestones but omits the full ladder.
     * Configuration keeps that assumption explicit and easy to change.
     */
    'groups' => [
        'purchases' => [
            ['code' => 'first-purchase', 'name' => 'First Purchase', 'threshold' => 1],
            ['code' => '5-purchases', 'name' => '5 Purchases', 'threshold' => 5],
            ['code' => '10-purchases', 'name' => '10 Purchases', 'threshold' => 10],
            ['code' => '25-purchases', 'name' => '25 Purchases', 'threshold' => 25],
            ['code' => '50-purchases', 'name' => '50 Purchases', 'threshold' => 50],
            ['code' => '100-purchases', 'name' => '100 Purchases', 'threshold' => 100],
            ['code' => '250-purchases', 'name' => '250 Purchases', 'threshold' => 250],
            ['code' => '500-purchases', 'name' => '500 Purchases', 'threshold' => 500],
            ['code' => '1000-purchases', 'name' => '1000 Purchases', 'threshold' => 1000],
            ['code' => '2500-purchases', 'name' => '2500 Purchases', 'threshold' => 2500],
        ],
    ],
    'badges' => [
        ['code' => 'beginner', 'name' => 'Beginner', 'threshold' => 1],
        ['code' => 'intermediate', 'name' => 'Intermediate', 'threshold' => 4],
        ['code' => 'advanced', 'name' => 'Advanced', 'threshold' => 8],
        ['code' => 'master', 'name' => 'Master', 'threshold' => 10],
    ],
    'cashback_amount_kobo' => 30000,
];
