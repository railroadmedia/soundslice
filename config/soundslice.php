<?php

/*
 * This should return an array.
 */

return [
    'soundslice' => [
        'awsS3' => [
            'accessKey' => env('AWS_S3_SOUNDSLICE_ACCESS_KEY'),
            'accessSecret' => env('AWS_S3_SOUNDSLICE_ACCESS_SECRET'),
            'region' => env('AWS_S3_SOUNDSLICE_REGION'),
            'bucket' => env('AWS_S3_SOUNDSLICE_BUCKET'),
        ],
        'awsCloudFront' => env('AWS_CLOUDFRONT_SOUNDSLICE'),
        'soundsliceAppId' => env('SOUNDSLICE_APP_ID'),
        'soundsliceSecret' => env('SOUNDSLICE_SECRET'),
        'notationKeySignifier' => ''
    ],
];