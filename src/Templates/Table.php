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

class Table
{
    /**
     * @var string
     */
    private $tableId;
    /**
     * @var string
     */
    private $tableClass;
    /**
     * @var string
     */
    private $headerId;
    /**
     * @var string
     */
    private $headerClass;
    /**
     * @var bool
     */
    private $isDatatable;

    /**
     * @var array
     */
    private $data = [];
    /**
     * @var array
     */
    private $header = [];

    /**
     * Constructor for table class.
     *
     * @param string $id id name for this table
     * @param string $class class name for this table
     * @param bool $isDatatable is datatable
     */
    public function __construct($id, $class = null, $isDatatable = false)
    {
        $this->tableId = $id;
        if ($class !== null) {
            $this->tableClass = "class=\"$class\"";
        }
        $this->isDatatable = $isDatatable;
    }

    /**
     * Setter for table class name.
     *
     * @param string $class class name
     */
    public function setTableClass($class)
    {
        $this->tableClass = "class=\"$class\"";
    }

    /**
     * Setter for table id name.
     *
     * @param string $id id name
     */
    public function setTableId($id)
    {
        $this->tableId = $id;
    }

    /**
     * Setter for thead class name.
     *
     * @param string $class class name
     */
    public function setHeaderClass($class)
    {
        $this->headerClass = "class=\"$class\"";
    }

    /**
     * Setter for thead id name.
     *
     * @param string $id id name
     */
    public function setHeaderId($id)
    {
        $this->headerId = "id=\"$id\"";
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param array $row
     */
    public function addRow($row)
    {
        $this->data[] = $row;
    }

    /**
     * @param array $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * Get the table html.
     *
     * @return string
     */
    public function getHTML()
    {
        $table = "<table id=\"$this->tableId\" $this->tableClass>";

        if (!empty($this->header)) {
            $table .= "<thead $this->headerId $this->headerClass><tr>";
            foreach ($this->header as $header) {
                $table .= "<th>$header</th>";
            }
            $table .= '</tr></thead>';
        }

        $table .= '<tbody>';
        foreach ($this->data as $row) {
            $table .= '<tr>';
            foreach ($row as $col) {
                $table .= "<td>$col</td>";
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';

        if ($this->isDatatable) {
            $table .= '<script type="text/javascript">$("#' . $this->tableId . '").DataTable();</script>';
        }

        return $table;
    }
}
