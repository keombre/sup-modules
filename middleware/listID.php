<?php

namespace modules\lists\middleware;

class listID extends \sup\middleware {

    public function __invoke($request, $response, $next) {
        
        $listID = $request->getAttributes()['route']->getArgument('id');

        if (is_null($listID))
            return $next($request, $response);
        
        $id = filter_var($listID, FILTER_SANITIZE_STRING);
        $version = $this->container->db->get("lists_settings", "active_version");

        if (!is_numeric($version))
            return $response->withRedirect($this->container->router->pathFor('lists'), 301);
        
        if ($this->container->auth->user->level(ROLE_STUDENT)) {
            $userID = $this->container->auth->user->getInfo('id');

            if ($this->container->db->has("lists_main", ["id" => $id, "user" => $userID, "version" => $version]))
                return $next($request, $response);
            else
                return $this->redirectWithMessage($response, 'lists', "error", ["Kánon nenalezen"]);
        } else if ($this->container->auth->user->level([ROLE_TEACHER, ROLE_ADMIN])) {
            if ($this->container->db->has("lists_main", ["id" => $id, "version" => $version]))
                return $next($request, $response);
            else
                return $this->redirectWithMessage($response, 'lists', "error", ["Kánon nenalezen"]);
        }
        
    }
}