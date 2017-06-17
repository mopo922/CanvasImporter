<?php

namespace CanvasImporter\Commands;

use Canvas\Models\Post;
use Illuminate\Console\Command;

class Import extends Command
{
    /** @var string Platform label for WordPress. */
    const PLATFORM_WORDPRESS = 'WordPress';

    /** @var string The name and signature of the console command. */
    protected $signature = 'canvas:import';

    /** @var string The console command description. */
    protected $description = 'Import posts from another blog.';

    /** @var CanvasImporter\Platform\PlatformInterface Platform client object. */
    private $platform;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $source = $this->choice('Platform of the blog to import:', [self::PLATFORM_WORDPRESS]);

        $url = $this->ask('URL of the blog to import:');
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme'])) {
            $url = 'http://' . $url;
        }

        switch ($source) {
            case self::PLATFORM_WORDPRESS:
                $this->configureWordPress($url);
                break;
            default:
                abort(400, 'No platform chosen.');
                break;
        }

        $this->info('Checking credentials...');
        if ($this->platform->checkCredentials()) {
            $this->info('Credentials are valid. Importing posts...');

            $posts = $this->platform->getPosts();
            $postCount = count($posts);
            $bar = $this->output->createProgressBar($postCount);

            foreach ($posts as $post) {
                $post = Post::create($this->platform->convertToCanvas($post));
                $post->syncTags($post['tags']);

                $bar->advance();
            }

            $bar->finish();
            $this->info($postCount . ' posts imported.');
        } else {
            $this->error('Invalid credentials provided.');
        }
    }

    /**
     * Procedure for importing a WordPress blog.
     *
     * @param string $url URL of the blog to import.
     */
    private function configureWordPress(string $url)
    {
        $username = $this->ask('WordPress admin username:');
        $password = $this->secret('WordPress admin password:');

        $this->platform = new Platform\WordPress($url, $username, $password);
    }
}
