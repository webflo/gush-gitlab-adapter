<?php

/*
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Model;

use Gitlab\Model;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Project extends Model\Project
{
    public static function castFrom(Model\Project $project)
    {
        $cast = new static($project->id, $project->getClient());

        foreach (static::$_properties as $property) {
            $cast->$property = $project->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $project = [
            'owner' => null,
            'html_url' => null,
            'fetch_url' => null,
            'push_url' => null,
            'is_fork' => false,
            'is_private' => true,
            'fork_origin' => null,
        ];

        foreach (static::$_properties as $property) {
            switch ($property) {
                case 'owner':
                    $project['owner'] = $this->{$property}->username;
                    break;

                case 'web_url':
                    $project['html_url'] = $this->$property;
                    break;

                case 'public':
                    $project['is_private'] = !$this->$property;
                    break;

                case 'ssh_url_to_repo':
                    $project['fetch_url'] = $this->$property;
                    $project['push_url'] = $this->$property;
                    break;
            }
        }

        return $project;
    }
}
