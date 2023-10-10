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

namespace ApiPlatform\Symfony\Security\State;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Allows access based on the ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface.
 * This implementation covers GraphQl and HTTP.
 *
 * @see ResourceAccessCheckerInterface
 */
final class AccessCheckerProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $decorated, private readonly ResourceAccessCheckerInterface $resourceAccessChecker, private readonly ?string $event = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        switch ($this->event) {
            case 'post_denormalize':
                $isGranted = $operation->getSecurityPostDenormalize();
                $message = $operation->getSecurityPostDenormalizeMessage();
                break;
            case 'post_validate':
                $isGranted = $operation->getSecurityPostValidation();
                $message = $operation->getSecurityPostValidationMessage();
                break;
            default:
                $isGranted = $operation->getSecurity();
                $message = $operation->getSecurityMessage();
        }

        $body = $this->decorated->provide($operation, $uriVariables, $context);

        // On a GraphQl QueryCollection we want to perform security stage only on the top-level query
        if ($operation instanceof QueryCollection && null !== ($context['source'] ?? null)) {
            return $body;
        }

        if ($operation instanceof HttpOperation) {
            $request = $context['request'] ?? null;

            $resourceAccessCheckerContext = [
                'object' => $body,
                'previous_object' => $request?->attributes->get('previous_data'),
                'request' => $request,
            ];
        } else {
            $resourceAccessCheckerContext = [
                'object' => $body,
                'previous_object' => $context['graphql_context']['previous_object'] ?? null,
            ];
        }

        if ($isGranted && !$this->resourceAccessChecker->isGranted($operation->getClass(), $isGranted, $resourceAccessCheckerContext)) {
            $operation instanceof GraphQlOperation ? throw new AccessDeniedHttpException($message ?? 'Access Denied.') : throw new AccessDeniedException($message ?? 'Access Denied.');
        }

        if ($operation instanceof HttpOperation && $operation->getUriVariables()) {
            foreach ($operation->getUriVariables() as $key => $uriVariable) {
                if (!$uriVariable instanceof Link || !$uriVariable->getSecurity()) {
                    continue;
                }

                $targetResource = $uriVariable->getFromClass() ?? $uriVariable->getToClass();

                if (!$targetResource) {
                    continue;
                }

                // We need to add all attributes here again because we do not know the name of the security object
                $resourceAccessCheckerContext += $request->attributes->all();

                if (!$this->resourceAccessChecker->isGranted($targetResource, $uriVariable->getSecurity(), $resourceAccessCheckerContext)) {
                    throw new AccessDeniedHttpException($uriVariable->getSecurityMessage() ?? 'Access Denied.');
                }
            }
        }

        return $body;
    }
}
