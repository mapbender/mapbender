<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Mapbender\CoreBundle\Utils\ArrayUtil;

abstract class RoutingDriver {

    const INSTR_CONTINUE = 'continue';
    const INSTR_LEFT1 = 'left1';
    const INSTR_LEFT2 = 'left2';
    const INSTR_LEFT3 = 'left3';
    const INSTR_LEFT4 = 'left4';
    const INSTR_RIGHT1 = 'right1';
    const INSTR_RIGHT2 = 'right2';
    const INSTR_RIGHT3 = 'right3';
    const INSTR_RIGHT4 = 'right4';
    const INSTR_KEEP_LEFT = 'keep-left';
    const INSTR_KEEP_RIGHT = 'keep-right';
    const INSTR_UTURN_LEFT = 'u-turn-left';
    const INSTR_UTURN_RIGHT = 'u-turn-right';
    const INSTR_FINISH = 'finish';
    const INSTR_VIA = 'via';
    const INSTR_ROUNDABOUT = 'roundabout';

    protected string $timeField = 'time';
    protected string $timeScale = 'ms';

    abstract public function getRoute($requestParams, $configuration) : array;

    abstract public function processResponse($response, $configuration);

    protected function createFeatureCollection($coordinates, $type, $srid): array
    {
        $feature = $this->createFeature($coordinates, $type);
        return [
            'type' => 'FeatureCollection',
            'crs' => [
                'type' => 'name',
                'properties' => [
                    'name' => 'urn:ogc:def:crs:EPSG::' . $srid,
                ],
            ],
            'features' => [$feature],
        ];
    }

