<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Models;

use ApiPlatform\Core\Annotation\ApiResource;
use Illuminate\Database\Eloquent\Model;

/**
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     properties={
 *         "id",
 *         "level"={"groups"={"barcelona", "chicago"}},
 *         "test",
 *         "fourthLevel"={"groups"={"barcelona", "chicago", "friends"}},
 *         "badFourthLevel"
 *     }
 * )
 */
class ThirdLevel extends Model
{
    public $timestamps = false;

    protected $attributes = [
        'level' => 3,
        'test' => true,
    ];
}
