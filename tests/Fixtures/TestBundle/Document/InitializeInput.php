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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Dto\InitializeInputDto;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
#[ApiResource(input: InitializeInputDto::class)]
class InitializeInput
{
    /**
     * @ODM\Id(strategy="NONE", type="int")
     */
    public $id;
    /**
     * @ODM\Field
     */
    public $manager;
    /**
     * @ODM\Field
     */
    public $name;
}
