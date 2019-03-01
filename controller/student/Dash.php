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
            'state [Int]',
            'accepted_by [Int]'
        ], [
            'user' => $this->container->auth->getUser()->getID(),
            'version' => $this->settings['active_version']
        ]);

        if ($list) {
            if ($list['state'] == 2) {
                $list['accepted_by'] = $this->container->factory->userFromID($list['accepted_by']);
            }
        }

        $limit = $this->db->get('versions', ['limit [Int]'], ['id' => $this->settings['active_version']])['limit'];

        $response = $this->sendResponse($request, $response, 'student/dash.phtml', [
            'list' => $list,
            'limit' => $limit
        ]);
    }
}
