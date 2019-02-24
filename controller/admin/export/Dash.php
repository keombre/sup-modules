<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\export;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $versions = ['active' => [], 'other' => []];
        
        foreach ($this->db->select('versions', [
            'id [Index]',
            'name [String]'
        ]) as $version) {
            if (
                $version['id'] == $this->settings['active_version_7'] ||
                $version['id'] == $this->settings['active_version_8']
            ) {
                $versions['active'][] = $version;
            } else {
                $versions['other'][] = $version;
            }
        }

        $response = $this->sendResponse($request, $response, "admin/export/dash.phtml", [
            "versions" => $versions,
            "sidebar_active" => "export"
        ]);

        return $response;
    }
}
