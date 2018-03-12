
Endpoints and service for connecting your application to Soundslice via their API.

------------------------------------------------------------------------------------------------------------------------

Soundslice API docs: https://www.soundslice.com/help/data-api/

Provide the following environmental variables:

* `AWS_S3_SOUNDSLICE_ACCESS_KEY`
* `AWS_S3_SOUNDSLICE_ACCESS_SECRET`
* `AWS_S3_SOUNDSLICE_REGION`
* `AWS_S3_SOUNDSLICE_BUCKET`
* `AWS_CLOUDFRONT_SOUNDSLICE`
* `SOUNDSLICE_APP_ID`
* `SOUNDSLICE_SECRET`

For acceptance tests, copy *.env.testing.example* and rename it "*.env.testing*", and provide the respective
environmental variables there. It's already added to *.gitignore*.


------------------------------------------------------------------------------------------------------------------------


How to use
========================================================================================================================

When your **application uploads a soundslice notation file, fire a `Railroad\Soundslice\Events\RemoteAssetUploadEvent` 
event**.

Specify the following on your Event:

```php

```

<div style="font-size:0.9em;color:grey">

**For Example:** have an event fire that is heard by a listener. If the file uploaded is to be a soundslice notation 
file, fire the above mentioned event
</div>

This will then ***a**synchronously* create the notation from the asset uploaded to s3. There is no response. If you
need confirmation of success, make a request to this package's GET "score" endpoint (for the name you specified in the 
POST).

Configure the s3 bucket that will contain the (musicxml) files that will uploaded to soundslice to create "notations".
The name ("target") you specify for your (s3) assets is what's used for the notations.  


<div style="font-size:0.9em;color:grey">

**Protip**: use the really great [railroad\remotestorage](https://packagist.org/packages/railroad/remotestorage) package! 
</div>

