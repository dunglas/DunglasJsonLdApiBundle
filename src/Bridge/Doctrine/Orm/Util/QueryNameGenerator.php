<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * Utility functions for working with Doctrine ORM query.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>

 */
final class QueryNameGenerator implements QueryNameGeneratorInterface
{

    /**
     * @inheritdoc
     */
    public function generateJoinAlias(string $association) : string
    {
        return sprintf('%s_%s', $association, $association);
    }

    /**
     * @inheritdoc
     */
    public function generateParameterName(string $name) : string
    {
        return sprintf('%s_%s', $name, $name);
    }
}
