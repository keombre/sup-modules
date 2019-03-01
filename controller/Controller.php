<?php declare(strict_types=1);

namespace modules\subjects\controller;

use \Slim\Container;

abstract class Controller extends \SUP\Controller
{
    protected $settings;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $settings = $this->db->get('settings', [
            'active_version_7',
            'active_version_8',
            'open_editing',
            'open_accepting'
        ]);
        $year = $this->container->auth->getUser()->getAttribute('year');
        $year = (!\is_numeric($year) || ($year != 6 && $year != 7)) ? null : $year + 1;

        $this->settings = [
            'open_editing' => \is_null($year) ? false : $settings['open_editing'],
            'open_accepting' => $settings['open_accepting'],
            'active_version' => \is_null($year) ? null : [
                7 => $settings['active_version_7'],
                8 => $settings['active_version_8']
            ][$year],
            'active_version_7' => $settings['active_version_7'],
            'active_version_8' => $settings['active_version_8']
        ];

    }
}
