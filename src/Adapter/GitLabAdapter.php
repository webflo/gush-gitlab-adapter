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
use Gitlab\Model;
use Gush\Exception;
use Gush\Model\Issue;
use Gush\Model\MergeRequest;

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
            foreach ($this->client->api('projects')->accessible(1, 2000) as $project) {
                if ($project['path_with_namespace'] === $this->getUsername() . '/' . $this->getRepository()) {
                    $currentProject = Model\Project::fromArray($this->client, $project);

                    break;
                }
            }
        }

        if (null === $currentProject) {
            throw new \RuntimeException(sprintf('Could not guess current gitlab project, tried %s/%s', $this->getUsername(), $this->getRepository()));
        }

        return $currentProject;
    }

    /**
     * @throws \Exception
     *
     * @return Boolean
     */
    public function authenticate()
    {
        if (Configurator::AUTH_HTTP_TOKEN !== $this->configuration['authentication']['http-auth-type']) {
            throw new \Exception("Authentication type for GitLab must be Token");
        }

        $this->client->authenticate($this->configuration['authentication']['password-or-token'], Client::AUTH_HTTP_TOKEN);

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
}
