<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\timers;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $timers = [];

        $timers[3] = $this->db->get('versions', [
            'timer1(1) [JSON]',
            'timer2(2) [JSON]'
        ], [
            'id' => $this->settings['active_version_7']
        ]);

        $timers[4] = $this->db->get('versions', [
            'timer1(1) [JSON]',
            'timer2(2) [JSON]'
        ], [
            'id' => $this->settings['active_version_8']
        ]);

        $response = $this->sendResponse($request, $response, "admin/timers/dash.phtml", [
            'time' => $timers,
            "sidebar_active" => "timers"
        ]);

        return $response;
    }
    
}
