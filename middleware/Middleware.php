<?php declare(strict_types=1);

namespace modules\subjects\middleware;

use \Slim\Container;

abstract class Middleware extends \SUP\Middleware
{
    protected $settings;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $settings = $this->container->db->get('settings', [
            'active_version_7',
            'active_version_8',
            'open_editing',
            'open_accepting'
        ]);
        $year = $this->container->auth->getUser()->getAttribute('year');

        $year = (!\is_numeric($year) || ($year != 6 && $year != 7)) ? null : $year + 1;
        
        if (!\is_numeric($year)) {
            $this->settings['open_editing'] = false;
        }

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
