<?php
/*
 *  This program comes with ABSOLUTELY NO WARRANTY; for details type `show w'.
 *     This is free software, and you are welcome to redistribute it
 *     under certain conditions; type `show c' for details.
 */

namespace marcocesarato\amwscan\Templates;

use marcocesarato\amwscan\Scanner;

class Report
{
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $data;

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $output
     */
    public function generate($output)
    {
        $template = __DIR__ . '/html/Report.html';
        $content = file_get_contents($template);
        $mainContent = '<h3>No malware founds!</h3>';

        if (!empty($this->data['results'])) {
            $mainTable = new Table('report', 'datatable container table table-bordered');
            $header = [
                'Line',
                'Type',
                'Name',
                'Code',
            ];
            $num = 0;
            foreach ($this->data['results'] as $filePath => $items) {
                $rows = [];
                foreach ($items as $item) {
                    $rows[] = [
                        (empty($item['line']) ? '-' : $item['line']),
                        $item['type'],
                        htmlentities($item['key']),
                        '<code>...' . htmlentities(trim($item['match'])) . '...</code>',
                    ];
                }
                usort($rows, function ($item1, $item2) {
                    if ($item1[0] == $item2[0]) {
                        return 0;
                    }

                    return $item1[0] < $item2[0] ? -1 : 1;
                });
                $table = new Table('file_' . $num, 'table container table-sm table-striped table-borderless', true);
                $table->setHeader($header);
                $table->setData($rows);
                $tableContent = $table->getHTML();
                $fileSize = $this->getFilesize($filePath);
                $fileCreateDate = date($this->dateFormat, filemtime($filePath));
                $fileModifiedDate = date($this->dateFormat, filectime($filePath));

                $rowContent = '<p><i class="fas fa-file text-primary mr-2"></i> ' . $filePath . '</p>';
                $rowContent .= '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Size: ' . $fileSize . '</span>';
                $rowContent .= '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Created: ' . $fileCreateDate . '</span>';
                $rowContent .= '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Modified: ' . $fileModifiedDate . '</span>';
                $rowContent .= '<span class="badge badge-pill badge-danger py-2 px-3 mr-1 shadow-none">Found: ' . count($rows) . '</span>';
                $rowContent .= '<br>' . $tableContent;

                $mainTable->addRow([$rowContent]);
                $num++;
            }
            $mainContent = $mainTable->getHTML();
        }

        $content = str_replace(
            [
                '{VERSION}',
                '{DATE}',
                '{COUNT}',
                '{INFECTED}',
                '{CONTENTS}',
            ],
            [
                Scanner::getVersion(),
                date($this->dateFormat),
                $this->data['count'],
                count($this->data['results']),
                $mainContent,
            ],
            $content
        );

        file_put_contents($output, $content);
    }

    /**
     * Get filesize.
     *
     * @param $filePath
     * @param int $dec
     *
     * @return string
     */
    protected function getFilesize($filePath, $dec = 2)
    {
        $bytes = filesize($filePath);
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
