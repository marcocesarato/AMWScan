<?php
/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace AMWScan\Templates;

use AMWScan\CodeMatch;
use AMWScan\Path;
use AMWScan\Scanner;

class Report
{
    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';
    /**
     * @var string
     */
    protected $dateFileFormat = 'Ymd-His';
    /**
     * @var array
     */
    protected $data;
    /**
     * @var string[]
     */
    protected $formatsText = ['text', 'txt', 'log', 'logs'];
    /**
     * @var string[]
     */
    protected $formatsHTML = ['html', 'htm'];

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Save report.
     *
     * @param $output
     * @param string $format
     *
     * @return string
     */
    public function save($output, $format = 'html')
    {
        $scanBasename = basename(Scanner::getPathScan());
        $scanBasename = strtolower($scanBasename);
        $scanBasename = str_replace('_', '-', $scanBasename);

        $output = str_replace(
            [
                '{DATE}',
                '{DIRNAME}',
            ],
            [
                date($this->dateFileFormat),
                $scanBasename,
            ],
            $output
        );

        // Textual report
        if (in_array($format, $this->formatsText)) {
            if (!preg_match('/[\S][.](' . implode('|', $this->formatsText) . ')$/', $output)) {
                $output .= '.log';
            }

            return $this->saveText($output);
        }

        // Html report
        if (in_array($format, $this->formatsHTML) &&
            !preg_match('/[\S][.](' . implode('|', $this->formatsHTML) . ')$/', $output)) {
            $output .= '.html';
        }

        // Default
        return $this->saveHTML($output);
    }

    /**
     * Save report with html format.
     *
     * @param $output
     *
     * @return string
     */
    protected function saveHTML($output)
    {
        $template = __DIR__ . '/Report.html';
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
                            case CodeMatch::WARNING:
                                $warnings++;
                                $badges[] = '<span class="badge badge-pill badge-warning py-1 px-2 ml-1 shadow-none">Warning</span>';
                                break;
                            case CodeMatch::DANGEROUS:
                                $dangerous++;
                                $badges[] = '<span class="badge badge-pill badge-danger py-1 px-2 ml-1 shadow-none">Dangerous</span>';
                                break;
                        }
                    }

                    $description = '-';
                    if (isset($item['description'])) {
                        $description = '<p>' . htmlentities($item['description']) . '</p>';

                        if (isset($item['link'])) {
                            $links = explode(',', $item['link']);
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
                        '<p><b>' . ucfirst($item['type']) . ' ' . htmlentities($item['key']) . ' ' . implode(' ', $badges) . '</b></p>' .
                        $description,
                        '<code>' . $match . '</code>',
                    ];
                }
                usort($rows, function ($item1, $item2) {
                    if ($item1[0] === $item2[0]) {
                        return 0;
                    }

                    return $item1[0] < $item2[0] ? -1 : 1;
                });
                $table = new Table('file_' . $num, 'table container table-sm table-striped table-borderless', true);
                $table->setHeader($header);
                $table->setData($rows);
                $tableContent = $table->getHTML();
                $fileSize = Path::getFilesize($filePath);
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

        return $output;
    }

    /**
     * Save report with text format.
     *
     * @param $output
     *
     * @return string
     */
    protected function saveText($output)
    {
        file_put_contents($output, $this->data['content']);

        return $output;
    }
}
