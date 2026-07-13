<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


class ExportDataPool extends ObjectIdentityPool
{
    /**
     * @return \non-empty-list<mixed>[]
     */
    public function getAllGroupedByClassName(): array
    {
        $dataOut = [];
        foreach ($this->uniqueClassNames as $ucn) {
            $ucnEntries = [];
            foreach ($this->entries as $key => $data) {
                if (preg_replace('/#.*$/', '', (string) $key) === $ucn) {
                    $ucnEntries[] = $data;
                }
            }
            if ($ucnEntries) {
                $dataOut[$ucn] = $ucnEntries;
            }
        }
        return $dataOut;
    }
}
