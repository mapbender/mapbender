<?php
namespace Mapbender\CoreBundle\Component;

class SrsParser {

    /**
     * Parses a srs definition file
     * @param string $filepath path to a definition file
     * @param string $type the type of the definition
     * @return array srs definition
     */
    public function parseSrsData($filepath, $type) {
        $data = array();
        $file = @fopen($filepath, "r");
        $temp = "";
        while (!feof($file)) {
            $currentLine = fgets($file);
            if (strpos($currentLine, "#") === 0) {
                $temp = array("title" => substr($currentLine, 2));
            } else {
                $str = explode("> ", (str_ireplace("<>", "", substr($currentLine, 1))));
                $temp["name"] = str_ireplace("\n", "", $str[0]);
                $temp["definition"] = str_ireplace("\n", "", $str[1]);
                $data[] = array(
                    "name" => $type . ":" . $temp["name"],
                    "title" => $temp["title"],
                    "definition" => $type . ":" . $temp["definition"]);
            }
        }

        fclose($file);
        return $data;
    }

}
