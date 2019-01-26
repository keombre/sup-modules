<?php

namespace modules\lists\controller\teacher;

class accept extends \sup\controller {

    protected $settings;

    function __construct($container) {
        parent::__construct($container);
        $this->settings = $this->db->get("settings", "*");
    }

    function __invoke($request, $response, $args) {
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

        if (!is_null($code)) {
            if ($code === false)
                return $this->redirectWithMessage($response, 'lists-teacher-accept', "error", ["Chybný kód"]);
            else if ($this->validateList($code) == false)
                return $this->redirectWithMessage($response, 'lists-teacher-accept', "error", ["Kánon nenalezen"]);
            else
                return $this->redirectWithMessage($response, 'lists-teacher-accept', "status", ["Kánon schválen"]);
        }

        return $this->sendResponse($request, $response, "teacher/accept.phtml");
    }
    

    private function validatePost($code) {
        if (!is_string($code))
            return false;
        $code = trim($code);
        
        if (strlen($code) == 6) {
            if (!is_numeric($code))
                return false;
            return ["code" => intval($code)];
        } else if (strlen($code) == 7) {
            if (substr($code, 3, 1) != '-')
                return false;
            if (!is_numeric(substr($code, 0, 3)) || !is_numeric(substr($code, 4)))
                return false;
            return ["code" => intval(substr($code, 0, 3) . substr($code, 4))];
        } else if (strlen($code) >= 13) {
            if (substr($code, 0, 1) != 'C' || substr($code, 7, 2) != '-U')
                return false;
            
            $parts = array_map(function($e){return substr($e, 1);}, explode('-', $code));
            if (!is_numeric($parts[0]))
                return false;
            
            return ["code" => intval($parts[0]), "user" => $parts[1]];
        }
        return false;
    }

    private function validateURL($id, $uri) {
        if (($code = $this->validatePost($id)) === false)
            return false;
        
        if (!is_string($uri))
            return $code;
        
        $books = explode('-', base64_decode($uri));
        if (count($books) != 20)
            return false;
        
        $books = filter_var_array($books, FILTER_VALIDATE_INT);
        foreach ($books as $book)
            if ($book === false)
                return false;
        
        return array_merge($code, ["books" => $books]);
    }

    private function validateList($code) {
        if (!$this->db->has("main", ['id' => $code['code'], 'version' => $this->settings['active_version']]))
            return false;
        
        $list = $this->db->get('main', '*', ['id' => $code['code'], 'version' => $this->settings['active_version']]);
        
        if (array_key_exists('user', $code)) {
            $user = $this->container->factory->userFromID($list['user']);
            
            if (!$user || $user->getUname() != $code['user']) {
                return false;
            }
        }

        if (array_key_exists('books', $code)) {
            $books = $this->db->select('lists', 'book', ['list' => $code['code']]);
            if (count(array_diff($books, $code['books'])) != 0)
                return false;
        }

        $this->acceptList($code['code']);
        return true;
    }

    private function acceptList($listID) {
        $userID = $this->db->get('main', 'user', ['id' => $listID]);
        $this->db->update('main', [
            'state' => 1
        ], [
            'version' => $this->settings['active_version'],
            'state'   => 2,
            'user'    => $userID
        ]);
        
        $this->db->update('main', ['state' => 2, 'accepted_by' => $this->container->auth->getUser()->getID()], ['id' => $listID]);
    }
}
