<?php

namespace Mapbender\CoreBundle\Asset;

class SourceMapBundler
{
    private array $files = [];
    private array $mappings = [];
    private string $outFilename;

    /** @var resource */
    private $outFile;
    private int $offset = 0;

    public function __construct(string $sourceMapFilename)
    {
        $this->outFile = fopen($sourceMapFilename, 'w');
        $this->outFilename = $sourceMapFilename;
    }

    public function addScript(string $path): self
    {
        $index = count($this->files);
        $this->files[] = $path;

        $data = file_get_contents($path);
        fwrite($this->outFile, $data . "\n");

        $lines = explode("\n", $data);
        for ($i = 0; $i < count($lines); ++$i) {
            $this->mappings[] = [
                'gen_line' => $this->offset + $i,
                'gen_col' => 0,
                'src_index' => $index,
                'src_line' => $i,
                'src_col' => 0];
        }
        $this->offset += count($lines);
        return $this;
    }

    /**
     * creates the source map
     * Write the output to a file and link to it in the generated source file:
     * `/*# sourceMappingURL=$sourceFile * /`
     */
    public function build(): string
    {
        return $this->buildMap($this->outFilename);
    }

    private function buildMap(string $mapFile): string
    {
        return json_encode(array(
            "version" => 3,
            "file" => $mapFile,
            "sourceRoot" => "",
            "sources" => $this->files,
            "names" => array(),
            "mappings" => $this->generateMappings()
        ));
    }


    public function generateMappings(): string
    {
        $mappingEncoded = array();

        $last_gen_line = 0;
        $last_src_index = 0;
        $last_src_line = 0;
        $last_src_col = 0;

        foreach ($this->mappings as $m) {
            $gen_line = $m['gen_line'];
            while (++$last_gen_line < $gen_line) {
                $mappingEncoded[] = ";";
            }

            $line_map_enc = array();

            $m_enc = Base64VLQ::encode($m['gen_col']);
            if (isset($m['src_index'])) {
                $m_enc .= Base64VLQ::encode($m['src_index'] - $last_src_index);
                $last_src_index = $m['src_index'];

                $m_enc .= Base64VLQ::encode($m['src_line'] - $last_src_line);
                $last_src_line = $m['src_line'];

                $m_enc .= Base64VLQ::encode($m['src_col'] - $last_src_col);
                $last_src_col = $m['src_col'];
            }
            $line_map_enc[] = $m_enc;

            $mappingEncoded[] = implode(",", $line_map_enc) . ";";
        }

        return implode($mappingEncoded);
    }

}
