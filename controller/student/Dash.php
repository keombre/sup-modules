<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $list = $this->db->get('main', [
            'id [Index]',
            'created [Int]',
            'state [Int]'
        ], [
            'user' => $this->container->auth->getUser()->getID(),
            'version' => $this->settings['active_version']
        ]);
        $limit = $this->db->get('versions', ['limit [Int]', 'limit_spare [Int]', 'timer1 [JSON]', 'timer2 [JSON]'], ['id' => $this->settings['active_version']]);
        
        if (is_null($limit['timer1']) || is_null($limit['timer2'])) {
            $action_type = 0;
            $date_since = ['time' => '', 'date' => ''];
            $date_to = ['time' => '', 'date' => ''];
        } else {
            $times = [
                1 => [
                    'open' => strtotime(implode(' ', $limit['timer1']['open'])),
                    'close' => strtotime(implode(' ', $limit['timer1']['close'])),
                ],
                2 => [
                    'open' => strtotime(implode(' ', $limit['timer2']['open'])),
                    'close' => strtotime(implode(' ', $limit['timer2']['close'])),
                ]
            ];
            
            if ($times[1]['open'] > time()) {
                $action_type = 0;
                $date_since = null;
                $date_to = $limit['timer1']['open'];
            } else if ($times[1]['close'] > time()) {
                $action_type = 1;
                $date_since = $limit['timer1']['open'];
                $date_to = $limit['timer1']['close'];
            } else if ($times[2]['open'] > time()) {
                $action_type = 2;
                $date_since = $limit['timer1']['close'];
                $date_to = $limit['timer2']['open'];
            } else if ($times[2]['close'] > time()) {
                $action_type = 3;
                $date_since = $limit['timer2']['open'];
                $date_to = $limit['timer2']['close'];
            } else {
                $action_type = 4;
                $date_since = $limit['timer2']['close'];
                $date_to = null;
            }
        }

        $response = $this->sendResponse($request, $response, 'student/dash.phtml', [
            'list' => $list,
            'limit' => $limit,
            'action_type' => $action_type,
            'date_since' => $date_since,
            'date_to' => $date_to
        ]);
    }
}
