<?php

namespace Railroad\Soundslice\Services;

use Aws\S3\S3Client;
use Carbon\Carbon;
use GuzzleHttp;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class SoundsliceService
{
    const TYPE_FOR_SCORE= 'soundslice-score';
    const KEY_FOR_SLUG = 'soundslice-slug';
    const KEY_FOR_HASH = 'soundslice-upload-hash';

    /** @var Filesystem */
    private $filesystem;

    /**
     * SoundsliceService constructor.
     * @param string $optionalPathPrefix
     */
    public function __construct($optionalPathPrefix = '')
    {
        $client = new S3Client([
            'credentials' => [
                'key'    => config('railcontent.awsS3_soundslice.accessKey'),
                'secret' => config('railcontent.awsS3_soundslice.accessSecret')
            ],
            'region' => config('railcontent.awsS3_soundslice.region'),
            'version' => 'latest',
        ]);

        $adapter = new AwsS3Adapter(
            $client, config('railcontent.awsS3_soundslice.bucket'), $optionalPathPrefix
        );
        $this->filesystem = new Filesystem($adapter);
    }

    private function request($uri, $method = 'GET', $options = [], $withAuth = true, $entireUrl = ''){

        // ltrim to make sure param can have leading slash or not - doesn't matter because of ltrim here.
        $uri = 'https://www.soundslice.com/' . ltrim($uri, '/');

        if(!empty($entireUrl)){
            $uri = $entireUrl;
        }else{
            // ensure has trailing slash - otherwise request will not work and you will hate life.
            if(substr($uri, -1) !== '/'){
                $uri = $uri . '/';
            }
        }

        // At least one method needs to *not* pass authentication.
        if($withAuth){
            $options['auth'] = [env('SOUNDSLICE_APP_ID'), env('SOUNDSLICE_SECRET')];
        }

        $client = new GuzzleHttp\Client();
        $response = $client->request($method, $uri, $options);

        return $response;
    }

    /**
     * @param string $name
     * @param int|string $folderId
     * @param string $artist
     * @param bool $published
     * @param bool $embedGlobally
     * @param bool $embedWhiteListOnly
     * @param bool $printingAllowed
     * @return array|boolean
     */
    public function createScore(
        $name,
        $folderId = '',
        $artist = '',
        $published = false,
        $embedWhiteListOnly = false,
        $embedGlobally = false,
        $printingAllowed = false
    )
    {
        // https://www.soundslice.com/help/data-api/#createscore
        $endpoint = 'api/v1/scores/';
        $method = 'POST';
        $query = [
            'name' => $name,
            'artist' => $artist,
            'status' => $published ? 3 : 1,
            'embed_status' => $embedWhiteListOnly ? 4 : ($embedGlobally ? 2 : 1),
            'print_status' => $printingAllowed ? 3 : 1,
            'folder_id' => $folderId
        ];

        $response = $this->request($endpoint, $method, ['form_params' => $query]);

        $body = json_decode((string) $response->getBody());
        $code = json_decode((string) $response->getStatusCode());
        // $reason = json_decode((string) $response->getReasonPhrase());

        if(!preg_match('/^(?=.{3})[2]\d*$/', $code)){
            if($code !== 422 ){
                error_log('Error code ' . $code . ' not expected.');
            }
            error_log('Failed with error:"' . print_r($body->errors ?? '', true) . '"');
            return [false, $body->errors ?? 'Very broken. Check the logs.'];
        }

        if($code !== 201) {
            error_log('SoundsliceService@create succeeded but with unexpected code (' . $code . ')');
        }

        // $url = $body->url;

        $soundsliceSlug = $body->slug;

        // todo: replace with firing event and then catching that in Railcontent (or Musora?)

//        $content = $this->contentService->create(
//            $soundsliceSlug,
//            self::TYPE_FOR_SCORE,
//            ContentService::STATUS_PUBLISHED,
//            null,
//            ConfigService::$brand,
//            null,
//            Carbon::now()->subMinute(),
//            null
//        );
//
//        $contentData = $this->contentDatumService->create($content['id'], self::KEY_FOR_SLUG, $soundsliceSlug, 1);
//
//        if($body->slug !== $contentData['value']){
//            return false;
//        }
//
//        return [
//            'success' => true,
//            'soundsliceSlug' => $body->slug,
//            'contentId' => $content['id']
//        ];
    }

    public function list()
    {
        $response = $this->request('api/v1/scores/');

        $body = (array) json_decode((string) $response->getBody());

        return $body;
    }

    public function createFolder($name)
    {
        $response = $this->request('api/v1/folders/', 'POST', ['form_params' => ['name' => $name]]);
        $body = (array) json_decode((string) $response->getBody());
        return $body['id'] ?? false;
    }

    public function deleteFolder($id)
    {
        $uri = 'api/v1/folders/' . (string) $id;
        $response = $this->request($uri, 'DELETE');

        //$body = (array) json_decode((string) $response->getBody());
        $status = json_decode((string) $response->getStatusCode());

        $success = ($status == 201 || $status == 200) ?? false;

        return $success;
    }

    public function get($slug)
    {
        $response = $this->request('api/v1/scores/' . $slug);

        if(is_null($response)){
            return false;
        }

        $body = (array) json_decode((string) $response->getBody());
        $status = json_decode((string) $response->getStatusCode());

        if($status !== 201 && $status !== 200){ // Soundslice's docs says expect 201, but we're getting 200. No idea why.
            return false;
        }

        return $body;
    }

    /**
     * @param $slug
     * @return bool
     *
     * https://www.soundslice.com/help/data-api/#deletescore
     */
    public function delete($slug)
    {
        $uri = 'api/v1/scores/' . $slug;
        $response = $this->request($uri, 'delete');

        // $body = (array) json_decode((string) $response->getBody());
        $status = json_decode((string) $response->getStatusCode());

        return $status == 201;
    }

    public function getFile($target)
    {
        return $this->filesystem->read($target);
    }

    public function getHashFromFile($fileContents)
    {
        return sha1(substr($fileContents, 0, 1024));
    }

    public function uploadNotationFromS3($slug, $s3Target)
    {
        $tmp_handle = fopen('php://temp', 'r+'); // stackoverflow.com/q/9287368
        fwrite($tmp_handle, $this->getFile($s3Target));
        rewind($tmp_handle);
        $fileContents = stream_get_contents($tmp_handle);

        $fileHash = $this->getHashFromFile($fileContents);

        // Soundslice PUT notation (https://www.soundslice.com/help/data-api/#putnotation), step 1:

        // https://requestb.in/1d5nirg1?inspect
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY
        $callbackUrl = 'https://requestb.in/1d5nirg1'; // TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY TEMPORARY

//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;
//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;
//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;
//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;
//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;
//        $callbackUrl = 'https://xxxxxxxxxxxxxxxxxxxx' . $fileHash;

        $uploadUrl = $this->getNotationUploadUrl($slug, $callbackUrl);

        // Soundslice PUT notation (https://www.soundslice.com/help/data-api/#putnotation), step 2:

        $response = $this->request(
            '',
            'PUT',
            ['body' => $fileContents],
            false,
            $uploadUrl
        );

        fclose($tmp_handle); // clean up temporary storage handle

        if(!$response->getStatusCode() === 200){
            return false;
        }

        // todo: replace with firing event and then catching that in Railcontent (or Musora?)
        // though I think we don't need to do this anymore.
        // though I think we don't need to do this anymore.
        // though I think we don't need to do this anymore.

        // store file hash

//        $content = $this->contentService->getBySlugAndType($slug, self::TYPE_FOR_SCORE);
//        $contentId = reset($content)['id'];
//
//        if(!$this->contentDatumService->create($contentId, self::KEY_FOR_HASH, $fileHash, 1)){
//            error_log('Hash creation failed for content-id: ' . $contentId .'.');
//            return false;
//        }

        return true;
    }

    public function getNotationUploadUrl($slug, $callbackUrl = null)
    {
        $options = [];

        if($callbackUrl){
            $options = ['form_params' => ['callback_url' => $callbackUrl ]];
        }

        $response = $this->request(
            'https://www.soundslice.com/api/v1/scores/' . $slug . '/notation/',
            'POST',
            $options
        );

        if(json_decode((string) $response->getStatusCode()) !== 201){
            return false;
        }

        return ((array) json_decode((string) $response->getBody()))['url'];
    }

    public function processNotificationUploadCallback()
    {
//        $scoreSlug
//        $scoreSlug
//        $scoreSlug
//        $scoreSlug

        // or hash?
    }

//    public function getSize($target)
//    {
//        return $this->filesystem->getSize($target);
//    }

}

