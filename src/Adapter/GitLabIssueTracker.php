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

/**
 * @author Luis Cordova <cordoval@gmail.com>
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitLabIssueTracker extends BaseIssueTracker
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    protected $configuration;

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

    /**
     * {@inheritdoc}
     */
    public function openIssue($subject, $body, array $options = [])
    {
        $issue = $this->getCurrentProject()->createIssue(
            $subject,
            [
                'description' => $body
            ]
        );

		return $issue->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssue($id)
    {
        return Issue::fromArray($this->client, $this->getCurrentProject(), $this->client->api('issues')->show($this->getCurrentProject()->id, $id))->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getIssueUrl($id)
    {
        return sprintf(
            '%s/%s/%s/issues/%d',
            $this->configuration['repo_domain_url'],
            $this->getUsername(),
            $this->getRepository(),
            $this->getIssue($id)['iid']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [], $page = 1, $perPage = 30)
    {
        $issues = $this->client->api('issues')->all($this->getCurrentProject()->id);

        if (isset($parameters['state'])) {
            $parameters['state'] = $parameters['state'] === 'open' ? 'opened' : 'closed';

            $issues = array_filter($issues, function ($issue) use ($parameters) { return $issue['state'] === $parameters['state']; });
        }

        if (isset($parameters['creator'])) {
            $issues = array_filter($issues, function ($issue) use ($parameters) { return $issue['user']['login'] === $parameters['creator']; });
        }

        if (isset($parameters['assignee'])) {
            $issues = array_filter($issues, function ($issue) use ($parameters) { return $issue['assignee']['login'] === $parameters['assignee']; });
        }

        return array_map(
            function ($issue) {
                return Issue::fromArray($this->client, $this->getCurrentProject(), $issue)->toArray();
            },
            $issues
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateIssue($id, array $parameters)
    {
        $issue = $this->client->api('issues')->show($this->getCurrentProject()->id, $id);
        $issue = Issue::fromArray($this->client, $this->getCurrentProject(), $issue);

        if (isset($parameters['assignee'])) {
            $assignee = $this->client->api('users')->search($parameters['assignee']);

            if (sizeof($assignee) === 0) {
                throw new \InvalidArgumentException(sprintf('Could not find user %s', $parameters['assignee']));
            }

            $issue->update([
                'assignee_id' => current($assignee)['id']
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $issue = $this->client->api('issues')->show($this->getCurrentProject()->id, $id);
        $issue = Issue::fromArray($this->client, $this->getCurrentProject(), $issue);
    }

    /**
     * {@inheritdoc}
     */
    public function createComment($id, $message)
    {
        $issue = Issue::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('issues')->show($this->getCurrentProject()->id, $id)
        );
        $comment = $issue->addComment($message);

        return sprintf('%s#note_%d', $this->getIssueUrl($id), $comment->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getComments($id)
    {
        $issue = Issue::fromArray(
            $this->client,
            $this->getCurrentProject(),
            $this->client->api('issues')->show($this->getCurrentProject()->id, $id)
        );

        return $issue->showComments();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        throw new Exception\NotSupported('Labels are not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        return array_map(
            function ($milestone) {
                return $milestone['title'];
            },
            $this->client->api('milestones')->all($this->getCurrentProject()->id)
        );
    }
}