    protected function createFeature($coordinates, $type): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => $type,
                'coordinates' => $coordinates,
            ],
            # implement properties if needed:
            /*
            'properties' => [
                'key' => 'value',
            ],
            */
        ];
    }

    protected function getRouteInfo($start, $destination, $distance, $time, $infoText): string
    {
        $search = ['{start}', '{destination}', '{length}', '{time}'];
        $replace = [$start, $destination, $this->formatDistance($distance), $this->formatTime($time)];
        return str_replace($search, $replace, $infoText);
    }

    protected function getRoutingInstructions($steps): array
    {
        return $this->addInstructionSymbols($this->translateInstructions($steps));
    }

    protected function formatTime($time): string
    {
        $time = gmdate('H:i', $time);
        if (substr($time, 0, 2) == '00') {
            $minutes = substr($time, 3, strlen($time));
            return (substr($minutes, 0, 1) == '0') ? substr($minutes, 1, 1) . ' Min' : $minutes . ' Min';
        }
        return $time . ' Std.';
    }

    protected function formatDistance($distance): string
    {
        if (floatval($distance) >= 1000) {
            return round((floatval($distance) / 1000.0), 2) . ' KM';
        }
        return $distance . 'm';
    }

    /**
     * @return array
     */
    protected function getInstructionSignMapping(): array
    {
        return array(
            8 => static::INSTR_UTURN_RIGHT, // gh: U_TURN_RIGHT
            7 => static::INSTR_KEEP_RIGHT,  // gh: KEEP_RIGHT
            3 => static::INSTR_RIGHT3,      // gh: TURN_SHARP_RIGHT
            2 => static::INSTR_RIGHT2,      // gh: TURN_RIGHT
            1 => static::INSTR_RIGHT1,      // gh: TURN_SLIGHT_RIGHT
            0 => static::INSTR_CONTINUE,    // gh: CONTINUE_ON_STREET
            -1 => static::INSTR_LEFT1,       // gh: TURN_SLIGHT_LEFT
            -2 => static::INSTR_LEFT2,       // gh: TURN_LEFT
            -3 => static::INSTR_LEFT3,       // gh: TURN_SHARP_LEFT
            -7 => static::INSTR_KEEP_LEFT,   // gh: KEEP_LEFT
            -8 => static::INSTR_UTURN_LEFT,  // gh: U_TURN_LEFT

            4 => static::INSTR_FINISH,      // gh: FINISH
            5 => static::INSTR_VIA,         // gh: REACHED_VIA
            6 => static::INSTR_ROUNDABOUT,  // gh: USE_ROUNDABOUT
        );
    }


    /**
     * Adds urls to appropriate icons to given instructions.
     * See constants defined in @param array[] $instructions
     * @return array[]
     * @see IDriverInterface
     *
     */
    protected function addInstructionSymbols(array $instructions)
    {
        $instructionsOut = array();
        $iconMap = array(
            static::INSTR_LEFT1 => 'slight_left.png',
            static::INSTR_LEFT2 => 'left.png',
            static::INSTR_LEFT3 => 'sharp_left.png',
            static::INSTR_KEEP_LEFT => 'keep_left.png',
            static::INSTR_UTURN_LEFT => 'u_turn_left.png',
            static::INSTR_RIGHT1 => 'slight_right.png',
            static::INSTR_RIGHT2 => 'right.png',
            static::INSTR_RIGHT3 => 'sharp_right.png',
            static::INSTR_KEEP_RIGHT => 'keep_right.png',
            static::INSTR_UTURN_RIGHT => 'u_turn_right.png',
            static::INSTR_CONTINUE => 'continue.png',
            static::INSTR_FINISH => 'destination.png',
            static::INSTR_ROUNDABOUT => 'roundabout.png',
        );
        foreach ($instructions as $instruction) {
            $icon = ArrayUtil::getDefault($iconMap, $instruction['action'], null);
            if ($icon) {
                $instruction += array(
                    'icon' => "../../bundles/mapbenderrouting/image/{$icon}",
                );
            }
            $instructionsOut[] = $instruction;
        }
        return $instructionsOut;
    }

    protected function calculateDuration($instruction): ?string
    {
        if ($instruction[$this->timeField] != null) {
            if ($this->timeScale == "ms") {
                $seconds = floor($instruction[$this->timeField] / 1000);
            } else {
                $seconds = $instruction[$this->timeField];
            }
            $minutes = floor($seconds / 60);
            $rest_seconds = $seconds % 60;
            $min = str_pad($minutes, 2, '0', STR_PAD_LEFT);
            $sec = str_pad($rest_seconds, 2, '0', STR_PAD_LEFT);
            return  $min.':'.$sec. ' min';
        } else {
           return null;
        }
    }

    protected function getInstructionAction($instruction) {
        $signMapping = $this->getInstructionSignMapping();
        return ArrayUtil::getDefault($signMapping, $this->getInstructionSign($instruction), null);
    }

    protected function getInstructionSign($instruction) {
        return $instruction['sign'];
    }

    protected function getInstructionText($instruction) {
        return $instruction['text'];
    }

    /**
     * @param array[] $nativeInstructions
     * @return array[]
     */
    public function translateInstructions(array $nativeInstructions): array
    {
        $instructionsOut = [];

        foreach ($nativeInstructions as $nativeInstruction) {
            $distanceOnLeg = ($nativeInstruction['distance'] < 1000) ? round($nativeInstruction['distance']) . 'm'
                : round($nativeInstruction['distance'] / 1000, 3) . 'km';

            $minutesOnLeg = $this->calculateDuration($nativeInstruction);
            // TODO check if & why this is necessary
            if (!$minutesOnLeg) {
                continue;
            }
            if (!next($nativeInstructions)) {
                $distanceOnLeg = $minutesOnLeg = '';
            }
            $instruction = [
                'action' => $this->getInstructionAction($nativeInstruction),
                'metersOnLeg' => $distanceOnLeg,
                'secondsOnLeg' => $minutesOnLeg,
                'text' => $this->getInstructionText($nativeInstruction),
            ];
            $instruction['leg'] = $nativeInstruction['interval'] ?? null;
            $instructionsOut[] = $instruction;
        }

        return $instructionsOut;
    }
}
