<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\export;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Download extends Controller {

    private $lookup = [
        -1 => '-',
         0 => '1',
         1 => 'N1',
         2 => 'N2'
    ];

    private $separator = ';';

    public function __invoke(Request $request, Response $response, $args) {

        $version = \filter_var($args['version'], \FILTER_SANITIZE_STRING);

        if (!$this->db->has('versions', ['id' => $version])) {
            return $this->redirectWithMessage($response, 'subjects-admin-export-dash', "error", ["Verze nenalezena"]);
        }

        $versionName = $this->db->get('versions', 'name', ['id' => $version]);

        $handle = fopen('php://temp', 'rw');
        fwrite($handle, "\xEF\xBB\xBF");

        $header = $this->db->select('subjects', [
            'id [Index]',
            'code [String]'
        ], [
            'version' => $version
        ]);

        fputcsv($handle, array_merge(['Třída', 'Příjmení', 'Jméno'], array_column($header, 'code', 'id')), $this->separator);

        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], [
            'version' => $version,
            'state' => 2
        ]) as $entry) {
            $user = $this->container->factory->userFromID($entry['user']);
            $subjects = $this->db->select('lists', [
                'subject [Index]',
                'level [Int]'
            ], [
                'list' => $entry['id']
            ]);

            $data = [];
            foreach ($header as $column) {
                $data[$column['id']] = $this->lookup[$subjects[$column['id']]['level'] ?? -1];
            }

            $user_name = $user->getAttribute('name');

            fputcsv($handle, array_merge([$user->getAttribute('class'), $user_name['sur'], $user_name['given']], $data), $this->separator);
        }

        return $this->sendStream($response, $handle, 'SUPi_export_' . str_replace(' ', '_', $versionName) . '.csv');

    }
}
