<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\subjects;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $versions = $this->container->db->select("versions",[
            "id [Index]",
            "name [String]",
            "limit [Int]",
            "limit_spare [Int]",
        ]);

        foreach ($versions as $id => $version) {
            $versions[$id]['count'] = $this->db->count('subjects', ['version' => $id]);
        }

        $response = $this->sendResponse($request, $response, "admin/subjects/dash.phtml", [
            "versions" => $versions,
            "sidebar_active" => "subjects"
        ]);

        return $response;
    }
}
