<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $versions = $this->container->db->select("versions", "*");
        $allowDownload = \is_string($this->settings['active_version_7']) && \is_string($this->settings['active_version_7']);
        
        $response = $this->sendResponse($request, $response, "admin/dash.phtml", [
            "versions" => $versions,
            "settings" => $this->db->get('settings', '*'),
            "allowDownload" => $allowDownload,
            "sidebar_active" => "dash"
        ]);
    }
}
