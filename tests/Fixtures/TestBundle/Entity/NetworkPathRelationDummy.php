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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::NET_PATH)]
#[ORM\Entity]
class NetworkPathRelationDummy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\OneToMany(targetEntity: NetworkPathDummy::class, mappedBy: 'networkPathRelationDummy')]
    public Collection|iterable $networkPathDummies;

    public function __construct()
    {
        $this->networkPathDummies = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
