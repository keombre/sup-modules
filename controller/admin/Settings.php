<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Settings extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        
        $edit = $this->sanitizePost($request, 'edit', FILTER_SANITIZE_STRING);
        $accept = $this->sanitizePost($request, 'accept', FILTER_SANITIZE_STRING);
        $active = $this->sanitizePost($request, 'active', FILTER_SANITIZE_STRING);

        if (!$this->container->db->has("versions", ["id" => $active])) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('settings-version-notfound', 'admin-dash')
            ]);
        }
        
        $store = [
            "active_version" => $active,
            "open_editing"   => $edit == "on",
            "open_accepting"  => $accept == "on"
        ];
        if ($this->container->db->count("settings")) {
            $this->container->db->update("settings", $store);
        } else {
            $this->container->db->insert("settings", $store);
        }

        return $this->redirectWithMessage($response, 'subjects-admin', "status", [
            $this->container->lang->g('settings-saved', 'admin-dash')
        ]);
    }
}
