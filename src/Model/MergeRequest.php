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
class MergeRequest extends Model\MergeRequest
{
    public static function castFrom(Model\MergeRequest $mr)
    {
        $cast = new static($mr->project, $mr->id, $mr->getClient());

        foreach (static::$_properties as $property) {
            $cast->$property = $mr->$property;
        }

        return $cast;
    }

    public function toArray()
    {
        $mr = array();

        foreach (static::$_properties as $property) {
            switch ($property) {
                case 'id':
                    $mr['number'] = $this->$property;
                    break;

                case 'author':
                    if (false === isset($mr['head'])) {
                        $mr['head'] = [];
                    }

                    $mr['head']['user'] = $this->$property->username;
                    break;

                case 'state':
                    $mr['state'] = $this->$property;
                    $mr['merged'] = $this->$property === 'merged';

                    if ($mr['merged']) {
                        $mr['message'] = 'Merged ' . $this->title . ' into ' . $this->target_branch;
                    }
                    break;

                case 'source_branch':
                    if (false === isset($mr['head'])) {
                        $mr['head'] = [];
                    }

                    $mr['head']['ref'] = $this->$property;
                    break;

                case 'target_branch':
                    $mr['base'] = array('label' => $this->$property, 'ref' => $this->$property);
                    break;

                default:
                    $mr[$property] = $this->$property;
            }
        }

        return array_replace_recursive(
            [
                'merged' => false,
                'url' => null,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
                'message' => null,
                'body' => null,
                'label' => null,
                'sha' => null,
                'base' => array(
                    'label' => null,
                    'ref' => null
                ),
                'head' => array(
                    'user' => null,
                    'ref' => null,
                )
            ],
            $mr
        );
    }
}
