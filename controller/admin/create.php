<?php

namespace modules\lists\controller\admin;

final class create extends upload {

    function __invoke($request, $response, $args) {
        $data = $request->getParsedBody();

        $name = substr(filter_var(@$data['name'], FILTER_SANITIZE_STRING), 0, 30);
        
        if (!is_string($name) || strlen($name) == 0)
            return $this->redirectWithMessage($response, 'lists', "error", ["Zadejte název verze"]);
        if ($name !== $data['name'])
            return $this->redirectWithMessage($response, 'lists', "error", ["Nepoužívejte speciální znaky"]);
        else if ($this->db->has("versions", ["name" => $name]))
            return $this->redirectWithMessage($response, 'lists', "error", ["Verze " . $name . " již existuje"]);
        
        $parsed = parent::__invoke($request, $response, $args);
        
        if (!is_array($parsed))
            return $parsed;

        $this->db->insert("versions", ["name" => $name]);
        $version = $this->db->id();

        $save = [];
        foreach ($parsed as $entry) {
            array_push($save, [
                "name"    => $entry[2],
                "author"  => $entry[1],
                "region"  => intval($entry[0]),
                "genere"  => intval($entry[3]),
                "version" => $version
            ]);
        }
        $this->db->insert("books", $save);
        $this->redirectWithMessage($response, 'lists-admin-manage', "status", [count($save) . " knih nahráno"], ['id' => $version]);
        return $response;
    }

}