<?php

namespace modules\lists\middleware;

class open_accepting extends \sup\middleware {

    public function __invoke($request, $response, $next) {

        if ($this->container->auth->user->level(ROLE_ADMIN)) {
            return $next($request, $response);
        } else if ($this->container->db->get("lists_settings", "open_accepting") != 1) {
            $response->getBody()->write("Nemáte přístup ke schvalování kánonů");
            return $response;
        } else {
            return $next($request, $response);
        }
    }
}
