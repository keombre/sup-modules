<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\admin\upload;
use Slim\Http\Request;
use Slim\Http\Response;

class manage extends upload {

    function __invoke(Request $request, Response $response, $args) {
        
        $data = $request->getParsedBody();
        $version = filter_var($args['id'], FILTER_SANITIZE_STRING);
        
        if (!$this->db->has('versions', ["id" => $version]))
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-version-notfound', 'admin-upload')
            ]);
        
        if ($request->isPut()) {

            $mode = $request->getQueryParam("mode");

            if ($mode == 'upload') {
                $parsed = parent::__invoke($request, $response, $args);
            
                if (!is_array($parsed))
                    return $parsed;

                $save = [];
                foreach ($parsed as $entry) {
                    array_push($save, [
                        "code"      => intval($entry[0]),
                        "name"      => trim($entry[1]),
                        "annotation" => trim($entry[2]),
                        "version"   => $version
                    ]);
                }
                $this->db->delete("subjects", ["version" => $version]);
                $this->db->insert("subjects", $save);
                return $this->redirectWithMessage($response, 'subjects-admin-manage', "status", [
                    $this->container->lang->g('success', 'admin-upload', ['count' => count($save)])
                ], ["id" => $version]);
            
            } else if ($mode == 'limit') {
                $limit = filter_var(@$data['limit'], \FILTER_VALIDATE_INT);

                if (!$limit) {
                    return $this->redirectWithMessage($response, 'subjects-admin-manage', "error", [
                        $this->container->lang->g('error-field-missing', 'admin-manage', ['id' => $id])
                    ], ["id" => $version]);
                }

                $this->db->update('versions', ['limit' => $limit], ['id' => $version]);

                return $this->redirectWithMessage($response, 'subjects-admin-manage', "status", [
                    $this->container->lang->g('success-saved', 'admin-manage')
                ], ["id" => $version]);
            }
        }
        
        $version_info = $this->db->get('versions', [
            'name [String]',
            'limit [Int]'
        ], ["id" => $version]);

        $subjects = $this->db->select('subjects', [
            'id [Index]',
            'code [Int]',
            'name [String]',
            'annotation [String]'
        ], ["version" => $version]);

        $response = $this->sendResponse($request, $response, "admin/manage.phtml", [
            "subjects" => $subjects,
            "version" => $version,
            "name" => $version_info['name'],
            "limit" => $version_info['limit']
        ]);

        return $response;
    }
}
