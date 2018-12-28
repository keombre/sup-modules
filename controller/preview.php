<?php

namespace modules\lists\controller;

class preview extends lists {

    protected $state = null;

    public function student($request, &$response, $args) {
        $state = $this->db->get('lists_main', 'state', ['id' => $this->listID]);

        if ($state == 0)
            return $response->withRedirect($this->container->router->pathFor('lists-edit', ["id" => $this->listID]), 301);

        return $this->preview($request, $response, $args);
    }

    public function teacher($request, &$response, $args) {
        $response->getBody()->write("kitten");
    }

    public function admin($request, &$response, $args) {
        $response->getBody()->write("kitten");
    }

    public function preview($request, &$response, $args) {
        
        $versionName = $this->db->get('lists_versions', 'name [String]', ['id' => $this->settings['active_version']]);

        $list = $this->db->get('users', ['[>]userinfo' => 'id', '[>]lists_main' => ['id' => 'user']], [
            'user' => [
                'users.name(code) [String]',
                'name' => [
                    'userinfo.givenname(given) [String]',
                    'userinfo.surname(sur) [String]'
                ],
                'userinfo.class [String]'
            ],
            'lists_main.state [Int]',
            'lists_main.id [Int]'
        ], ['lists_main.id' => $this->listID]);

        $list['books'] = $this->db->select('lists_lists', ['[>]lists_books' => ['book' => 'id']], [
            'lists_books.id [Int]',
            'lists_books.name [String]',
            'lists_books.author [String]'
        ], ['lists_lists.list' => $this->listID, 'ORDER' => 'lists_books.id']);

        $qrURL = (string) $request
                ->getUri()
                ->withPath($this->container->router->pathFor("lists-teacher-accept", ["id" => $this->formatBarcode($list)]))
                ->withQuery(http_build_query(['b' => base64_encode(implode('-', array_column($list['books'], 'id')))]))
                ->withFragment("");
        
        $qrcode = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        ])))->render($qrURL);
        
        $generatorPNG = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = base64_encode($generatorPNG->getBarcode($this->formatBarcode($list), $generatorPNG::TYPE_CODE_39E, 1.5));

        $this->sendResponse($request, $response, "lists/preview.phtml", [
            "barcode" => $barcode,
            "qrcode" => $qrcode,
            "versionName" => $versionName,
            "list" => $list
        ]);
        return $response;
    }

    private function formatBarcode($list) {
        return 'C' . $list['id'] . "-U" . $list['user']['code'];
    }
}
