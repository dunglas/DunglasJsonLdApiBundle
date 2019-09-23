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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpClient\HttpClientTrait;

/**
 * Base class for functional API tests.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class ApiTestCase extends KernelTestCase
{
    use ApiTestAssertionsTrait;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        self::getClient(null);
    }

    /**
     * Creates a Client.
     *
     * @param array $kernelOptions  Options to pass to the createKernel method
     * @param array $defaultOptions Default options for the requests
     */
    protected static function createClient(array $kernelOptions = [], array $defaultOptions = []): Client
    {
        $kernel = static::bootKernel($kernelOptions);

        try {
            /**
             * @var Client
             */
            $client = $kernel->getContainer()->get('test.api_platform.client');
        } catch (ServiceNotFoundException $e) {
            if (!class_exists(AbstractBrowser::class) || !trait_exists(HttpClientTrait::class)) {
                throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit and HttpClient components are not available. Try running "composer require --dev symfony/browser-kit symfony/http-client".');
            }

            throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
        }

        $client->setDefaultOptions($defaultOptions);

        self::getHttpClient($client);
        self::getClient($client->getKernelBrowser());

        return $client;
    }

    /**
     * Finds the IRI of the resource of the given class, corresponding to the given criteria.
     */
    protected function findIriBy(string $resourceClass, array $criteria): ?string
    {
        if (
            (
                !static::$container->has('doctrine') ||
                null === $objectManager = static::$container->get('doctrine')->getManagerForClass($resourceClass)
            ) &&
            (
                !static::$container->has('doctrine_mongodb') ||
                null === $objectManager = static::$container->get('doctrine_mongodb.odm.default_document_manager')->getManagerForClass($resourceClass)
            )
        ) {
            throw new \RuntimeException(sprintf('"%s" only supports classes managed by Doctrine ORM or Doctrine MongoDB ODM. Override this method to implement your own retrieval logic if you don\'t use those libraries.', __METHOD__));
        }

        $resource = $objectManager->getRepository($resourceClass)->findOneBy($criteria);
        if (null === $resource) {
            return null;
        }

        return static::$container->get('api_platform.iri_converter')->getIriFromitem($resource);
    }
}
