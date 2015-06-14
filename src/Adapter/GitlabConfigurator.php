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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class GitlabConfigurator extends DefaultConfigurator
{
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $config = parent::interact($input, $output);

        $config['base_url'] = rtrim($config['base_url'], '/');
        $config['repo_domain_url'] = rtrim($config['repo_domain_url'], '/');

        return $config;
    }


  /**
   * Get the unique name of a configured adapter.
   *
   * @return string
   */
    public function getAdapterName($config) {
        if (empty($config['base_url'])) {
            throw new \Exception('Invalid base_url configuration.');
        }

        $url = parse_url($config['base_url']);
        return $this->getBaseAdapterName() . ':' . $url['host'];
    }
}
