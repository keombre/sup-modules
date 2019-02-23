<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\entries;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);
        
        $listInfo = $this->db->get('main', ['user [Int]', 'version [Int]'], [
            'id' => $listID,
            'version' => [
                $this->settings['active_version_7'],
                $this->settings['active_version_8']
            ],
            'state[!]' => 0
        ]);
        
        if ($listInfo == false || count($listInfo) == 0) {
            return $this->notFound($response);
        }

        $user = $this->container->factory->userFromID($listInfo['user']);

        if (is_null($user)) {
            return $this->notFound($response);
        }

        $subjects = $this->db->select('lists', [
            '[>]subjects' => ['subject' => 'id']
        ], [
            'subjects.id',
            'subjects.code',
            'subjects.name',
            'subjects.state',
            'lists.level'
        ], [
            'ORDER' => 'subjects.id',
            'lists.list' => $listID
        ]);

        $response = $this->sendResponse($request, $response, "admin/entries/edit.phtml", [
            "subjects" => $subjects,
            "user" => $user,
            "sidebar_active" => "entries"
        ]);
    }

    private function notFound(&$response)
    {
        return $this->redirectWithMessage($response, 'subjects-admin-entries-dash', "error", ["Seznam nenalezen"]);
    }
}
