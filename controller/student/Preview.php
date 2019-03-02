<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Preview extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);

        $state = $this->db->get('main', ['state [Int]'], [
            'user' => $this->container->auth->getUser()->getID(),
            'id' => $listID,
            'version' => $this->settings['active_version']
        ]);

        if ($state === false) {
            return $this->redirectWithMessage($response, 'subjects-student', "error", [
                $this->container->lang->g('error-notfound', 'student-preview')
            ]);
        }

        if ($request->isPut()) {
            if ($state['state'] == 0 || $state['state'] == 3) {    
                $this->db->update('main', ['state' => 1], [
                    'id' => $listID,
                    'version' => $this->settings['active_version']
                ]);

                if ($state['state'] == 3) {
                    $this->db->update('lists', ['level' => 0], ['level' => 3, 'list' => $listID]);
                    foreach ($this->db->select('subjects', ['id'], [
                        'state' => 1,
                        'version' => $this->settings['active_version']
                    ]) as $sub_del) {
                        $this->db->delete('lists', ['list' => $listID, 'subject' => $sub_del['id']]);
                    }
                }

                return $this->redirectWithMessage($response, 'subjects-student-preview', "status", [
                    $this->container->lang->g('success-sent', 'student-preview')
                ], ['id' => $listID]);

            }
        }

        $subjects = $this->db->select('lists', [
            '[>]subjects' => ['subject' => 'id']
        ], [
            'subjects.code',
            'subjects.name',
            'lists.level'
        ], [
            'ORDER' => 'subjects.id',
            'lists.list' => $listID,
            'subjects.state' => 0
        ]);

        $versionName = $this->db->get('versions', 'name', ['id' => $this->settings['active_version']]);

        $this->sendResponse($request, $response, "student/preview.phtml", [
            "subjects" => $subjects,
            "listID" => $listID,
            "state" => $state['state']
        ]);
    }
}
