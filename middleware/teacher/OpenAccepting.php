<?php

namespace modules\subjects\middleware\teacher;

use \SUP\Middleware;
use Slim\Http\Request;
use Slim\Http\Response;

class OpenAccepting extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if ($this->container->auth->getUser()->canBecome(ROLE_ADMIN)) {
            return $next($request, $response);

        } else if ($this->container->db->get("settings", "open_accepting [Int]") != 1) {
            $response->getBody()->write($this->container->lang->g('denied', 'teacher-dash'));
            return $response;
        } else {
            return $next($request, $response);
        }
    }
}