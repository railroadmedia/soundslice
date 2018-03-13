

Notes
========================================================================================================================

Endpoints and service for connecting your application to Soundslice via their API.

Soundslice API docs: https://www.soundslice.com/help/data-api/

Provide the following environmental variables:

* `SOUNDSLICE_APP_ID`
* `SOUNDSLICE_SECRET`

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

\* For notes about these params see the "Appendix 1 - create score parameter notes" section below 


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
    
`assetUrl` should be a publicly-accessible URL that provides the relevant media file (ex: *musicxml* file)


Appendix 1 - create score parameter notes
------------------------------------------------------------------------------------------------------------------------

#### publicly-listed

Specifies who can view the score on soundslice.com. (Embeds ignore this and use embed_status.)

`false` - “Only me”

`true` - “Anybody who knows its URL”


#### embed-white-list-only

RE embed options. For more, see [Soundslice's embedding docs](https://www.soundslice.com/help/management/#embedding).

`false` - Not restricted only to whitelist domains - defers to `embed-globally` option 

`true` - Allowed only on whitelist domains


#### embed-globally

`false` - Disabled (default value, if not provided)

`true` - Allowed on any domain


#### A note about the *embed-white-list-only* and *embed-globally*

These are options of this *soundslice-integrating **package*** that abstract options of the actual Soundslice API.

Thus, if you look directly at the should slice API you'll see this instead:

    embed_status	Optional	
    An integer specifying embed options. For more, see embedding docs.
    
    1 — Disabled (default value, if not provided)
    2 — Allowed on any domain
    4 — Allowed on whitelist domains        

This package determines which integer to specify for the that option the following way:

```php
$embedWhiteListOnly ? 4 : ($embedGlobally ? 2 : 1)
```

Just an FYI lest you look at soundslice's API docs when attempting to use this method **of this *integration* package**.



------------------------------------------------------------------------------------------------------------------------





