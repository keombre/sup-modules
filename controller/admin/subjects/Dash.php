<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\subjects;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        if ($request->isGet()) {
            $versions = $this->container->db->select("versions", [
                "id [Index]",
                "name [String]",
                "limit [Int]",
                "limit_spare [Int]",
            ]);

                foreach ($versions as $id => $version) {
                    $versions[$id]['count'] = $this->db->count('subjects', ['version' => $id]);
                }

                $active_versions = [
                7 => $this->settings['active_version_7'],
                8 => $this->settings['active_version_8']
            ];

                $response = $this->sendResponse($request, $response, "admin/subjects/dash.phtml", [
                "active_versions" => $active_versions,
                "versions" => $versions,
                "sidebar_active" => "subjects"
            ]);

            return $response;
        } else if ($request->isPut()) {
            $v7 = $this->sanitizePost($request, 'v7', FILTER_SANITIZE_STRING);
            $v8 = $this->sanitizePost($request, 'v8', FILTER_SANITIZE_STRING);

            if (is_null($v7) || is_null($v8)) {
                return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Zvolte verze"]);
            }

            if (
                !$this->db->has('versions', ['id' => $v7]) ||
                !$this->db->has('versions', ['id' => $v7])
            ) {
                return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "error", ["Verze nenalezena"]);
            }

            $this->db->update('settings', [
                'active_version_7' => $v7,
                'active_version_8' => $v8
            ]);

            return $this->redirectWithMessage($response, 'subjects-admin-subjects-dash', "status", ["Uloženo"]);
        }
    }
}
