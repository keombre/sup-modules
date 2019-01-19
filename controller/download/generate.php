<?php declare(strict_types=1);

namespace modules\lists\controller\download;

class generate extends \sup\controller
{
    public function __construct(\Slim\Container $container)
    {
        parent::__construct($container);
        
        $this->settings = $this->db->get("settings", "*");
    }

    public function __invoke($request, $response, $args)
    {
        if (!is_string($this->settings['active_version']))
            return $this->redirectWithMessage($response, 'lists', "error", ["Nejdřív zvolte verzi"]);
        
        $books = $this->getBooks();
        $lists = $this->getLists();

        $this->normalizeID($books, $lists);

        $books = $this->formatBooks($books);
        foreach ($lists as $listID => $list)
            $lists[$listID]['books'] = $this->formatLists($list);
        
        $fname = sys_get_temp_dir() .  '/sup-archive.zip';
        
        if (is_file($fname))
            unlink($fname);
        
        $this->createZip($books, $lists, $fname);
        return $this->sendFile($response, $fname, 'archive.zip');
    }

    private function normalizeID(array &$books, array &$lists) {
        $i = 1;

        foreach ($books as $bookID => $book)
            $books[$bookID]['id'] = $i++;
        
        foreach ($lists as $listID => $list) {
            foreach ($list['books'] as $userBookID => $userBook) {
                $lists[$listID]['books'][$userBookID]['id'] = $books[$userBook['id']]['id'];
            }
        }
    }

    private function createZip($books, $lists, $fname) {
        $zip = new \ZipArchive();
        $filename = $fname;

        if ($zip->open($filename, \ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$filename>\n");
        }

        $zip->addFromString('kanon.csv', $books);
        foreach ($lists as $list) {
            $filename = is_null($list['class']) ? '': strtolower($list['class']) . '/';
            $filename .= preg_replace('/[[:^print:]]/', '', str_replace(' ', '-', \strtolower($list['user']))) . '.gms';

            $zip->addFromString('users/' . $filename, $list['books']);
        }
        $zip->close();
    }

    private function getBooks():array {
        return $this->db->select('books', [
            'id [Index]',
            'region [Int]',
            'author [String]',
            'name [String]'
        ], ['version' => $this->settings['active_version']]);
    }

    private function getLists():array {
        $ret = [];
        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], ['state' => 2, 'version' => $this->settings['active_version']]) as $list) {
            $user = $this->container->factory->userFromID($list['user']);
            
            $name = $user->getName();
            if ($name == ' ')
                $name = $user->getUName();

            $books = $this->db->select('lists', ['[>]books' => ['book' => 'id']], [
                'books.id [Index]',
                'books.region [Int]',
                'books.author [String]',
                'books.name [String]'
            ], [
                'version' => $this->settings['active_version'],
                'lists.list' => $list['id']
            ]);

            $ret[] = [
                'id' => $list['id'],
                'user' => $name,
                'class' => $user->getAttribute('class'),
                'books' => $books
            ];
        }
        return $ret;
    }

    private function formatLists(array $list):string {
        $file = \fopen('php://temp', 'rw');
        \fwrite($file, $list['user'] . PHP_EOL);

        foreach ($list['books'] as $book) {
            \fwrite($file, 'A');
            \fputcsv($file, $book, ';');
        }

        \rewind($file);
        return \stream_get_contents($file);
    }
    
    private function formatBooks(array $books):string {
        $csv = \fopen('php://temp', 'rw');

        foreach ($books as $book) {
            \fputcsv($csv, $book, ';');
        }

        \rewind($csv);
        return \stream_get_contents($csv);
    }
}
