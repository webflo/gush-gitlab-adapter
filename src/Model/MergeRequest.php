<?php

/**
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
 * @author Julien Bianchi <contact@jubianchi.fr>
 */
class MergeRequest extends Model\Issue
{
    public static function castFrom(Model\Issue $issue)
    {
        $cast = new static($issue->project, $issue->id, $issue->getClient());

        foreach (static::$_properties as $property) {
            $cast->$property = $issue->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $issue = array();

        foreach (static::$_properties as $property) {
            switch ($property) {
                case 'iid':
                    $issue['number'] = $this->$property . ' (' . $this->id . ')';
                    break;

                case 'author':
                    $issue['head'] = ['user' => User::castFrom($this->$property)->toArray()];
                    break;

                default:
                    $issue[$property] = $this->$property;
            }
        }

        return $issue;
    }
}
