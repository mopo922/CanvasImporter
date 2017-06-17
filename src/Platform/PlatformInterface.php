<?php

namespace CanvasImporter\Platform;

interface PlatformInterface {
    /**
     * Confirm the accuracy of the given authentication credentials.
     *
     * @return bool True if the credentials are valid admin credentials.
     */
    public function checkCredentials();

    /**
     * Get all Posts, including drafts.
     *
     * @return array
     */
    public function getPosts();

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
    public function convertToCanvas(array $post);
}
