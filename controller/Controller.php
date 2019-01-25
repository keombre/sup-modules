<?php declare(strict_types=1);

namespace modules\subjects\controller;

use \Slim\Container;

abstract class Controller extends \SUP\Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->settings = $this->db->get('settings', [
            'active_version',
            'open_editing',
            'open_accepting',
            'subject_limit'
        ]);
    }
}
