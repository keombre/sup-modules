<?php

namespace modules\lists\middleware;

class open_drawing extends \sup\middleware {

    public function __invoke($request, $response, $next) {

        $user = $this->container->auth->getUser();
        if ($user->is(ROLE_ADMIN)) {
            return $next($request, $response);
        } else if ($this->container->db->get("settings", "open_drawing [Int]") == 0) {
            return $response->withRedirect($this->container->router->pathFor('lists'), 301); 
        } else {
            return $next($request, $response);
        }
    }
}
