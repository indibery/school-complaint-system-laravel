<?php

use Valet\Drivers\LaravelValetDriver;

class LocalValetDriver extends LaravelValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return parent::serves($sitePath, $siteName, $uri);
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        // /complaints로 시작하는 요청을 /admin-panel/issues로 리라이트
        if (strpos($uri, '/complaints') === 0 && strpos($uri, '/api/') === false) {
            $_SERVER['REQUEST_URI'] = str_replace('/complaints', '/admin-panel/issues', $_SERVER['REQUEST_URI']);
            $uri = str_replace('/complaints', '/admin-panel/issues', $uri);
        }

        return parent::frontControllerPath($sitePath, $siteName, $uri);
    }
}
