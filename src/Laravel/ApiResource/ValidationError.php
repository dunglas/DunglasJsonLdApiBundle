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

namespace ApiPlatform\Laravel\ApiResource;

use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\WebLink\Link;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[ErrorResource(
    uriTemplate: '/validation_errors/{id}',
    status: 422,
    openapi: false,
    uriVariables: ['id'],
    shortName: 'ValidationError',
    operations: [
        new ErrorOperation(
            name: '_api_validation_errors_problem',
            outputFormats: ['json' => ['application/problem+json']],
            normalizationContext: ['groups' => ['json'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ],
            uriTemplate: '/validation_errors/{id}'
        ),
        new ErrorOperation(
            name: '_api_validation_errors_hydra',
            outputFormats: ['jsonld' => ['application/problem+json']],
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ],
            uriTemplate: '/validation_errors/{id}.jsonld'
        ),
        new ErrorOperation(
            name: '_api_validation_errors_jsonapi',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: ['groups' => ['jsonapi'], 'skip_null_values' => true, 'rfc_7807_compliant_errors' => true],
            uriTemplate: '/validation_errors/{id}.jsonapi'
        ),
    ],
    graphQlOperations: []
)]
class ValidationError extends RuntimeException implements \Stringable, ProblemExceptionInterface, HttpExceptionInterface, SymfonyHttpExceptionInterface
{
    private int $status = 422;
    private mixed $id;

    public function __construct(string $message = '', mixed $code = null, int|\Throwable|null $previous = null, protected array $violations = [])
    {
        $this->id = $code;
        $this->setDetail($message);
        parent::__construct($message ?: $this->__toString(), 422, $previous);
    }

    public function getId()
    {
        return $this->id;
    }

    #[SerializedName('description')]
    #[Groups(['jsonapi', 'jsonld', 'json'])]
    public function getDescription(): string
    {
        return $this->detail;
    }

    #[Groups(['jsonld', 'json'])]
    public function getType(): string
    {
        return '/validation_errors/'.$this->id;
    }

    #[Groups(['jsonld', 'json'])]
    public function getTitle(): ?string
    {
        return 'Validation Error';
    }

    #[Groups(['jsonld', 'json'])]
    private string $detail;

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): void
    {
        $this->detail = $detail;
    }

    #[Groups(['jsonld', 'json'])]
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    #[Groups(['jsonld', 'json'])]
    public function getInstance(): ?string
    {
        return null;
    }

    #[SerializedName('violations')]
    #[Groups(['json', 'jsonld'])]
    /**
     * @return array<int, array{propertyPath: string, message: string}>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
