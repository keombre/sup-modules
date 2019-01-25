<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class dash extends Controller {

    function __invoke(Request $request, Response $response, $args) {
        $versions = $this->container->db->select("versions", "*");
        $allowDownload = is_string($this->settings['active_version']);
        $response = $this->sendResponse($request, $response, "admin/dash.phtml", [
            "versions" => $versions,
            "settings" => $this->settings,
            "allowDownload" => $allowDownload
        ]);
    }
}
