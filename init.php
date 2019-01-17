<?php

namespace modules\lists;

final class init {
    
    private $container;

    function __construct(\Slim\Container $container) {
        $this->container = $container;

        if (!$this->container->db->count("settings")) {
            $this->container->db->insert("settings", [
                "open_editing"  => 0,
                "open_accepting" => 0
            ]);
        }
    }
}
