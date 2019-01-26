<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\admin\Upload;
use Slim\Http\Request;
use Slim\Http\Response;

class Create extends Upload
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        $name = substr(filter_var(@$data['name'], \FILTER_SANITIZE_STRING), 0, 30);
        $limit = filter_var(@$data['limit'], \FILTER_VALIDATE_INT);
        
        if (!is_string($name) || strlen($name) == 0) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-version-missing', 'admin-upload')
            ]);
        }
        if ($name !== $data['name'] || $limit === false) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-version-charset', 'admin-upload')
            ]);
        } elseif ($this->db->has("versions", ["name" => $name])) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('error-version-exists', 'admin-upload', ['version' => $name])
            ]);
        }
        
        $parsed = parent::__invoke($request, $response, $args);
        
        if (!is_array($parsed)) {
            return $parsed;
        }

        $this->db->insert("versions", ["name" => $name, "limit" => $limit]);
        $version = $this->db->id();

        $save = [];
        foreach ($parsed as $entry) {
            array_push($save, [
                "code"      => intval($entry[0]),
                "name"      => trim($entry[1]),
                "annotation" => trim($entry[2]),
                "version"   => $version
            ]);
        }

        $this->db->insert("subjects", $save);
        $this->redirectWithMessage($response, 'subjects-admin-manage', "status", [
            $this->container->lang->g('success', 'admin-upload', ['count' => count($save)])
        ], ['id' => $version]);
        return $response;
    }
}
