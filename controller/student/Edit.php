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

        $this->listID = $this->db->get('main', ['id'], [
            'user' => $this->container->auth->getUser()->getID(),
            'version' => $this->settings['active_version']
        ])['id'];

        if (is_null($this->listID)) {
            $this->listID = $this->getListID();
            $this->db->insert('main', [
                'id' => $this->listID,
                'user' => $this->container->auth->getUser()->getID(),
                'created' => time(),
                'state' => 0,
                'version' => $this->settings['active_version']
            ]);
        }

        $state = $this->db->get('main', ['state'], ['id' => $this->listID, 'version' => $this->settings['active_version']])['state'];
        if ($state == 1 || $state == 2) {
            return $response->withRedirect($this->container->router->pathFor('subjects-student-preview', ['id' => $this->listID]), 301);
        }

        if (!$this->checkTime($state)) {
            return $this->redirectWithMessage($response, 'subjects-student', "error", [
                'Nemáte přístup k úpravám'
            ]);
        }

        $subjects = $this->db->select('subjects', [
            'id [Index]',
            'code [String]',
            'name [String]',
            'annotation [String]'
        ], [
            'version' => $this->settings['active_version'],
            'state' => 0
        ]);

        if ($request->isPut()) {

            $data = $request->getParsedBody();

            if (!\array_key_exists('subject', $data) || !\array_key_exists('action', $data)) {
                return $this->invalidRequest($response);
            }

            $subject = \filter_var($data['subject'], \FILTER_SANITIZE_STRING);
            $action  = \filter_var($data['action'], \FILTER_SANITIZE_STRING);

            if (
                !\array_key_exists($subject, $subjects) || 
                ($state == 3 && $this->db->has('lists', ['list' => $this->listID, 'level' => 0, 'subject' => $subject]))
            ) {
                return $this->invalidRequest($response);
            }
            
            switch ($action) {
                case 'enrol':
                    $this->cleanPrevious($subject);
                    $this->db->insert('lists', [
                        'list' => $this->listID,
                        'subject' => $subject,
                        'level' => $state == 3 ? 3 : 0
                    ]);
                    break;
                case 'spare1':
                    $this->cleanPrevious($subject);
                    $this->cleanSpare(1);
                    $this->db->insert('lists', [
                        'list' => $this->listID,
                        'subject' => $subject,
                        'level' => 1
                    ]);
                    break;
                case 'spare2':
                    $this->cleanPrevious($subject);
                    $this->cleanSpare(2);
                    $this->db->insert('lists', [
                        'list' => $this->listID,
                        'subject' => $subject,
                        'level' => 2
                    ]);
                    break;
                case 'cancel':
                    $this->cleanPrevious($subject);
                    if ($this->db->count('lists', [
                        'list' => $this->listID
                    ]) == 0) {
                        $this->db->delete('main', ['id' => $this->listID]);
                        return $response->withRedirect($this->container->router->pathFor('subjects-student-edit'), 301);
                    }
                    break;

                default:
                    return $this->invalidRequest($response);
            }

            if (!\array_key_exists('id', $args) && $this->db->count('lists', ['list' => $this->listID]) > 0) {
                $this->db->insert('main', [
                    'id' => $this->listID,
                    'user' => $this->container->auth->getUser()->getID(),
                    'created' => time(),
                    'state' => 0,
                    'version' => $this->settings['active_version']
                ]);
                return $response->withRedirect($this->container->router->pathFor('subjects-student-edit'), 301);
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

            if (($selected[$id]['level'] ?? false) === 0 || ($selected[$id]['level'] ?? false) == 3) {
                $selectedCount++;
            } else if (($selected[$id]['level'] ?? false) == 1 || ($selected[$id]['level'] ?? false) == 2) {
                $spareCount++;
            }
        }

        $limit = $this->db->get('versions', ['limit [Int]', 'limit_spare [Int]'], ['id' => $this->settings['active_version']]);

        $this->sendResponse($request, $response, "student/edit.phtml", [
            "state" => $state,
            "counts" => [$selectedCount, $limit['limit'], $spareCount, $limit['limit_spare']],
            "subjects" => $subjects,
            "listID" => $this->listID
        ]);
    }

    private function checkTime($state) {
        $limit = $this->db->get('versions', ['timer1 [JSON]', 'timer2 [JSON]'], ['id' => $this->settings['active_version']]);

        if (is_null($limit['timer1']) || is_null($limit['timer2'])) {
            return false;
        }

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
            return false;
        } else if ($times[1]['close'] > time() && $state == 0) {
            return true;
        } else if ($times[2]['open'] > time()) {
            return false;
        } else if ($times[2]['close'] > time() && $state == 3) {
            return true;
        } else {
            return false;
        }
    }

    private function invalidRequest($response)
    {
        return $this->redirectWithMessage($response, 'subjects-student-edit', "error", [
            $this->container->lang->g('error', 'student-edit')
        ]);
    }

    private function cleanPrevious($subject)
    {
        $this->db->delete('lists', [
            'list' => $this->listID,
            'subject' => $subject
        ]);
    }

    private function cleanSpare(int $type)
    {
        $this->db->delete('lists', [
            'list' => $this->listID,
            'level' => $type
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
