<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChangeRepoVisibility extends Command
{
    protected $signature = 'repo:visibility {visibility}';
    protected $description = 'Change the visibility of a GitHub repository based on URL in .env';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $visibility = $this->argument('visibility');
        $validVisibilities = ['public', 'private'];

        if (!in_array($visibility, $validVisibilities)) {
            $this->error('Invalid visibility. Use "public" or "private".');
            return 1;
        }

        $repoUrl = env('GITHUB_REPO_URL');
        if (!$repoUrl) {
            $this->error('GitHub repository URL not found in .env file.');
            return 1;
        }

        $repo = $this->parseRepoFromUrl($repoUrl);
        if (!$repo) {
            $this->error('Invalid repository URL format.');
            return 1;
        }

        $token = env('GITHUB_TOKEN');
        if (!$token) {
            $this->error('GitHub token not found in .env file.');
            return 1;
        }

        $response = Http::withToken($token)
            ->patch("https://api.github.com/repos/{$repo}", [
                'visibility' => $visibility,
            ]);

        if ($response->successful()) {
            $this->info("Repository '{$repo}' visibility changed to '{$visibility}'.");
        } else {
            $this->error('Failed to change repository visibility. ' . $response->body());
        }
    }

    protected function parseRepoFromUrl($url)
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Extract the repository path from the URL
        if (preg_match('/\/([^\/]+)\/([^\/]+)$/', $path, $matches)) {
            return "{$matches[1]}/{$matches[2]}";
        }

        return null;
    }
}
