<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Adapter;

use Buzz\Message\Response;
use Gitlab\Client;
use Gitlab\Model;
use Gush\Exception\AdapterException;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
trait GitLabAdapter
{
    /**
     * @var Client|null
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @throws \RuntimeException
     *
     * @return Model\Project
     */
    protected function getCurrentProject()
    {
        static $currentProject;

        if (null === $currentProject) {
            $currentProject = $this->findProject($this->getUsername(), $this->getRepository());
        }

        if (null === $currentProject) {
            throw new \RuntimeException(
                sprintf(
                    'Could not guess current gitlab project, tried %s/%s',
                    $this->getUsername(),
                    $this->getRepository()
                )
            );
        }

        return $currentProject;
    }

    protected function findProject($namespace, $projectName)
    {
        $projects = $this->client->api('projects')->accessible(1, 100);
        $currentModel = null;

        // loop until we find a matching project or run-out of pages
        while (true) {
            foreach ($projects as $project) {
                if ($project['path_with_namespace'] === $namespace.'/'.$projectName) {
                    $currentModel = Model\Project::fromArray($this->client, $project);

                    break 2;
                }
            }

            $paginating = $this->getPagination($this->client->getHttpClient()->getLastResponse());

            if (!isset($paginating['next'])) {
                break;
            }

            $projects = $this->client->getHttpClient()->get(
                substr($paginating['next'], strlen($this->configuration['base_url']))
            )->getContent();
        }

        if (!$currentModel) {
            throw new \RuntimeException(
                sprintf('Could not find gitlab project id with %s/%s', $namespace, $projectName)
            );
        }

        return $currentModel;
    }

    /**
     * @throws \Exception
     *
     * @return Boolean
     */
    public function authenticate()
    {
        if (Configurator::AUTH_HTTP_TOKEN !== $this->configuration['authentication']['http-auth-type']) {
            throw new AdapterException('Authentication type for GitLab must be Token.');
        }

        $this->client->authenticate(
            $this->configuration['authentication']['password-or-token'],
            Client::AUTH_HTTP_TOKEN
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return is_array($this->client->api('projects')->owned());
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenGenerationUrl()
    {
        return sprintf('%/profile/account', $this->configuration['repo_domain_url']);
    }

    protected static function getPagination(Response $response)
    {
        $header = $response->getHeader('Link');

        if (empty($header)) {
            return;
        }

        $pagination = [];

        foreach (explode(',', $header) as $link) {
            preg_match('/<(.*)>; rel="(.*)"/i', trim($link, ','), $match);

            if (3 === count($match)) {
                $pagination[$match[2]] = $match[1];
            }
        }

        return $pagination;
    }
}
