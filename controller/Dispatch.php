<?php declare(strict_types=1);

namespace modules\subjects\controller;

use SUP\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dispatch extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $userLevel = $this->container->auth->getUser()->getAttribute('activeRole');

        switch ($userLevel) {
            case ROLE_STUDENT:
                return $response->withRedirect($this->container->router->pathFor('subjects-student'), 301);
            case ROLE_TEACHER:
                return $response->withRedirect($this->container->router->pathFor('subjects-teacher'), 301);
            case ROLE_ADMIN:
                return $response->withRedirect($this->container->router->pathFor('subjects-admin'), 301);
            
            default:
                return $response->withRedirect($this->container->router->pathFor('dashboard'), 301);
        }
    }
}
