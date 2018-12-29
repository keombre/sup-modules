<?php

namespace modules\lists\controller\admin;

class settings extends \sup\controller {

    function __invoke($request, $response, $args) {

        $data = $request->getParsedBody();
        
        $edit  = filter_var(@$data['edit'],  FILTER_SANITIZE_STRING);
        $accept = filter_var(@$data['accept'], FILTER_SANITIZE_STRING);
        $draw = filter_var(@$data['draw'], FILTER_SANITIZE_STRING);
        $active = filter_var(@$data['active'], FILTER_SANITIZE_STRING);

        if (!$this->container->db->has("versions", ["id" => $active]))
            return $this->redirectWithMessage($response, 'lists', "error", ["Období nenalezeno"]);
        
        $store = [
            "active_version" => $active,
            "open_editing"   => $edit == "on",
            "open_accepting"  => $accept == "on",
            "open_drawing"   => $draw == "on",
        ];
        if ($this->container->db->count("settings"))
            $this->container->db->update("settings", $store);
        else
            $this->container->db->insert("settings", $store);

        return $this->redirectWithMessage($response, 'lists', "status", ["Nastavení uloženo"]);

    }
}