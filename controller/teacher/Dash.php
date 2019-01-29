<?php declare(strict_types=1);

namespace modules\subjects\controller\teacher;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $subjects = $this->db->select('subjects', [
            'id [Index]',
            'name [String]',
            'annotation [String]'
        ], ['version' => [
            $this->settings['active_version_7'],
            $this->settings['active_version_8']
        ]]);

        $count = array_count_values($this->db->select(
            'lists',
            ['[>]main' => ['list' => 'id']],
            'subject',
            ['main.version' => [
                $this->settings['active_version_7'],
                $this->settings['active_version_8']
            ], 'main.state' => 2]
        ));
        arsort($count);

        return $this->sendResponse($request, $response, "teacher/dash.phtml", [
            "subjects" => $subjects,
            "count" => $count,
            "allowAccepting" => $this->settings['open_accepting'] == 1
        ]);
    }
}
