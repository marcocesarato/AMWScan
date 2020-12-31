<?php

namespace marcocesarato\amwscan\Templates;

use marcocesarato\amwscan\Definitions;
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
                'Description',
                'Match',
            ];
            $num = 0;
            foreach ($this->data['results'] as $filePath => $items) {
                $dangerous = 0;
                $warnings = 0;
                $rows = [];
                foreach ($items as $item) {
                    $badges = [];
                    if (!empty($item['line'])) {
                        $badges[] = '<span class="badge badge-pill badge-secondary py-1 px-2 ml-1 shadow-none">Line: ' . $item['line'] . '</span>';
                    }

                    if (!empty($item['level'])) {
                        switch ($item['level']) {
                            case Definitions::LVL_WARNING:
                                $warnings++;
                                $badges[] = '<span class="badge badge-pill badge-warning py-1 px-2 ml-1 shadow-none">Warning</span>';
                                break;
                            case Definitions::LVL_DANGEROUS:
                                $dangerous++;
                                $badges[] = '<span class="badge badge-pill badge-danger py-1 px-2 ml-1 shadow-none">Dangerous</span>';
                                break;
                        }
                    }

                    $description = '-';
                    if (isset($item['exploit'])) {
                        $description = '<p>' . htmlentities($item['exploit']['description']) . '</p>';

                        if (isset($item['exploit']['link'])) {
                            $links = explode(',', $item['exploit']['link']);
                            foreach ($links as $key => $link) {
                                $links[$key] = '<a href="' . $link . '" target="_blank" class="text-primary">' . $link . '</a>';
                            }
                            $description .= '<p><i>[' . implode(', ', $links) . ']</i></p>';
                        }
                    }

                    $maxLength = 500;
                    $match = trim($item['match']);
                    $match = strlen($match) > $maxLength ? substr($match, 0, $maxLength) . '...' : $match;
                    $match = highlight_string('<?php ' . $match, true);
                    $match = str_replace('&lt;?php&nbsp;', '', $match);

                    $rows[] = [
                        '<p><b>' . $item['type'] . ' ' . htmlentities($item['key']) . ' ' . implode(' ', $badges) . '</b></p>' .
                        $description,
                        '<code>' . $match . '</code>',
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

                $badges = [];
                $badges[] = '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Size: ' . $fileSize . '</span>';
                $badges[] = '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Created: ' . $fileCreateDate . '</span>';
                $badges[] = '<span class="badge badge-pill badge-fileinfo py-2 px-3 mr-1 shadow-none">Modified: ' . $fileModifiedDate . '</span>';

                if ($warnings > 0) {
                    $badges[] = '<span class="badge badge-pill badge-warning py-2 px-3 mr-1 shadow-none">Warns: ' . $warnings . '</span>';
                }

                if ($dangerous > 0) {
                    $badges[] = '<span class="badge badge-pill badge-danger py-2 px-3 mr-1 shadow-none">Dangers: ' . $dangerous . '</span>';
                }

                $rowContent = '<p><i class="fas fa-file text-primary mr-2"></i> ' . $filePath . '</p>' . implode(' ', $badges);
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

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }
}
