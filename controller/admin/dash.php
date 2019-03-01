<?php declare(strict_types=1);

namespace modules\subjects\controller\admin;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        //$versions = $this->container->db->select("versions", "*");
        //$allowDownload = \is_string($this->settings['active_version_7']) && \is_string($this->settings['active_version_7']);
        $problems = $this->getEntries();
        $response = $this->sendResponse($request, $response, "admin/dash.phtml", [
            /*"versions" => $versions,
            "settings" => $this->db->get('settings', '*'),
            "allowDownload" => $allowDownload,*/
            "problems" => $problems,
            "sidebar_active" => "dash"
        ]);
    }

    private function getEntries()
    {   
        $entries = [];
        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], [
            'version' => [
                $this->settings['active_version_7'],
                $this->settings['active_version_8']
            ],
            'state[!]' => 0
        ]) as $entry) {
            $invalid = $this->db->count('lists', [
                '[>]subjects' => ['subject' => 'id']
            ], 'subjects.id', [
                'lists.list' => $entry['id'],
                'subjects.state' => 1
            ]);
            if ($invalid) {
                $entries[] = [
                    'user' => $this->container->factory->userFromID($entry['user']),
                    'id' => $entry['id'],
                    'invalid' => $invalid
                ];
            }
        }

        return $entries;
    }

}
