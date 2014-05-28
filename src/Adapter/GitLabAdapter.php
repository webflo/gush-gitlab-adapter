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
use Gush\Config;
use Gush\Model\Issue;
use Gush\Model\MergeRequest;
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

    public function __construct(Config $configuration)
    {
        parent::__construct($configuration);

        $this->client = $this->buildGitLabClient();
    }

    /**
     * @return Client
     */
    protected function buildGitLabClient()
    {
        $config = $this->configuration->get('gitlab');
        $this->url = trim($config['base_url'], '/');
        $this->domain = trim($config['repo_domain_url'], '/');

        $client = new Client($this->domain . '/' . $this->url . '/');

        return $client;
    }

    /**
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
            throw new \RuntimeException(sprintf('Could not guess current %s project, tried %s/%s', static::NAME, $this->getUsername(), $this->getRepository()));
        }

        return $currentProject;
    }

    public static function doConfiguration(OutputInterface $output, DialogHelper $dialog)
    {
        $config = [];

        $config['repo_domain_url'] = $dialog->askAndValidate(
            $output,
            'Gitlab URL (http://gitlab-host): ',
            function ($domain) {
                return rtrim($domain, '/');
            }
        );

        $output->writeln('<comment>Enter your GitLab URL: </comment>');
        $config['base_url'] = $dialog->askAndValidate(
            $output,
            'Gitlab API base URL (/api/v3): ',
            function ($url) {
                return rtrim($url, '/');
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

        if ('http_token' !== $credentials['http-auth-type']) {
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
        return is_array($this->client->api('projects')->owned());
    }

    /**
     * Returns the URL for generating a token.
     * If the adapter does not support tokens, returns null
     *
     * @return null|string
     */
    public function getTokenGenerationUrl()
    {
        return $this->domain . 'profile/account';
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
		return Issue::castFrom($this->getCurrentProject()->createIssue(
			$subject,
			[
				'description' => $body
			]
		))->toArray();
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
        return sprintf('%s/%s/%s/issues/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getIssues(array $parameters = [])
    {
        $issues = $this->client->api('issues')->all($this->getCurrentProject()->id);

        if (isset($parameters['state'])) {
            $parameters['state'] = $parameters['state'] === 'open' ? 'opened' : 'closed';

            $issues = array_filter($issues, function($issue) use($parameters) { return $issue['state'] === $parameters['state']; });
        }

        if (isset($parameters['creator'])) {
            $issues = array_filter($issues, function($issue) use($parameters) { return $issue['user']['login'] === $parameters['creator']; });
        }

        if (isset($parameters['assignee'])) {
            $issues = array_filter($issues, function($issue) use($parameters) { return $issue['assignee']['login'] === $parameters['assignee']; });
        }

        return array_map(
            function($issue) {
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

        return Issue::castFrom($issue)->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function closeIssue($id)
    {
        $issue = $this->client->api('issues')->show($this->getCurrentProject()->id, $id);
        $issue = Issue::fromArray($this->client, $this->getCurrentProject(), $issue);

        return Issue::castFrom($issue->close())->toArray();
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
        throw new Exception\NotSupported('This feature is not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function getMilestones(array $parameters = [])
    {
        return $this->client->api('milestones')->all($this->getCurrentProject()->id);
    }

    /**
     * {@inheritdoc}
     */
    public function openPullRequest($base, $head, $subject, $body, array $parameters = [])
    {
        $head = explode(':', $head);

        $mr = MergeRequest::castFrom(
            $this->getCurrentProject()->createMergeRequest(
                $head[1],
                $base,
                $subject,
				null,
                $body
            )
        );

        return $mr->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequest($id)
    {
        return MergeRequest::fromArray($this->client, $this->getCurrentProject(), $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id))->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestUrl($id)
    {
        return sprintf('%s/%s/%s/merge_requests/%d', $this->domain, $this->getUsername(), $this->getRepository(), $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestCommits($id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function mergePullRequest($id, $message)
    {
        $mr = $this->client->api('merge_requests')->show($this->getCurrentProject()->id, $id);
        $mr = MergeRequest::fromArray($this->client, $this->getCurrentProject(), $mr);

        return $mr->merge($message)->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequests($state = null)
    {
        $mergeRequests = $this->client->api('merge_requests')->all($this->getCurrentProject()->id);

        if (null !== $state) {
            $mergeRequests = array_filter($mergeRequests, function($mr) use($state) { return $mr['state'] === $state; });
        }

        return array_map(
            function($mr) {
                return MergeRequest::fromArray($this->client, $this->getCurrentProject(), $mr)->toArray();
            },
            $mergeRequests
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPullRequestStates()
    {
        return ['opened', 'closed', 'merged'];
    }

    /**
     * {@inheritdoc}
     */
    public function createRelease($name, array $parameters = [])
    {
        throw new Exception\NotSupported('Releases are not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function getReleases()
    {
        throw new Exception\NotSupported('Releases are not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function removeRelease($id)
    {
        throw new Exception\NotSupported('Releases are not supported by Gitlab');
    }

    /**
     * {@inheritdoc}
     */
    public function createReleaseAssets($id, $name, $contentType, $content)
    {
        throw new Exception\NotSupported('Releases are not supported by Gitlab');
    }
}
