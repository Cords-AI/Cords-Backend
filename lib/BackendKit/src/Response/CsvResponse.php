<?php

namespace BackendKit\Response;

use Symfony\Component\HttpFoundation\Response;

class CsvResponse extends Response
{
    public function __construct(array $list)
    {
        $utf8ByteOrderMark = "\xEF\xBB\xBF";

        $fp = fopen('php://temp', 'w');
        fwrite($fp, $utf8ByteOrderMark);

        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        rewind($fp);
        parent::__construct(stream_get_contents($fp));
        fclose($fp);

        $this->headers->set('Content-Encoding', 'UTF-8');
        $this->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $this->headers->set('Content-Disposition', 'attachment; filename="testing.csv"');
    }
}
