<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\subjects;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $version = \filter_var($args['id'], \FILTER_SANITIZE_STRING);

        if ($request->isGet()) {
            if (!$this->db->has('versions', ['id' => $version])) {
                return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Seznam nenalezen"]);
            }

            $subjects = $this->db->select('subjects', [
                'id [Index]',
                'code [String]',
                'name [String]',
                'annotation [String]',
                'state [Int]'
            ], [
                'version' => $version
            ]);

            $info = $this->db->get('versions', [
                'name [String]',
                'limit [Int]',
                'limit_spare [Int]'
            ], ['id' => $version]);

            $response = $this->sendResponse($request, $response, "admin/subjects/edit.phtml", [
                "version" => $version,
                "info" => $info,
                "subjects" => $subjects,
                "sidebar_active" => "subjects"
            ]);
    
            return $response;

        } else if ($request->isPut()) {
            if ($version == 'new') {
                $name = $this->sanitizePost($request, 'name', FILTER_SANITIZE_STRING);

                if (is_null($name) || $name == "") {
                    return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Vyplňte jméno"]);
                }

                if ($this->db->has('versions', ['name' => $name])) {
                    return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Název již existuje"]);
                }

                $this->db->insert('versions', ['name' => $name]);

                $id = $this->db->id();

                return $response->withRedirect($this->container->router->pathFor('subjects-admin-subjects-edit', ['id' => $id]), 301);
            }
        } else if ($request->isPost()) {
            if (!$this->db->has('versions', ['id' => $version])) {
                return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Seznam nenalezen"]);
            }

            $limit = $this->sanitizePost($request, 'limit', \FILTER_VALIDATE_INT);
            $limit_spare = $this->sanitizePost($request, 'limit_spare', \FILTER_VALIDATE_INT);

            if ($limit === false || $limit < 0 || $limit_spare === false || $limit_spare < 0) {
                return $this->redirectWithMessage($response, 'subjects-admin-subjects-edit', "error", [
                    $this->container->lang->g('error-field-missing', 'admin-manage')
                ], ["id" => $version]);
            }

            $this->db->update('versions', ['limit' => $limit, 'limit_spare' => $limit_spare], ['id' => $version]);

            return $this->redirectWithMessage($response, 'subjects-admin-subjects-edit', "status", [
                $this->container->lang->g('success-saved', 'admin-manage')
            ], ["id" => $version]);
        }
    }
}
