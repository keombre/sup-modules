<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $subjectes = $this->db->select('subjects',[
            'id [Index]',
            'name [String]',
            'annotation [String]'
        ], [
            'version' => $this->settings['active_version']
        ]);

        $selected = $this->db->select('lists', [
            '[>]main' => ['list' => 'id']
        ], [
            'lists.subject [Index]',
            'lists.level [Int]'
        ], [
            'main.user' => $this->container->auth->getUser()->getID(),
            'main.version' => $this->settings['active_version']
        ]);

        foreach ($subjectes as $id => $subject) {
            $subjectes[$id]['level'] = $selected[$id]['level'] ?? false;
        }

        $this->sendResponse($request, $response, "student/edit.phtml", [
            "subjects" => $subjectes
        ]);
    }
}
