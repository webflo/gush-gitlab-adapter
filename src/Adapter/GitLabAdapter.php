<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Github\Client;
use Github\HttpClient\CachedHttpClient;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class GitLabAdapter extends GitHubAdapter
{
    const NAME = 'gitlab';

    protected $authenticationType = Client::AUTH_HTTP_TOKEN;

    /**
     * Initializes the Adapter
     *
     * @return void
     */
    protected function initialize()
    {
        $this->client = $this->buildGitLabClient();
    }

    /**
     * @return Client
     */
    protected function buildGitLabClient()
    {
        $config = $this->configuration->get('gitlab');
        $cachedClient = new CachedHttpClient([
            'cache_dir' => $this->configuration->get('cache-dir'),
            'base_url'  => $config['base_url']
        ]);

        $client = new Client($cachedClient);
        $this->url = rtrim($config['base_url'], '/');
        $this->domain = rtrim($config['repo_domain_url'], '/');

        return $client;
    }

    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog)
    {
        $config = [];

        $output->writeln('<comment>Enter your GitLab URL: </comment>');
        $config['base_url'] = $dialog->askAndValidate(
            $output,
            'Api url: ',
            function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            }
        );

        $config['repo_domain_url'] = $dialog->askAndValidate(
            $output,
            'Repo domain url: ',
            function ($field) {
                return $field;
            }
        );

        return $config;
    }

    /**
     * @return Boolean
     */
    public function authenticate()
    {
        $credentials = $this->configuration->get('authentication');

        if (Client::AUTH_HTTP_PASSWORD === $credentials['http-auth-type']) {
            throw new \Exception("Authentication type for GitLab must be Token");
        }

        $this->client->addListener('request.before_send', array(
            new AuthListener($credentials['password-or-token']), 'onRequestBeforeSend'
        ));

        $this->client->authenticate(
            $credentials['password-or-token'],
            null,
            Client::AUTH_HTTP_TOKEN
        );

        return;
    }
}
