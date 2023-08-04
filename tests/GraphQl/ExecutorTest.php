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

namespace ApiPlatform\Tests\GraphQl;

use ApiPlatform\GraphQl\Executor;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ExecutorTest extends TestCase
{
    public function testEnableIntrospectionQuery(): void
    {
        $executor = new Executor(true);

        $expected = new DisableIntrospection(DisableIntrospection::ENABLED);
        $this->assertNotNull($executor);
        $this->assertEquals($expected, DocumentValidator::getRule('DisableIntrospection'));
    }

    public function testDisableIntrospectionQuery(): void
    {
        $executor = new Executor(false);

        $expected = new DisableIntrospection(DisableIntrospection::DISABLED);
        $this->assertNotNull($executor);
        $this->assertEquals($expected, DocumentValidator::getRule('DisableIntrospection'));
    }
}
