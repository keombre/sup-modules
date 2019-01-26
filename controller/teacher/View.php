<?php declare(strict_types=1);

namespace modules\subjects\controller\teacher;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class View extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        if (array_key_exists('id', $args)) {
            $listID = filter_var($args['id'], FILTER_SANITIZE_STRING);

        //return (new \modules\lists\controller\preview($this->container))->withListID($listID)->preview($request, $response, $args);
        } else {
            $students = [];
            foreach ($this->db->select('main', ['id', 'user'], ['version' => $this->settings['active_version'], 'state' => 2]) as $entry) {
                $students[] = [
                    'user' => $this->container->factory->userFromID($entry['user']),
                    'list' => $entry['id']
                ];
            }

            $response = $this->sendResponse($request, $response, "teacher/view.phtml", ["students" => $students]);
        }
        return $response;
    }
}
