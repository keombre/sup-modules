<?php declare(strict_types=1);

namespace modules\subjects\controller\teacher;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Accept extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $code = null;

        if ($request->isPut()) {
            $data = $request->getParsedBody();
            $post = filter_var(@$data['code'], FILTER_SANITIZE_STRING);

            $code = $this->validatePost($post);
        }

        if ($request->isGet() && array_key_exists('id', $args)) {
            $id   = filter_var(@$args['id'], FILTER_SANITIZE_STRING);
            $uri  = $request->getQueryParam('b');
            $code = $this->validateURL($id, $uri);
        }

        $l = $this->container->lang;

        if (!is_null($code)) {
            if ($code === false) {
                return $this->redirectWithMessage($response, 'subjects-teacher-accept', "error", [$l->g('error-missing-code', 'teacher-accept')]);
            } elseif ($this->validateList($code) == false) {
                return $this->redirectWithMessage($response, 'subjects-teacher-accept', "error", [$l->g('error-notfound', 'teacher-accept')]);
            } else {
                return $this->redirectWithMessage($response, 'subjects-teacher-accept', "status", [$l->g('success', 'teacher-accept')]);
            }
        }

        return $this->sendResponse($request, $response, "teacher/accept.phtml");
    }

    private function validatePost($code)
    {
        if (!is_string($code)) {
            return false;
        }
        $code = trim($code);

        if (strlen($code) == 6) {
            if (!is_numeric($code)) {
                return false;
            }
            return ["code" => intval($code)];
        } elseif (strlen($code) == 7) {
            if (substr($code, 3, 1) != '-') {
                return false;
            }
            if (!is_numeric(substr($code, 0, 3)) || !is_numeric(substr($code, 4))) {
                return false;
            }
            return ["code" => intval(substr($code, 0, 3) . substr($code, 4))];
        } elseif (strlen($code) >= 13) {
            if (substr($code, 0, 1) != 'C' || substr($code, 7, 2) != '-U') {
                return false;
            }

            $parts = array_map(function ($e) {
                return substr($e, 1);
            }, explode('-', $code));
            if (!is_numeric($parts[0])) {
                return false;
            }

            return ["code" => intval($parts[0]), "user" => $parts[1]];
        }
        return false;
    }

    private function validateURL($id, $uri)
    {
        if (($code = $this->validatePost($id)) === false) {
            return false;
        }

        if (!is_string($uri)) {
            return $code;
        }

        $subjects = explode('-', base64_decode($uri));

        $subjects = filter_var_array($subjects, FILTER_VALIDATE_INT);
        foreach ($subjects as $subject) {
            if ($subject === false) {
                return false;
            }
        }

        return array_merge($code, ["subjects" => $subjects]);
    }

    private function validateList($code)
    {
        if (!$this->db->has("main", ['id' => $code['code'], 'version' => [
            $this->settings['active_version_7'],
            $this->settings['active_version_8']
        ]])) {
            return false;
        }

        $list = $this->db->get('main', '*', ['id' => $code['code'], 'version' => [
            $this->settings['active_version_7'],
            $this->settings['active_version_8']
        ]]);

        if (array_key_exists('user', $code)) {
            $user = $this->container->factory->userFromID((int) $list['user']);

            if (!$user || $user->getUname() != $code['user']) {
                return false;
            }
        }

        if (array_key_exists('subjects', $code)) {
            $subjects = $this->db->select('lists', 'subject', ['list' => $code['code']]);
            if (count(array_diff($subjects, $code['subjects'])) != 0) {
                return false;
            }
        }

        $this->acceptList($code['code']);
        return true;
    }

    private function acceptList($listID)
    {
        $userID = $this->db->get('main', 'user', ['id' => $listID]);
        $this->db->update('main', [
            'state' => 1
        ], [
            'version' => [
                $this->settings['active_version_7'],
                $this->settings['active_version_8']
            ],
            'state'   => 2,
            'user'    => $userID
        ]);

        $this->db->update('main', ['state' => 2, 'accepted_by' => $this->container->auth->getUser()->getID()], ['id' => $listID]);
    }
}
