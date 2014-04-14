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

use Gitlab\Client;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class GitLabAdapter extends BaseAdapter
{
    const NAME = 'gitlab';

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var string|null
     */
    protected $domain;

    /**
     * @var Client|null
     */
    private $client;

    /**
     * @var string
     */
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
        $this->url = rtrim($config['base_url'], '/');
        $this->domain = rtrim($config['repo_domain_url'], '/');

        $client = new Client($this->url);

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
     * @throws \Exception
     * @return Boolean
     */
    public function authenticate()
    {
        $credentials = $this->configuration->get('authentication');

        if (0 === $credentials['http-auth-type']) {
            throw new \Exception("Authentication type for GitLab must be Token");
        }

        $this->client->authenticate($credentials['password-or-token'], Client::AUTH_HTTP_TOKEN);

        $this->authenticationType = Client::AUTH_HTTP_TOKEN;

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        $result = $this->client->api('projects')->owned();
        ladybug_dump_die($result);
        //return is_array($this->client->api('projects')->owned());
    }

    /**
     * Returns the URL for generating a token.
     * If the adapter does not support tokens, returns null
     *
     * @return null|string
     */
    public function getTokenGenerationUrl()
    {
        // TODO: Implement getTokenGenerationUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createFork($org)
    {
        // TODO: Implement createFork() method.
    }

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        // TODO: Implement openIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        // TODO: Implement getIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        // TODO: Implement getIssueUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [])
    {
        // TODO: Implement getIssues() method.
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        // TODO: Implement updateIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        // TODO: Implement closeIssue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        // TODO: Implement createComment() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        // TODO: Implement getComments() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        // TODO: Implement getLabels() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        // TODO: Implement getMilestones() method.
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        // TODO: Implement openPullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        // TODO: Implement getPullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        // TODO: Implement getPullRequestUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        // TODO: Implement getPullRequestCommits() method.
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        // TODO: Implement mergePullRequest() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests()
    {
        // TODO: Implement getPullRequests() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        // TODO: Implement createRelease() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        // TODO: Implement getReleases() method.
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        // TODO: Implement removeRelease() method.
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        // TODO: Implement createReleaseAssets() method.
    }
}
