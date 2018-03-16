
**Endpoints and service for connecting your application to Soundslice via their API.**

Soundslice API docs: https://www.soundslice.com/help/data-api/


Installation
========================================================================================================================

Install
------------------------------------------------------------------------------------------------------------------------

[Available via Packagist](https://packagist.org/packages/railroad/soundslice).


Register Service Provider
------------------------------------------------------------------------------------------------------------------------

Add the *SoundsliceServiceProvider* to your application's list of service-providers ("providers" property, array) in 
/config/app.php.


Publish Config
------------------------------------------------------------------------------------------------------------------------

Run this command to publish the package's config file(s) to your application's config directory:

```bash
$ php artisan vendor:publish
```


Provide Soundslice Credentials
------------------------------------------------------------------------------------------------------------------------

Provide the following environmental variables:

* `SOUNDSLICE_APP_ID`
* `SOUNDSLICE_SECRET`

See Lastpass Secure Note "**Musora - Local .env**" <span style="color:grey;font-size:0.8em">(in "Shared Railroad Web 
Development" -> "Local Dev" -> )</span>

(For acceptance tests, copy *.env.testing.example* and rename it "*.env.testing*", and provide the respective
environmental variables there. It's already added to *.gitignore*)



API Reference
========================================================================================================================

Notes
------------------------------------------------------------------------------------------------------------------------

### endpoints

Prepend all endpoints below with `/soundslice/`. 

Anything in curly braces is an inline parameter.

For example if below it says `get/{slug}`, then your endpoint for the 
slug `foo` would actually be `/soundslice/get/foo`.


### Defaults

All optional booleans default to `false` unless noted otherwise.


## List of methods available

* createScore
* list
* get
* delete
* move
* folder create
* folder delete
* create notation


## Errors

All errors are available as an item in the Json response's `errors` array (which exists in place of the `data` array). 

Example:

```json
{
  "errors":[
    {
      "status":"Internal Server Error",
      "code":500,
      "title":"SoundSliceJsonController@createScore failed",
      "detail":"flux capacitor exceeded threshold with gamma output of 28937u4893 hertz."
    }
  ]
}
```

This is as per my attempts to understand [the json-api docs](http://jsonapi.org/format/#errors).

Each error item will have the fields as per the example below:

**status**:

> the HTTP status code applicable to this problem, expressed as a string value.

**code**:

> an application-specific error code, expressed as a string value.

**title**:

> a short, human-readable summary of the problem that SHOULD NOT change from occurrence to occurrence of the problem, except for purposes of localization.

**detail**:

> a human-readable explanation specific to this occurrence of the problem. Like title, this field’s value can be localized



Methods
------------------------------------------------------------------------------------------------------------------------


### create score

**PUT** "create"

| param                     | data-type | required  | optional  |
|---------------------------|-----------|-----------|-----------|
| name                      | string    |           |     x     |
| artist                    | string    |           |     x     |
| folder-id                 | string    |           |     x     |
| publicly-listed\*         | boolean   |           |     x     |
| embed-white-list-only\*   | boolean   |           |     x     |
| embed-globally\*          | boolean   |           |     x     |
| printing-allowed          | boolean   |           |     x     |

\* For notes about these params see the "Create Score Parameter Notes" section below 


#### Returns, on success

* status code `201`
* status text `Created`
* `slug` string

```json
{
    "statusText":"Created",
    "status":201,
    "data":{
        "slug": "fo1337br"
    }
}
```


------------------------------------------------------------------------------------------------------------------------

### list scores

**GET** "list"

#### Returns, on success

* status code `200`
* status text `OK`
* `scores` array with all the scores for the account.

Example (account in for this example only has two scores):

```json
{
    "statusText":"OK",
    "status":200,
    "data":{
        "scores":[
            {
                "status":1,
                "show_notation":true,
                "print_status":1,
                "can_print":false,
                "embed_status":1,
                "name":"nameFoo ex",
                "artist":"artistFoo pariatur ab",
                "slug":"fo154364br",
                "recording_count":0,
                "has_notation":false
            }
        ]
    }
}
```


------------------------------------------------------------------------------------------------------------------------

### get score

**GET** "get/{slug}"


#### Returns, on success

* status code `200`
* status text `OK`
* `score` array

```json
{
    "statusText":"OK",
    "status":200,
    "data":{
        "score": {
            "status":1,
            "show_notation":true,
            "print_status":1,
            "can_print":false,
            "embed_status":1,
            "name":"nameFoo labore natus",
            "artist":"artistFoo atque repellendus iusto",
            "url":"/scores/154453/",
            "recording_count":0,
            "has_notation":false
        }
    }
}
```

------------------------------------------------------------------------------------------------------------------------

### delete score

**DELETE** "delete"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |


#### Returns, on success

* status code `200`
* status text `OK`
* `deleted` integer representing a boolean value

```json
{
    "statusText":"OK",
    "status":200,
    "data":{
        "deleted": 1
    }
}
```


------------------------------------------------------------------------------------------------------------------------

### ~~move score~~

***UNDER CONSTRUCTION***

~~**POST** "move"~~

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| ~~slug~~                  | string    | yes       |

#### ~~Returns, on success~~



------------------------------------------------------------------------------------------------------------------------

### folder create

**PUT** "folder/create"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| name                  | string    | yes       |
| parent-id             | string    |           |


#### Returns, on success

* status code `201`
* status text `Created`
* `folder-id` string

```json
{
    "statusText":"Created",
    "status":201,
    "data":{
        "folder-id": "fo1337br"
    }
}
```


------------------------------------------------------------------------------------------------------------------------

### folder delete

**DELETE** "folder/delete"

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| id                    | string    | yes       |
    

#### Returns, on success

* status code `200`
* status text `Created`
* `delete` integer representing a boolean value

```json
{
    "statusText":"OK",
    "status":200,
    "data":{
        "deleted": 1
    }
}
```


------------------------------------------------------------------------------------------------------------------------

### create notation

**PUT** "notation"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |
| asset-url             | string    | yes       |
    
`asset-url` should be a publicly-accessible URL that provides the relevant media file (ex: *musicxml* file)


#### Returns, on success

* status code `200` (**BECAUSE DOES NOT CREATE**)
* status text `OK` (**BECAUSE DOES NOT CREATE**)
* `notation` integer representing boolean value. **DOES NOT REPRESENT CREATION OF NOTATION,
ONLY SUCCESS IN STARTING PROCESS THAT ATTEMPTS CREATION - YOU MUST REQUEST GET TO DETERMINE
SUCCESS**.

```json
{
    "statusText":"OK",
    "status":200,
    "data":{
        "notation": 1
    }
}
```



Create Score Parameter Notes
------------------------------------------------------------------------------------------------------------------------

#### "publicly-listed"

Specifies who can view the score on soundslice.com. (Embeds ignore this and use embed_status.)

`false` - “Only me”

`true` - “Anybody who knows its URL”


#### Embed options

Reference: [Soundslice's embedding docs](https://www.soundslice.com/help/management/#embedding).


##### "embed-white-list-only"

`false` - Not restricted only to whitelist domains - defers to `embed-globally` option 

`true` - Allowed only on whitelist domains


##### "embed-globally"

`false` - Disabled (default value, if not provided)

`true` - Allowed on any domain


##### A note about the *embed-white-list-only* and *embed-globally* options

These are options of this *soundslice-integrating **package*** that abstract options of the actual Soundslice API.

Thus, if you look directly at the Soundslice API you'll see this instead:

    embed_status	Optional	
    An integer specifying embed options. For more, see embedding docs.
    
    1 — Disabled (default value, if not provided)
    2 — Allowed on any domain
    4 — Allowed on whitelist domains

This package determines which integer to specify for the that (`embed_status`) option the following way:

```php
$embedWhiteListOnly ? 4 : ($embedGlobally ? 2 : 1)
```

Just an FYI lest you look at soundslice's API docs when attempting to use this method **of this *integration* package**.


---------------------------------------

These are our test-cases
========================================

(in [soundslice/tests/Acceptance/SoundsliceTest.php](https://github.com/railroadmedia/soundslice/blob/master/tests/Acceptance/SoundsliceTest.php))

1. create_score
    1. create_score_fails_folder_id_not_whole_number
    1. create_score_fails_folder_does_not_exist
    1. create_score_fails_already_exists
    1. create_score_validation_fail
1. list
1. get_score
    1. get_score_not_found
1. delete_score
    1. delete_score_not_found
    1. delete_score_validation_failure
1. create_folder
    1. create_nested_folder
    1. create_folder_validation_failure
    1. create_folder_failure_invalid_parent_id
1. delete_folder
    1. delete_folder_not_found
    1. delete_folder_validation_failure
1. create_notation
    1. create_notation_validation_failure
    1. create_notation_upload_more_than_one
    1. create_notation_with_same_values


---------------------------------------

