<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\entries;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $entries3 = $this->getEntries(7);
        $entries4 = $this->getEntries(8);

        $response = $this->sendResponse($request, $response, "admin/entries/dash.phtml", [
            "entries3" => $entries3,
            "entries4" => $entries4,
            "sidebar_active" => "entries"
        ]);

        return $response;
    }

    private function getEntries(int $year)
    {
        if ($year != 7 && $year != 8)
            return false;

        $entries = [];
        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], [
            'version' => $this->settings['active_version_' . $year],
            'state[!]' => 0
        ]) as $entry) {
            $entries[] = [
                'user' => $this->container->factory->userFromID($entry['user']),
                'list' => $entry['id'],
                'invalid' => $this->db->count('lists', [
                    '[>]subjects' => ['subject' => 'id']
                ], 'subjects.id', [
                    'lists.list' => $entry['id'],
                    'subjects.state' => 1
                ]),
                'total' => $this->db->count('lists', [
                    '[>]subjects' => ['subject' => 'id']
                ], 'subjects.id', [
                    'lists.list' => $entry['id']
                ])
            ];
        }

        return $entries;
    }
}
