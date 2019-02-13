<?php declare(strict_types=1);

namespace modules\subjects\controller\teacher;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class View extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $students = [];
        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], ['version' => [
            $this->settings['active_version_7'],
            $this->settings['active_version_8']
        ], 'state' => 2]) as $entry) {
            $students[] = [
                'user' => $this->container->factory->userFromID($entry['user']),
                'list' => $entry['id'],
                'invalid' => $this->db->has('lists', [
                    '[>]subjects' => ['subject' => 'id']
                ], [
                    'lists.list' => $entry['id'],
                    'subjects.state' => 1
                ])
            ];
        }

        return $this->sendResponse($request, $response, "teacher/view.phtml", ["students" => $students]);
    }
}
