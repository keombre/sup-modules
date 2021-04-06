<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\stats;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Dash extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        if ($request->isGet()) {
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
        } else if ($request->isPut()) {
            $limit3 = $this->db->get('versions', ['limit [Int]', 'limit_spare [Int]'], ['id' => $this->settings['active_version_7']]);
            $limit4 = $this->db->get('versions', ['limit [Int]', 'limit_spare [Int]'], ['id' => $this->settings['active_version_8']]);

            $entries = [];
            foreach ($this->db->select('main', [
                    'id [Index]',
                    'version [Int]'
            ], [
                'version' => [
                    $this->settings['active_version_7'],
                    $this->settings['active_version_8']
                ],
                'state[!]' => 0
            ]) as $entry) {

            $subjects = $this->db->select('lists', ['[>]subjects' => ['subject' => 'id']], 'lists.level [Int]', ['lists.list' => $entry['id'], 'subjects.state' => 0]);
            $counts = array_count_values($subjects);

            if ($entry['version'] == 3) {
                    if (!array_key_exists(0, $counts) || $counts[0] !== $limit3['limit']) {
                            $this->db->update('main', ['state' => 3], ['id' => $entry['id']]);
                            continue;
                    }
                    if (!array_key_exists(1, $counts) || ! array_key_exists(2, $counts) || $counts[1] + $counts[2] !== $limit3['limit_spare']) {
                            $this->db->update('main', ['state' => 3], ['id' => $entry['id']]);
                            continue;
                    }
            } else if ($entry['version'] == 4) {
                    if (!array_key_exists(0, $counts) || $counts[0] !== $limit4['limit']) {
                            $this->db->update('main', ['state' => 3], ['id' => $entry['id']]);
                            continue;
                    }
                    if (!array_key_exists(1, $counts) || ! array_key_exists(2, $counts) || $counts[1] + $counts[2] !== $limit4['limit_spare']) {
                            $this->db->update('main', ['state' => 3], ['id' => $entry['id']]);
                            continue;
                    }
            }
            $this->db->update('main', ['state' => 2], ['id' => $entry['id']]);
/*
                if ($this->db->has('lists', [
                    '[>]subjects' => ['subject' => 'id']
                ], [
                    'lists.list' => $entry['id'],
                    'subjects.state' => 1
                ])) {
                    $this->db->update('main', ['state' => 3], ['id' => $entry['id']]);
                } else {
                    $this->db->update('main', ['state' => 2], ['id' => $entry['id']]);
                }
            */
            }
            return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "status", [
                'Zápisy přepočítány'
            ]);
        }

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
            $info = $this->db->select('lists', [
                '[>]main' => ['list' => 'id']
            ], 'main.id', [
                'lists.subject' => $entry['id'],
                'main.state' => [2, 3],
                'main.version' => $this->settings['active_version_' . $year],
                'lists.level' => 0
            ]);


            $entries[$id]['entries'] = $this->db->count('lists', [
                '[>]main' => ['list' => 'id']
            ], 'main.id', [
                'lists.subject' => $entry['id'],
                'main.state' => [2, 3],
                'main.version' => $this->settings['active_version_' . $year],
                'lists.level' => 0
            ]);

            $entries[$id]['entries_spare'] = $this->db->count('lists', [
                '[>]main' => ['list' => 'id']
            ], 'lists.list', [
                'lists.subject' => $entry['id'],
                'main.state' => [2, 3],
                'main.version' => $this->settings['active_version_' . $year],
                'lists.level' => [1, 2]
            ]);
        }

        return $entries;
    }
}
