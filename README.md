
Endpoints and service for connecting your application to Soundslice via their API.

------------------------------------------------------------------------------------------------------------------------

Soundslice API docs: https://www.soundslice.com/help/data-api/

Provide the following environmental variables:

* `AWS_S3_SOUNDSLICE_ACCESS_KEY`
* `AWS_S3_SOUNDSLICE_ACCESS_SECRET`
* `AWS_S3_SOUNDSLICE_REGION`
* `AWS_S3_SOUNDSLICE_BUCKET`
* `SOUNDSLICE_APP_ID`
* `SOUNDSLICE_SECRET`

For acceptance tests, copy *.env.testing.example* and rename it "*.env.testing*", and provide the respective
environmental variables there. It's already added to *.gitignore*.
