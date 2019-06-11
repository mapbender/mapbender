<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


class ExportDataPool extends ObjectIdentityPool
{
    public function getAllGroupedByClassName()
    {
        $dataOut = array();
        foreach ($this->uniqueClassNames as $ucn) {
            $ucnEntries = array();
            foreach ($this->entries as $key => $data) {
                if (preg_replace('/#.*$/', '', $key) === $ucn) {
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
