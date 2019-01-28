<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{

    protected $listID;

    public function __invoke(Request $request, Response $response, $args)
    {
        
        if (\array_key_exists('id', $args)) {
            $this->listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);

            if (!$this->db->has('main', ['id' => $this->listID])) {
                return $this->redirectWithMessage($response, 'subjects-student', "error", ['Seznam nenalezen']); 
            }

            if ($this->db->get('main', ['state'], ['id' => $this->listID])['state'] != 0) {
                return $response->withRedirect($this->container->router->pathFor('subjects-student-preview', ['id' => $this->listID]), 301);
            }
        }

        $subjects = $this->db->select('subjects', [
            'id [Index]',
            'name [String]',
            'annotation [String]'
        ], [
            'version' => $this->settings['active_version']
        ]);

        if ($request->isPut()) {

            $listID = $this->listID ?? $this->getListID();

            $data = $request->getParsedBody();

            if (!\array_key_exists('subject', $data) || !\array_key_exists('action', $data)) {
                return $this->invalidRequest($response);
            }

            $subject = \filter_var($data['subject'], \FILTER_SANITIZE_STRING);
            $action  = \filter_var($data['action'], \FILTER_SANITIZE_STRING);

            if (!\array_key_exists($subject, $subjects)) {
                return $this->invalidRequest($response);
            }
            
            switch ($action) {
                case 'enrol':
                    $this->cleanPrevious($subject);
                    $this->db->insert('lists', [
                        'list' => $listID,
                        'subject' => $subject,
                        'level' => 0
                    ]);
                    break;
                case 'spare':
                    $this->cleanPrevious($subject);
                    $this->db->insert('lists', [
                        'list' => $listID,
                        'subject' => $subject,
                        'level' => 1
                    ]);
                    break;
                case 'option':
                    $this->cleanPrevious($subject);
                    $this->db->insert('lists', [
                        'list' => $listID,
                        'subject' => $subject,
                        'level' => 2
                    ]);
                    break;
                case 'cancel':
                    $this->cleanPrevious($subject);
                    if ($this->db->count('lists', [
                        'list' => $listID
                    ]) == 0) {
                        $this->db->delete('main', ['id' => $listID]);
                        return $response->withRedirect($this->container->router->pathFor('subjects-student-edit'), 301);
                    }
                    break;

                default:
                    return $this->invalidRequest($response);
            }

            if (!\array_key_exists('id', $args) && $this->db->count('lists', ['list' => $listID]) > 0) {
                $this->db->insert('main', [
                    'id' => $listID,
                    'user' => $this->container->auth->getUser()->getID(),
                    'created' => time(),
                    'state' => 0,
                    'version' => $this->settings['active_version']
                ]);
                return $response->withRedirect($this->container->router->pathFor('subjects-student-edit', ['id' => $listID]), 301);
            }
        }

        $selected = $this->db->select('lists', [
            '[>]main' => ['list' => 'id']
        ], [
            'lists.subject [Index]',
            'lists.level [Int]'
        ], [
            'main.user' => $this->container->auth->getUser()->getID(),
            'main.id' => $this->listID,
            'main.version' => $this->settings['active_version']
        ]);

        $selectedCount = 0;
        $spareCount = 0;

        foreach ($subjects as $id => $subject) {
            $subjects[$id]['level'] = $selected[$id]['level'] ?? false;

            if (($selected[$id]['level'] ?? false) === 0) {
                $selectedCount++;
            } else if (($selected[$id]['level'] ?? false) === 1) {
                $spareCount++;
            }
        }

        $limit = $this->db->get('versions', ['limit [Int]'], ['id' => $this->settings['active_version']]);

        $this->sendResponse($request, $response, "student/edit.phtml", [
            "counts" => [$selectedCount, $limit['limit'], $spareCount],
            "subjects" => $subjects,
            "listID" => $this->listID
        ]);
    }

    private function invalidRequest($response)
    {
        return $this->redirectWithMessage($response, 'subjects-student-edit', "error", ['Chybná akce'], [
            'id' => $this->listID
        ]);
    }

    private function cleanPrevious($subject)
    {
        $this->db->delete('lists', [
            'list' => $this->listID,
            'subject' => $subject
        ]);
    }

    private function getListID()
    {
        if (!is_null($this->listID)) {
            return $this->listID;
        }
        do {
            $id = rand(100000, 999999);
        } while ($this->container->db->has('main', ['id' => $id]));
        return $id;
    }
}
