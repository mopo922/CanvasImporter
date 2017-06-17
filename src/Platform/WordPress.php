<?php

namespace CanvasImporter\Platform;

use GuzzleHttp\Client as Guzzle;

class WordPress implements PlatformInterface
{
    /** @var string $apiUrl API URL of the blog to be imported. */
    private $apiUrl;

    /** @var string $username Admin username of the blog to be imported. */
    private $username;

    /** @var string $password Admin password of the blog to be imported. */
    private $password;

    /**
     * Configure the Platform login credentials.
     *
     * @param string $url Base URL of the blog to be imported.
     * @param string $username Admin username of the blog to be imported.
     * @param string $password Admin password of the blog to be imported.
     */
    public function __construct(string $url, string $username, string $password)
    {
        $this->apiUrl = $url . 'wp-json/wp/v2/';
        $this->username = $username;
        $this->password = $password;
        $this->client = new Guzzle();
    }

    /**
     *
     */
    private function endpoint(string $endpoint, array $params = [])
    {
        $endpoint = strtolower($endpoint);
        $validEndpoints = ['posts'];

        if (!in_array($endpoint, $validEndpoints)) {
            abort(500, 'Invalid endpoint requested.');
        }

        return $this->apiUrl . $endpoint . '?' . http_build_query($params);
    }

    /**
     * Confirm the accuracy of the given authentication credentials.
     *
     * @return bool True if the credentials are valid admin credentials.
     */
    public function checkCredentials()
    {
        try {
            // @TODO IS "USERS" A BETTER ENDPOINT TO CHECK?
            $result = $this->client->request(
                'GET',
                $this->endpoint('posts', ['status' => 'draft']),
                ['auth' => [$this->username, $this->password]]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all posts, including drafts.
     *
     * @return array
     */
    public function getPosts()
    {
        try {
            $result = $this->client->request(
                'GET',
                $this->endpoint('posts', [
                    'status' => 'draft,publish',
                    'type' => 'post',
                ]),
                ['auth' => [$this->username, $this->password]]
            );
            return json_decode($result->getBody());
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Convert post data from platform's format to Canvas's format.
     *
     * @param array $post Array of data from the platform.
     * @return array Array of data, formatted for Canvas:
     *         - user_id
     *         - slug
     *         - title
     *         - subtitle
     *         - content_raw
     *         - page_image
     *         - meta_description
     *         - is_published
     *         - layout
     *         - published_at
     *         - tags
     */
    public function convertToCanvas(array $post)
    {
        return [
            'user_id' => x, // @TODO TRANSLATE USERS FROM $post['author']
            'slug' => $post['slug'],
            'title' => $post['title']['rendered'],
            // 'subtitle' => x,
            'content_raw' => $post['content']['rendered'], // @TODO ANY SCRUBBING REQUIRED?
            'page_image' => x, // @TODO GET THIS FROM $post['featured_media']
            // 'meta_description' => x,
            'is_published' => $post['status'] === 'publish',
            'layout' => 'canvas::frontend.blog.post',
            'published_at' => str_replace('T', ' ', $post['date_gmt']), // @TODO ALSO AVAILABLE LOCALIZED AS "date"
            'tags' => $post['tags'], // @TODO TRANSLATE TO STRINGS FROM IDS, INCLUDE CATEGORIES TOO?
        ];
    }
}
