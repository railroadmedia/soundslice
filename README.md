

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

Table of Contents
------------------------------------------------------------------------------------------------------------------------

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

### createScore

PUT 'create'

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| name                  | string    | yes       |
| folderId              | string    |           |
| artist                | string    |           |
| publiclyListed        | boolean   |           |
| embedWhiteListOnly    | boolean   |           |
| embedGlobally         | boolean   |           |
| printingAllowed       | boolean   |           |

### list

GET 'list' 


### get

GET 'get/{slug}'


### delete

DELETE 'delete'
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |

### move

POST 'move'

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |


### folder create

PUT 'folder/create'
    name
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |


### folder delete

DELETE 'folder/delete'

| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| id                    | string    | yes       |
    

### create notation

PUT 'notation'
    
| param                 | data-type | required  |
|-----------------------|-----------|-----------|
| slug                  | string    | yes       |
| assetUrl              | string    | yes       |
    
`assetUrl` should be a publicly-accessible URL that provides the relevant media file (ex: *musicxml* file)



------------------------------------------------------------------------------------------------------------------------





