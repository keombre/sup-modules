<?php

namespace modules\subjects\middleware\student;

use \SUP\Middleware;
use Slim\Http\Request;
use Slim\Http\Response;

class OpenEditing extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        $user = $this->container->auth->getUser();

        if ($user->canBecome(ROLE_TEACHER) || $user->canBecome(ROLE_ADMIN)) {
            return $next($request, $response);

        } else if ($this->container->db->get("settings", "open_editing [Int]") != 1) {
            $response->getBody()->write($this->container->lang->g('denied', 'student-dash'));
            return $response;
        } else {
            return $next($request, $response);
        }
    }
}