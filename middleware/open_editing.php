<?php

namespace modules\lists\middleware;

class open_editing extends \sup\middleware {

    public function __invoke($request, $response, $next) {

        if ($this->container->auth->user->level([ROLE_TEACHER, ROLE_ADMIN])) {
            return $next($request, $response);
        } else if (!$this->container->db->get("settings", "open_editing")) {
            return $response->withRedirect($this->container->router->pathFor('lists'), 301); 
        } else {
            return $next($request, $response);
        }
    }
}