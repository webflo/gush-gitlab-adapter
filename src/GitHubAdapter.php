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
use Github\ResultPager;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Aaron Scherer
 */
class GitHubAdapter extends BaseAdapter
{
    /**
     * @var string
     */
    protected $name = 'github';

    /**
     * @var Client
     */
    private $client;

    protected $authenticationType = Client::AUTH_HTTP_PASSWORD;

    /**
     * Initializes the Adapter
     *
     * @return void
     */
    protected function initialize()
    {
        $this->client = $this->buildGitHubClient();
    }

    /**
     * @return Client
     */
    protected function buildGitHubClient()
    {
        $cachedClient = new CachedHttpClient([
            'cache_dir' => $this->configuration->get('cache-dir')
        ]);

        $config = $this->configuration->get('github');
        $client = new Client($cachedClient);
        $client->setOption('base_url', $config['base_url']);

        return $client;
    }

    public function doConfigure(OutputInterface $output, DialogHelper $dialog)
    {
        $config = array();

        $output->writeln('<comment>Enter your GitHub URL (supports Enterprise): </comment>');
        $config['base_url'] = $dialog->askAndValidate(
            $output,
            'Url: ',
            function ($url) {
                return filter_var($url, FILTER_VALIDATE_URL);
            },
            false,
            'https://api.github.com/'
        );

        return $config;
    }

    /**
     * @return Bool
     */
    public function authenticate()
    {
        $credentials = $this->configuration->get('credentials');

        if (Client::AUTH_HTTP_PASSWORD === $credentials['http-auth-type']) {
            $this->client->authenticate(
                $credentials['username'],
                $credentials['password-or-token'],
                $credentials['http-auth-type']
            );

            return;
        }

        $this->client->authenticate(
            $credentials['password-or-token'],
            $credentials['http-auth-type']
        );

        $this->authenticationType = Client::AUTH_HTTP_TOKEN;

        return;
    }

    /**
     * Returns true if the adapter is authenticated, false otherwise
     *
     * @return Bool
     */
    public function isAuthenticated()
    {
        if (Client::AUTH_HTTP_PASSWORD === $this->authenticationType) {
            return is_array(
                $this->client->api('authorizations')
                    ->all()
            );
        }

        return is_array(
            $this->client->api('me')
                ->show()
        );
    }

    /**
     * @param string $subject
     * @param string $body
     * @param array  $options
     *
     * @return mixed
     */
    public function openIssue($subject, $body, array $options = array())
    {
        $api = $this->client->api('issue');
        return $api->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge($options, array('title' => $subject, 'body' => $body))
        );
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getIssue($id)
    {
        $api = $this->client->api('issue');
        return $api->show(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getIssues(array $parameters = array())
    {
        $pager = new ResultPager($this->client);
        return $pager->fetchAll(
            $this->client->api('issue'),
            'all',
            array(
                $this->getUsername(),
                $this->getRepository(),
                $parameters
            )
        );
    }

    /**
     * @param int   $id
     * @param array $parameters
     *
     * @return mixed
     */
    public function updateIssue($id, array $parameters)
    {
        $api = $this->client->api('issue');
        return $api->update(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $parameters
        );
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function closeIssue($id)
    {
        return $this->updateIssue($id, array('state' => 'closed'));
    }

    /**
     * @param int    $id
     * @param string $message
     *
     * @return mixed
     */
    public function createComment($id, $message)
    {
        $api =
            $this->client->api('issue')
                ->comments();
        return $api->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            array('body' => $message)
        );
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getComments($id)
    {
        $pager = new ResultPager($this->client);
        return $pager->fetchAll(
            $this->client->api('issue')
                ->comments(),
            'all',
            array(
                $this->getUsername(),
                $this->getRepository(),
                $id
            )
        );
    }

    /**
     * @return mixed
     */
    public function getLabels()
    {
        $api =
            $this->client->api('issue')
                ->labels();
        return $api->all(
            $this->getUsername(),
            $this->getRepository()
        );
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function getMilestones(array $parameters = array())
    {
        $api =
            $this->client->api('issue')
                ->milestones();
        return $api->all(
            $this->getUsername(),
            $this->getRepository(),
            $parameters
        );
    }

    /**
     * @param string $base
     * @param string $head
     * @param string $subject
     * @param string $body
     * @param array  $parameters
     *
     * @return mixed
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = array())
    {
        $api = $this->client->api('pull_request');
        return $api->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge(
                $parameters,
                array(
                    'base'  => $base,
                    'head'  => $head,
                    'title' => $subject,
                    'body'  => $body,
                )
            )
        );
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getPullRequest($id)
    {
        $api = $this->client->api('pull_request');
        return $api->show(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getPullRequestCommits($id)
    {
        $api = $this->client->api('pull_request');
        return $api->commits(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * @param $id
     * @param $message
     *
     * @return mixed
     */
    public function mergePullRequest($id, $message)
    {
        $api = $this->client->api('pull_request');
        return $api->merge(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $message
        );
    }

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @return mixed
     */
    public function createRelease($name, array $parameters = array())
    {
        $api =
            $this->client->api('repo')
                ->releases();
        return $api->create(
            $this->getUsername(),
            $this->getRepository(),
            array_merge(
                $parameters,
                array(
                    'tag_name' => $name
                )
            )
        );
    }

    /**
     * @return mixed
     */
    public function getReleases()
    {
        $api =
            $this->client->api('repo')
                ->releases();
        return $api->all(
            $this->getUsername(),
            $this->getRepository()
        );
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function removeRelease($id)
    {
        $api =
            $this->client->api('repo')
                ->releases();
        return $api->remove(
            $this->getUsername(),
            $this->getRepository(),
            $id
        );
    }

    /**
     * @param int    $id
     * @param string $name
     * @param string $contentType
     * @param string $content
     *
     * @return mixed
     */
    public function createReleaseAssest($id, $name, $contentType, $content)
    {
        $api =
            $this->client->api('repo')
                ->releases()
                ->assets();
        return $api->create(
            $this->getUsername(),
            $this->getRepository(),
            $id,
            $name,
            $contentType,
            $content
        );
    }
}
