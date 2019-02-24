<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Settings extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        
        $edit = $this->sanitizePost($request, 'edit', FILTER_SANITIZE_STRING);
        $accept = $this->sanitizePost($request, 'accept', FILTER_SANITIZE_STRING);
        $active7 = $this->sanitizePost($request, 'active7', FILTER_SANITIZE_STRING);
        $active8 = $this->sanitizePost($request, 'active8', FILTER_SANITIZE_STRING);

        if (!$this->container->db->has("versions", ["id" => $active7]) ||
            !$this->container->db->has("versions", ["id" => $active8])
        ) {
            return $this->redirectWithMessage($response, 'subjects-admin', "error", [
                $this->container->lang->g('settings-version-notfound', 'admin-dash')
            ]);
        }
        
        $store = [
            "active_version_7" => $active7,
            "active_version_8" => $active8,
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
