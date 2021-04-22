<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Exception;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Client
{
    /**
     * @var MiddlewarePipeInterface
     */
    protected $pipe;

    /**
     * @param Container $container
     */
    public function __construct(MiddlewarePipeInterface $pipe)
    {
        $this->pipe = $pipe;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string $routeName
     * @param User|null $actor
     * @param Request|null $parent
     * @param array $queryParams
     * @param array $body
     * @return ResponseInterface
     * @throws Exception
     */
    public function send(string $routeName, User $actor = null, Request $parent = null, array $queryParams = [], array $body = []): Response
    {
        $request = ServerRequestFactory::fromGlobals(null, $queryParams, $body);

        if ($parent) {
            $request = $request->withAttribute('session', $parent->getAttribute('session'));
            $request = RequestUtil::withActor($request, RequestUtil::getActor($parent));
        }

        // This should override the actor from the parent request, if one exists.
        if ($actor) {
            $request = RequestUtil::withActor($request, $actor);
        }

        $request = $request->withAttribute('routeName', $routeName);


        return $this->pipe->handle($request);
    }
}
