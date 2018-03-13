
**Endpoints and service for connecting your application to Soundslice via their API.**

Soundslice API docs: https://www.soundslice.com/help/data-api/


Installation
========================================================================================================================

[Available via Packagist](https://packagist.org/packages/railroad/soundslice).

Provide the following environmental variables:

* `SOUNDSLICE_APP_ID`
* `SOUNDSLICE_SECRET`

See Lastpass Secure Note "**Musora - Local .env**" <span style="color:grey;font-size:0.8em">(in "Shared Railroad Web 
Development" -> "Local Dev" -> )</span>

For acceptance tests, copy *.env.testing.example* and rename it "*.env.testing*", and provide the respective
environmental variables there. It's already added to *.gitignore*.


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


Methods
------------------------------------------------------------------------------------------------------------------------

### create score

**PUT** "create"

| param                     | data-type | required  |
|---------------------------|-----------|-----------|
| name                      | string    | yes       |
| folder-id                 | string    |           |
| artist                    | string    |           |
| publicly-listed\*         | boolean   |           |
| embed-white-list-only\*   | boolean   |           |
| embed-globally\*          | boolean   |           |
| printing-allowed          | boolean   |           |

\* For notes about these params see the "Create Score Parameter Notes" section below 


### list scores

**GET** "list"


### get score

**GET** "get/{slug}"


### delete score

**DELETE** "delete"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |

### move score

**POST** "move"

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |


### folder create

**PUT** "folder/create"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| name                  | string    | yes       |


### folder delete

**DELETE** "folder/delete"

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| id                    | string    | yes       |
    

### create notation

**PUT** "notation"
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |
| asset-url             | string    | yes       |
    
`asset-url` should be a publicly-accessible URL that provides the relevant media file (ex: *musicxml* file)


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
