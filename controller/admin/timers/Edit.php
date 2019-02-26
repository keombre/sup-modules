<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\timers;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

use PASVL\Traverser\VO\Traverser;
use PASVL\ValidatorLocator\ValidatorLocator;

class Edit extends Controller
{

    private $schema = [
        ':int :in(3,4) {2}' => [
            ':int :in(1,2) {2}' => [
                ':string :in(open,close) {2}' => [
                    'time' => ':string :regex(#\d{2}:\d{2}#)',
                    'date' => ':string :date'
                ]
            ]
        ]
    ];

    public function __invoke(Request $request, Response $response, $args)
    {
        $time = $this->sanitizePostArray($request, 'time', \FILTER_SANITIZE_STRING);

        $traverser = new Traverser(new ValidatorLocator());

        try {
            $traverser->match($this->schema, $time);
            
            $this->db->update('versions', [
                'timer1 [JSON]' => $time[3][1],
                'timer2 [JSON]' => $time[3][2]
            ], [
                'id' => $this->settings['active_version_7']
            ]);

            $this->db->update('versions', [
                'timer1 [JSON]' => $time[4][1],
                'timer2 [JSON]' => $time[4][2]
            ], [
                'id' => $this->settings['active_version_8']
            ]);

            return $this->redirectWithMessage($response, 'subjects-admin-timers-dash', "status", [
                'Uloženo'
            ]);
            
        } catch (\PASVL\Traverser\FailReport $report) {

            if ($report->getReason()->isValueType()) {
                return $this->redirectWithMessage($response, 'subjects-admin-timers-dash', "error", [
                    'Chybná data'
                ]);
            } else {
                return $this->redirectWithMessage($response, 'subjects-admin-timers-dash', "error", [
                    'Chyba aplikace'
                ]);
            }

        }
    }
}
