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

        $listInfo = $this->db->get('main', ['user [Int]', 'version [Int]', 'state [Int]'], [
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

        if ($request->isPut()) {
                $subject = $this->sanitizePost($request, 'subject', \FILTER_SANITIZE_STRING);
                $level = $this->sanitizePost($request, 'level', \FILTER_VALIDATE_INT);
                if ($level === false || ($level !== 0 && $level !== 1 && $level !== 2))
                        return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "error", ["Chybný režim"], ['id' => $listID]);

                if ($subject === false || !$this->db->has('subjects', ['code' => $subject, 'state' => 0, 'version' => strval($listInfo['version'])]))
                        return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "error", ["Předmět nenalezen"], ['id' => $listID]);

                $sub_info = $this->db->get('subjects', '*', ['code' => $subject, 'state' => 0, 'version' => strval($listInfo['version'])]);

                if ($this->db->has('lists', ['list' => $listID, 'subject' => intval($sub_info['id'])])) {
                        $rec = $this->db->get('lists', '*', ['list' => $listID, 'subject' => intval($sub_info['id'])]);
                        if (intval($rec['level']) === $level)
                                return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "error", ["Předmět je již zapsán"], ['id' => $listID]);
                        else {
                                $this->db->update('lists', ['level' => intval($level)], ['list' => $listID, 'subject' => intval($sub_info['id'])]);
                                return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "status", ["Typ zápisu aktualizován"], ['id' => $listID]);
                        }
                } else {
                        $this->db->insert('lists', ['subject' => $sub_info['id'], 'level' => $level, 'list' => $listID]);
                        return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "status", ["Předmět přidán"], ['id' => $listID]);
                }
        } else if ($request->isDelete()) {
                $sub = $this->sanitizePost($request, 'id', \FILTER_VALIDATE_INT);
                if ($sub === false || !$this->db->has('lists', ['subject' => $sub], ['list' => $listID]))
                        return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "error", ["Předmět nenalezen"], ['id' => $listID]);

                $this->db->delete('lists', ['list' => $listID, 'subject' => $sub]);
                return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "status", ["Předmět odstraněn"], ['id' => $listID]);
        } else if ($request->isPost()) {
                $state = $this->sanitizePost($request, 'state', \FILTER_VALIDATE_INT);

                if ($state === null || $state === false || !in_array($state, [0, 1, 2, 3]))
                        return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "error", ["Neplatný stav"], ['id' => $listID]);

                $this->db->update("main", ["state" => $state], ["id" => $listID]);
                return $this->redirectWithMessage($response, 'subjects-admin-entries-edit', "status", ["Stav aktualizován"], ['id' => $listID]);
        }

        $response = $this->sendResponse($request, $response, "admin/entries/edit.phtml", [
            "subjects" => $subjects,
            "user" => $user,
            "sidebar_active" => "entries",
            "list_id" => $listID,
            "state" => $listInfo['state']
        ]);
    }

    private function notFound(&$response)
    {
        return $this->redirectWithMessage($response, 'subjects-admin-entries-dash', "error", ["Seznam nenalezen"]);
    }
}
