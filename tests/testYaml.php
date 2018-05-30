<?php

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

class _yaml
{

    var $builder = [], $names = [],
        $lasttab = 0;

    // place atr into last inserted level
    function placeatr($lvl, $tag, $atr = '')
    {
        $this->rebuild($lvl);
        while (!isset($this->builder[$lvl])) {
            $this->builder[] = [];
            $this->names[] = '';
        }
        $this->names[$lvl] = $tag;
        $this->builder[$lvl] = array_merge_recursive($this->builder[$lvl], [$tag => $atr]);
        $this->lasttab = $lvl;
    }

    /**
     * @param $lvl
     */
    function rebuild($lvl)
    {
        while ($lvl < $this->lasttab) {
            $val = $this->builder[$this->lasttab];
            $this->builder[$this->lasttab] = [];
            $this->lasttab--;
            $name = $this->names[$this->lasttab];
            if (empty($this->builder[$this->lasttab][$name])) {
                $this->builder[$this->lasttab][$name] = $val;
            } else {
                if (is_string($this->builder[$this->lasttab][$name])) {
                    $this->builder[$this->lasttab][$name] = [$this->builder[$this->lasttab][$name]];
                }
                $this->builder[$this->lasttab] = array_merge_recursive(
                    $this->builder[$this->lasttab],
                    [$name => $val]
                );
            }
        }
    }

    function toarray()
    {
        $this->rebuild(0);
        return $this->builder[0];
    }

}

class testYaml extends TestCase
{

    // читалка ES ямла (не путать с ямлом)
    public function testESyaml0()
    {
        $xxx = <<<PTRN0
ship "Kar Ik Vot 349"
	name 55-199.1
# комментарий
	sprite "ship/kar ik vot 349"
	attributes
		category "Heavy Warship"
		cost 41280000
		automaton 1
		"cargo space" 87
		drag 16.8
		"engine capacity" 173
		"fuel capacity" 400
		"gun ports" 4
		"heat dissipation" 0.5
		hull 65400
		mass 1350
		"outfit space" 1054
		ramscoop 3
		"self destruct" 0.8
		shields 57200
		"turret mounts" 8
		"weapon capacity" 447
	outfits
		"Cooling Module" 9
		Hyperdrive
		"Outfits Expansion" 4
		"Jump Drive"
		"Quantum Keystone"
		"Fuel Pod" 4
		"Double Plasma Core"
		"Triple Plasma Core"
		"Large Heat Shunt"
		"Systems Core (Large)"
		"Control Transceiver"
		"Thruster (Planetary Class)"
		"Steering (Stellar Class)"
		"Korath Detainer" 2
		"Wanderer Ramscoop"
		Sunbeam
		"Dual Sunbeam Turret" 3
	crew 0
	fuel 800
	shields 57200
	hull 65400
	position -272.71958 -235.53284
	engine -24 237 1
	engine 24 237 1
	gun -8 -212 "Korath Detainer"
	gun 8 -212 "Korath Detainer"
	gun -16 -158 Sunbeam
	gun 16 -158
	turret -37 -150 "Dual Sunbeam Turret"
	turret 37 -150 "Dual Sunbeam Turret"
	turret -38 -132 "Dual Sunbeam Turret"
	turret 38 -132
	turret -40 -111
	turret 40 -111
	turret -102 188
	turret 102 188
	explode "tiny explosion" 120
	explode "small explosion" 60
	explode "medium explosion" 70
	explode "large explosion" 50
	explode "huge explosion" 15
	"final explode" "final explosion large" 1
	system Sevrelect
	planet "Setar Fort"
PTRN0;
        $scaner = new Ksnk\scaner\scaner();
        $yaml = new _yaml();
        $scaner
            ->newbuf($xxx)
            ->syntax([
                'tabs' => '\t*',
                'line' => ':komment:|:tag::atr:',
                'tag' => '\w+|"[^"]+"',
                'atr' => '[^\R]*?',
                'komment' => '\#[^\R]*?'
            ], '/^:tabs::line:$/m', function ($line) use (&$yaml) {
                if ('' != trim($line['_skiped'])) return false;
                if (!empty($line['komment'])) return true;
                $yaml->placeatr(strlen($line['tabs']), $line['tag'], $line['atr']);
                return true;
            });
        $this->assertEquals($yaml->toarray(), [
            'ship' => ['"Kar Ik Vot 349"'
                , 'name' => '55-199.1'
                , 'sprite' => '"ship/kar ik vot 349"'
                , 'attributes' => ['category' => '"Heavy Warship"'
                    , 'cost' => '41280000'
                    , 'automaton' => '1'
                    , '"cargo space"' => '87'
                    , 'drag' => '16.8'
                    , '"engine capacity"' => '173'
                    , '"fuel capacity"' => '400'
                    , '"gun ports"' => '4'
                    , '"heat dissipation"' => '0.5'
                    , 'hull' => '65400'
                    , 'mass' => '1350'
                    , '"outfit space"' => '1054'
                    , 'ramscoop' => '3'
                    , '"self destruct"' => '0.8'
                    , 'shields' => '57200'
                    , '"turret mounts"' => '8'
                    , '"weapon capacity"' => '447'
                ]
                , 'outfits' => ['"Cooling Module"' => '9'
                    , 'Hyperdrive' => ''
                    , '"Outfits Expansion"' => '4'
                    , '"Jump Drive"' => ''
                    , '"Quantum Keystone"' => ''
                    , '"Fuel Pod"' => '4'
                    , '"Double Plasma Core"' => ''
                    , '"Triple Plasma Core"' => ''
                    , '"Large Heat Shunt"' => ''
                    , '"Systems Core (Large)"' => ''
                    , '"Control Transceiver"' => ''
                    , '"Thruster (Planetary Class)"' => ''
                    , '"Steering (Stellar Class)"' => ''
                    , '"Korath Detainer"' => '2'
                    , '"Wanderer Ramscoop"' => ''
                    , 'Sunbeam' => ''
                    , '"Dual Sunbeam Turret"' => '3'
                ]
                , 'crew' => '0'
                , 'fuel' => '800'
                , 'shields' => '57200'
                , 'hull' => '65400'
                , 'position' => '-272.71958 -235.53284'
                , 'engine' => ['-24 237 1', '24 237 1']
                , 'gun' => ['-8 -212 "Korath Detainer"', '8 -212 "Korath Detainer"', '-16 -158 Sunbeam', '16 -158']
                , 'turret' => ['-37 -150 "Dual Sunbeam Turret"', '37 -150 "Dual Sunbeam Turret"', '-38 -132 "Dual Sunbeam Turret"', '38 -132', '-40 -111', '40 -111', '-102 188', '102 188']
                , 'explode' => ['"tiny explosion" 120', '"small explosion" 60', '"medium explosion" 70', '"large explosion" 50', '"huge explosion" 15']
                , '"final explode"' => '"final explosion large" 1'
                , 'system' => 'Sevrelect'
                , 'planet' => '"Setar Fort"'
            ]]);
    }

}
