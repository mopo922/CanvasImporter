<?php

namespace CanvasImporter\Platform;

use Canvas\Models\User;
use GuzzleHttp\Client as Guzzle;
use League\HTMLToMarkdown\HtmlConverter;

class WordPress implements PlatformInterface
{
    /** @var string $apiUrl API URL of the blog to be imported. */
    private $apiUrl;

    /** @var string $username Admin username of the blog to be imported. */
    private $username;

    /** @var string $password Admin password of the blog to be imported. */
    private $password;

    /** @var GuzzleHttp\Client $client Guzzle (cURL) client. */
    private $client;

    /** @var string $publicPath Relative location to store media files. Used for storing the Post record. */
    private $publicPath;

    /** @var string $storagePath Absolute location to store media files. */
    private $storagePath;

    /** @var array $categories Categories fetched from WordPress. */
    private $categories;

    /** @var array $posts Posts fetched from WordPress. */
    private $posts;

    /** @var array $tags Tags fetched from WordPress. */
    private $tags;

    /** @var array $users Users fetched from WordPress. */
    private $users;

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
        $this->publicPath = '/import/' . date('Y-m-d') . '/';
        $this->storagePath = storage_path('app/public' . $this->publicPath);
    }

    /**
     *
     */
    private function endpoint(string $endpoint, $params)
    {
        $endpoint = strtolower($endpoint);
        $validEndpoints = ['posts'];

        if (!in_array($endpoint, $validEndpoints)) {
            abort(500, 'Invalid endpoint requested.');
        }

        $suffix = '';
        if ($params) {
            $suffix = is_array($params)
                ? '?' . http_build_query($params)
                : '/' . $params;
        }

        return $this->apiUrl . $endpoint . $suffix;
    }

    /**
     *
     */
    private function get(string $endpoint, $params)
    {
        try {
            $result = $this->client->request(
                'GET',
                $this->endpoint($endpoint, $params),
                ['auth' => [$this->username, $this->password]]
            );
            return json_decode($result->getBody());
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Confirm the accuracy of the given authentication credentials.
     *
     * @return bool True if the credentials are valid admin credentials.
     */
    public function checkCredentials()
    {
        try {
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
     * Get all Posts, including drafts, from WordPress.
     *
     * @return array
     */
    public function getPosts()
    {
        if (!is_array($this->posts)) {
            $this->posts = $this->get('posts', [
                'status' => 'draft,publish',
                'type' => 'post',
            ]);
        }

        return $this->posts;
    }

    /**
     * Get all Users from WordPress.
     *
     * @return array
     */
    public function getUsers()
    {
        if (!is_array($this->users)) {
            $users = $this->get('users', [
                'roles' => 'administrator,editor,author,contributor',
            ]);

            foreach ($users as $user) {
                $this->users[$user['id']] = $user;
            }
        }

        return $this->users;
    }

    /**
     * Get all Categories from WordPress.
     *
     * @return array
     */
    public function getCategories()
    {
        if (!is_array($this->categories)) {
            $categories = $this->get('categories');

            foreach ($categories as $category) {
                $this->categories[$category['id']] = $category;
            }
        }

        return $this->categories;
    }

    /**
     * Get all Tags from WordPress.
     *
     * @return array
     */
    public function getTags()
    {
        if (!is_array($this->tags)) {
            $tags = $this->get('tags');

            foreach ($tags as $tag) {
                $this->tags[$tag['id']] = $tag;
            }
        }

        return $this->tags;
    }

    /**
     * Convert post data from platform's format to Canvas's format.
     *
     * @param array $post Array of data from the platform.
     * @return array Array of data, formatted for Canvas.
     */
    public function convertToCanvas(array $post)
    {
        return [
            'user_id' => $this->getUserId($post),
            'slug' => $post['slug'],
            'title' => $post['title']['rendered'],
            // 'subtitle' => '',
            'content_raw' => $this->getContentMarkdown($post),
            'page_image' => $this->getFeaturedImage($post),
            // 'meta_description' => '',
            'is_published' => $post['status'] === 'publish',
            'layout' => config('blog.post_layout');
            'published_at' => str_replace('T', ' ', $post['date_gmt']),
            'tags' => $this->getCombinedTags($post),
        ];
    }

    /**
     * Get the corresponding Canvas User ID, defaulting to 1 (original admin user) if no match found.
     *
     * @param array $post Array of data from the platform.
     * @return int Canvas User ID.
     */
    private function getUserId(array $post)
    {
        $userEmail = $this->getUsers()[$post['author']]['email'];
        $user = User::where('email', $userEmail)->first();

        return $user ? $user->id : 1;
    }

    /**
     * Get the Post content in Markdown format.
     *
     * @param array $post Array of data from the platform.
     * @return string Post content in Markdown format.
     */
    private function getContentMarkdown(array $post)
    {
        $converter = new HtmlConverter(['strip_tags' => true]);

        return $converter->convert($post['content']['rendered']);
    }

    /**
     * Combine WP Tags and Categories into Canvas Tags.
     *
     * @param array $post Array of data from the platform.
     * @return array List of tag names.
     */
    private function getCombinedTags(array $post)
    {
        $tags = [];

        foreach ($post['categories'] as $categoryId) {
            $tags[] = $this->formatTagName($this->getCategories()[$categoryId]['name']);
        }
        foreach ($post['tags'] as $tagId) {
            $tags[] = $this->formatTagName($this->getTags()[$tagId]['name']);
        }

        return array_unique($tags);
    }

    /**
     * Convert a tag to "Proper Case"
     *
     * @param string $tag The original tag/category name from WP.
     * @return string Tag name, with Uppercase Words.
     */
    private function formatTagName(string $tag)
    {
        return ucwords(strtolower(str_replace('-', ' ', $tag)));
    }

    /**
     * Download the featured image from WP.
     *
     * @param array $post Array of data from the platform.
     * @return string The local path of the image file.
     */
    private function getFeaturedImage(array $post)
    {
        $pageImage = null;

        if ($post['featured_media']) {
            $media = $this->get('media', $post['featured_media']);
            $target = str_replace('/', '-', $media['media_details']['file']);
            copy(
                $media['source_url'],
                $this->storagePath . $target
            );
            $pageImage = $this->publicPath . $target;
        }

        return $pageImage;
    }
}
