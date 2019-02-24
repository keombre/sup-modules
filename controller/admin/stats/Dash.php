<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\stats;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        
        $entries3 = $this->getEntries(7);
        $entries4 = $this->getEntries(8);

        $this->entrySort($entries3);
        $this->entrySort($entries4);

        $version3 = $this->settings['active_version_7'];
        $version4 = $this->settings['active_version_8'];

        $response = $this->sendResponse($request, $response, "admin/stats/dash.phtml", [
            "entries3" => $entries3,
            "entries4" => $entries4,
            "version3" => $version3,
            "version4" => $version4,
            "sidebar_active" => "stats"
        ]);

        return $response;
    }

    private function entrySort(array &$array) {
        usort($array, function($a, $b) {
            $level = $b['entries'] <=> $a['entries'];
            return $level == 0 ? $b['entries_spare'] <=> $a['entries_spare'] : $level;
        });
    }

    private function getEntries(int $year)
    {
        if ($year != 7 && $year != 8)
            return false;
        
        $entries = $this->db->select('subjects', [
            'id [Index]',
            'code [String]',
            'name [String]',
            'state [Int]'
        ], [
            'version' => $this->settings['active_version_' . $year]
        ]);

        foreach ($entries as $id => $entry) {
            $entries[$id]['entries'] = $this->db->count('lists', [
                '[>]main' => ['list' => 'id']
            ], 'lists.list', [
                'lists.subject' => $entry['id'],
                'main.state' => 2,
                'main.version' => $this->settings['active_version_' . $year],
                'lists.level' => 0
            ]);

            $entries[$id]['entries_spare'] = $this->db->count('lists', [
                '[>]main' => ['list' => 'id']
            ], 'lists.list', [
                'lists.subject' => $entry['id'],
                'main.state' => 2,
                'main.version' => $this->settings['active_version_' . $year],
                'lists.level' => 1
            ]);
        }

        return $entries;
    }
}
