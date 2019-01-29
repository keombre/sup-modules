<?php

namespace modules\lists\controller;

class view extends lists {

    public function student($request, &$response, $args) {
        if ($this->settings['open_editing'] && $this->container->auth->getUser()->getAttribute('year') == 8) {
            $listgroups = $this->db->select("main", "*", [
                "user" => $this->userID,
                "version" => $this->settings['active_version']
            ]);

            foreach ($listgroups as $id => $list) {
                if ($listgroups[$id]['state'] == 2) {
                    $listgroups[$id]['accepted_by'] = $this->container->factory->userFromID($listgroups[$id]['accepted_by']);
                }
            }
            
            $response = $this->sendResponse($request, $response, "view.phtml", ["lists" => $listgroups]);
        } else {
            $response->getBody()->write("Nemáte přístup k tvorbě kánonu");
        }
    }

    public function teacher($request, &$response, $args) {
        $books = $this->db->select('books', [
            'id [Index]',
            'name [String]',
            'author [String]',
            'region [Int]',
            'genere [Int]'
        ], ['version' => $this->settings['active_version']]);
        
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
        $allowDownload = is_string($this->settings['active_version']);
        $response = $this->sendResponse($request, $response, "admin/dash.phtml", [
            "versions" => $versions,
            "settings" => $this->settings,
            "allowDownload" => $allowDownload
        ]);
    }
}
