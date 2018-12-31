<?php

namespace modules\lists\middleware;

class listID extends \sup\middleware {

    public function __invoke($request, $response, $next) {
        
        $listID = $request->getAttributes()['route']->getArgument('id');

        if (is_null($listID))
            return $next($request, $response);
        
        $id = filter_var($listID, FILTER_SANITIZE_STRING);
        $version = $this->container->db->get("settings", "active_version");

        if (!is_numeric($version))
            return $response->withRedirect($this->container->router->pathFor('lists'), 301);

        $user = $this->container->auth->getUser();
        
        if ($user->is(ROLE_STUDENT)) {
            $userID = $user->getID();

            if ($this->container->db->has("main", ["id" => $id, "user" => $userID, "version" => $version]))
                return $next($request, $response);
            else
                return $this->redirectWithMessage($response, 'lists', "error", ["Kánon nenalezen"]);
        } else if ($user->is(ROLE_TEACHER) || $user->is(ROLE_ADMIN)) {
            if ($this->container->db->has("main", ["id" => $id, "version" => $version]))
                return $next($request, $response);
            else
                return $this->redirectWithMessage($response, 'lists', "error", ["Kánon nenalezen"]);
        }
        
    }
}
