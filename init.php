<?php declare(strict_types=1);

namespace modules\subjects;

final class Init
{
    private $container;

    public function __construct(\Slim\Container $container)
    {
        $this->container = $container;

        if (!$this->container->db->count("settings")) {
            $this->container->db->insert("settings", [
                "open_editing"  => 0,
                "open_accepting" => 0
            ]);
        }
    }
}
