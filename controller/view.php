<?php

namespace modules\lists\controller;

class view extends lists {

    public function student($request, &$response, $args) {
        if ($this->settings['open_editing']) {
            $listgroups = $this->db->select("main", "*", [
                "user" => $this->userID,
                "version" => $this->settings['active_version']
            ]);
            
            $response = $this->sendResponse($request, $response, "view.phtml", ["lists" => $listgroups]);
        } else {
            $response->getBody()->write("Nemáte přístup k tvorbě kánonu");
        }
    }

    public function teacher($request, &$response, $args) {
        $books = [];
        foreach ($this->db->select('books', '*', ['version' => $this->settings['active_version']]) as $book)
            $books[$book['id']] = $book;
        
        $count = array_count_values($this->db->select('lists', ['[>]main' => ['list' => 'id']], 'book', ['main.version' => $this->settings['active_version'], 'main.state' => 2]));
        arsort($count);

        $response = $this->sendResponse($request, $response, "teacher/dash.phtml", [
            "books" => $books,
            "count" => $count,
            "allowAccepting" => $this->settings['open_accepting'] == 1
        ]);
    }

    public function admin($request, &$response, $args) {
        $versions = $this->container->db->select("versions", "*");

        $response = $this->sendResponse($request, $response, "admin/dash.phtml", [
            "versions" => $versions,
            "settings" => $this->settings
        ]);
    }
}
