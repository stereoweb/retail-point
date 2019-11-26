<?php
namespace Stereo\RTP;

class XMLParser
{
    private $reader;
    private $xml;

    public $errors = [];

    public function __construct($file = false)
    {
        $this->xml = $file;

        if ($this->xml === false) {
            $this->errors[] = "Missing first argument : file";
        }

        if (empty($this->xml)) {
            $this->errors[] = "File argument empty";
        }

        if (!file_exists($this->xml)) {
            $this->errors[] = "File doesn't exist";
        }

        $this->check_errors();
    }

    public function check_errors()
    {
        if (count($this->errors)) {
            foreach ($this->errors as $error) {
                throw new Exception($error);
            }
        }
    }

    public function open()
    {
        $this->reader = new \XMLReader();
        $this->reader->open($this->xml);
    }

    public function close()
    {
        $this->reader->close();
    }

    public function fetch($json = false)
    {
        while ($this->reader->read()) {
            if ($this->reader->nodeType == \XMLReader::END_ELEMENT) {
                continue; //skips the rest of the code in this iteration
            }

            if ($this->reader->name == 'product') {
                $element = new \SimpleXMLElement($this->reader->readOuterXML());

                if ($json) {
                    $element = json_decode(json_encode($element), true);
                }

                return $element;
            }
        }

        $this->close();
        return false;
    }

    public function fetch_giftcard($json = false)
    {
        while ($this->reader->read()) {
            if ($this->reader->nodeType == \XMLReader::END_ELEMENT) {
                continue; //skips the rest of the code in this iteration
            }

            if ($this->reader->name == '_QUERY_CC_1') {
                $element = new \SimpleXMLElement($this->reader->readOuterXML());

                if ($json) {
                    $element = json_decode(json_encode($element), true);
                }

                return $element;
            }
        }

        $this->close();
        return false;
    }

    public function list_products_attributes()
    {
        $attributes = [];
        $this->open();

        while ($element = $this->fetch()) {
            foreach ($element->skus as $skus) {
                foreach ($skus->sku->children() as $key => $value) {
                    $attributes[$key] = $key;
                }
            }
        }

        natcasesort($attributes);

        return $attributes;
    }

    public function get_category_tree()
    {
        $tree = [];
        $this->open();

        while ($element = $this->fetch()) {
            foreach ($element->skus as $skus) {
                $dept = trim(strval($skus->sku->Dept), " \\\"");
                $sub = trim(strval($skus->sku->SubDept), " \\\"");

                if (empty($dept)) continue 1;

                if (!isset($tree[$dept])) {
                    $tree[$dept] = [];
                }

                if (empty($sub)) continue 1;

                if (!in_array($sub, $tree[$dept])) {
                    $tree[$dept][] = $sub;
                }
            }
        }

        ksort($tree);
        foreach ($tree as &$sub) {
            asort($sub);
        }

        return $tree;
    }

    public function products_count()
    {
        $this->open();
        $count = 0;

        while ($element = $this->fetch()) $count ++;

        return $count;
    }
}
