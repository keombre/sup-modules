<?php

namespace modules\lists\controller\admin;

final class manage extends upload {

    function __invoke($request, $response, $args) {

        $data = $request->getParsedBody();
        $version = filter_var($args['id'], FILTER_SANITIZE_STRING);
        
        if (!$this->db->has('versions', ["id" => $version]))
            return $this->redirectWithMessage($response, 'lists', "error", [
                $this->container->lang->g('error-version-notfound', 'admin-upload')
            ]);
        
        $status = null;
        if ($request->isPut()) {
            
            $mode = $request->getQueryParam("mode");

            if ($mode == 'upl') {
                $parsed = parent::__invoke($request, $response, $args);
            
                if (!is_array($parsed))
                    return $parsed;

                $save = [];
                foreach ($parsed as $entry) {
                    array_push($save, [
                        "name"    => trim($entry[2]),
                        "author"  => trim($entry[1]),
                        "region"  => intval($entry[0]),
                        "genere"  => intval($entry[3]),
                        "version" => $version
                    ]);
                }
                $this->db->delete("books", ["version" => $version]);
                $this->db->insert("books", $save);
                return $this->redirectWithMessage($response, 'lists-admin-manage', "status", [
                    $this->container->lang->g('success', 'admin-upload', ['count' => count($save)])
                ], ["id" => $version]);
            } else if ($mode == 'reg' || $mode == 'gen') {
                
                if (!is_array(@$data[$mode]))
                    return $this->redirectWithMessage($response, 'lists-admin-manage', "error", [
                        $this->container->lang->g('error-generic', 'admin-upload')
                    ], ["id" => $version]);
                
                $books = $this->db->select("books", "*", ["version" => $version]);
                $count = array_unique(array_column($books, $mode == 'reg'?'region':'genere'));

                $fields = [];
                foreach ($data[$mode] as $id => $entry)
                    if (in_array($id, $count))
                        $fields[$id] = filter_var_array($entry, ["name" => FILTER_SANITIZE_STRING, "min" => FILTER_VALIDATE_INT, "max" => FILTER_VALIDATE_INT]);

                if (!count($fields))
                    return $this->redirectWithMessage($response, 'lists-admin-manage', "error", [
                        $this->container->lang->g('error-fields-missing', 'admin-manage')
                    ], ["id" => $version]);
                
                $save = [];
                foreach ($fields as $id => $field) {
                    if (strlen($field['name']) == 0)
                        return $this->redirectWithMessage($response, 'lists-admin-manage', "error", [
                            $this->container->lang->g('error-field-missing', 'admin-manage', ['id' => $id])
                        ], ["id" => $version]);
                    
                    array_push($save, [
                        "id" => $id,
                        "name" => $field['name'],
                        "min" => is_numeric($field['min'])?$field['min']:null,
                        "max" => is_numeric($field['max'])?$field['max']:null,
                        "version" => $version
                    ]);
                }

                foreach ($save as $entry)
                    if ($this->db->has($mode == 'reg'?'regions':'generes', ['id' => $entry['id'], 'version' => $version]))
                        $this->db->update($mode == 'reg'?'regions':'generes', $entry, ['id' => $entry['id'], 'version' => $version]);
                    else
                        $this->db->insert($mode == 'reg'?'regions':'generes', $entry);

                return $this->redirectWithMessage($response, 'lists-admin-manage', "status", [
                    $this->container->lang->g('success-saved', 'admin-manage')
                ], ["id" => $version]);
            }
        }

        $regions = $this->db->select('regions', [
            'id [Index]',
            'name [String]',
            'min [Int]',
            'max [Int]'
        ], ['version' => $version]);

        $generes = $this->db->select('generes', [
            'id [Index]',
            'name [String]',
            'min [Int]',
            'max [Int]'
        ], ['version' => $version]);
        
        $name = $this->db->get('versions', 'name', ["id" => $version]);
        $books = $this->db->select("books", "*", ["version" => $version]);

        $regionsCount = array_unique(array_column($books, 'region'));
        $generesCount = array_unique(array_column($books, 'genere'));

        $response = $this->sendResponse($request, $response, "admin/manage.phtml", [
            "books" => $books,
            "version" => $version,
            "name" => $name,
            "status" => $status,
            "regions" => $regions,
            "generes" => $generes,
            "regionsCount" => $regionsCount,
            "generesCount" => $generesCount
        ]);

        return $response;
    }
}