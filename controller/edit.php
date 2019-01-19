<?php

namespace modules\lists\controller;

class edit extends lists {

    function teacher($request, &$response, $args) {}
    function admin($request, &$response, $args) {}
    function student($request, &$response, $args) {

        $data = $request->getParsedBody();

        $state = $this->container->db->get('main', 'state', ['id' => $this->listID]);
        if ($state != 0)
            return $response->withRedirect($this->container->router->pathFor('lists-validate', ['id' => $this->listID]), 301);

        if ($request->isPut()) {

            $books = array_unique(filter_var_array(@$data['books'], FILTER_VALIDATE_INT));
            if (!count($books))
                return $this->redirectWithMessage($response, 'lists-edit', "error", ["Žádné knihy nezvoleny"], ['id' => $this->listID]);

            $id = $this->getListID();

            foreach ($this->container->db->select("lists", "book", ["list" => $id]) as $remove)
                if (in_array($remove, $books))
                    unset($books[array_search($remove, $books)]);
            
            $save = array_reduce($books, function ($e, $f) use ($id) {
                if (is_numeric($f)) array_push($e, ["list" => $id, "book" => $f]);
                return $e;
            }, []);

            if (!count($save))
                return $this->redirectWithMessage($response, 'lists-edit', "error", ["Chyba při ukládání knih"], ["id" => $this->listID]);
            
            $this->container->db->insert("lists", $save);

            if (is_null($this->listID)) {
                $this->container->db->insert("main", [
                    "id" => $id,
                    "user" => $this->userID,
                    "created" => time(),
                    "version" => $this->settings['active_version']
                ]);
                return $response->withRedirect($this->container->router->pathFor('lists-edit', ['id' => $id]), 301);
            }
        } else if ($request->isDelete()) {
            $books = array_unique(filter_var_array(@$data['books'], FILTER_SANITIZE_STRING));

            if ($this->removeEmptyList())
                return $response->withRedirect($this->container->router->pathFor('lists'), 301);

            if (is_null($this->listID)) {
                if (!count($books))
                    return $response->withRedirect($this->container->router->pathFor('lists'), 301);
                else
                    return $this->redirectWithMessage($response, 'lists', "error", ["Kánon nenalezen"]);
            }

            if (!count($books))
                return $this->redirectWithMessage($response, 'lists-edit', "error", ["Žádné knihy nezvoleny"], ['id' => $this->listID]);

            $this->container->db->delete("lists", ["list" => $this->listID, "OR" => ["book" => $books]]);
            
            if ($this->removeEmptyList())
                return $this->redirectWithMessage($response, 'lists', "status", ["Kánon smazán"]);
        }
        
        $listbooks = [];
        if (!is_null($this->listID))
            $listbooks = $this->container->db->select("lists", "book", ["list" => $this->listID, "ORDER" => "book"]);
        
        /*
        $allbooks = [];
        foreach ($this->container->db->select("books", "*", ["version" => $this->settings['active_version']]) as $book)
            $allbooks[$book['id']] = $book;
        */

        $allbooks = $this->db->select('books', [
            'id [Index]',
            'name [String]',
            'author [String]',
            'region [Int]',
            'genere [Int]',
            'version [Int]'
        ], ['version' => $this->settings['active_version']]);
        
        $books = [];
        $list = [];

        foreach ($listbooks as $book) {
            if (array_key_exists($book, $allbooks)) {
                if (!array_key_exists($allbooks[$book]['region'], $list))
                    $list[$allbooks[$book]['region']] = [];
                $list[$allbooks[$book]['region']][$book] = $allbooks[$book];
                unset($allbooks[$book]);
            }
        }
        
        foreach ($allbooks as $book) {
            if (!array_key_exists($book['region'], $books))
                $books[$book['region']] = [];
            $books[$book['region']][$book['id']] = $book;
        }

        $regions = array_column($this->container->db->select("regions", "*", ['version' => $this->settings['active_version']]), 'name', 'id');
        $generes = array_column($this->container->db->select("generes", "*", ['version' => $this->settings['active_version']]), 'name', 'id');
        $listLength = count($listbooks);
        
        $this->sendResponse($request, $response, "edit.phtml", [
            "list" => $list,
            "books" => $books,
            "regions" => $regions,
            "generes" => $generes,
            "listLength" => $listLength,
            "listID" => $this->listID
        ]);
        
        return $response;
    }

    private function removeEmptyList() {
        if ($this->container->db->has('main', ['AND' => ['user' => $this->userID, 'id' => $this->listID]])) {
            if (!$this->container->db->has('lists', ['list' => $this->listID])) {
                $this->container->db->delete('main', ['id' => $this->listID]);
                return true;
            }
        }
        return false;
    }

    private function getListID() {
        if (!is_null($this->listID)) return $this->listID;
        do $id = rand(100000, 999999);
        while ($this->container->db->has("main", ["id" => $id]));
        return $id;
    }
}
