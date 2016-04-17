<?php
/**
 * @copyright Copyright (C) 2016 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@bouhime.com>
 */

namespace app\components\ability\effect;

class v020700 extends v020600
{
    public function getCalculatorVersion()
    {
        return '2.7.0';
    }

    public function getSwimSpeedPct()
    {
        $x = $this->calcX('swim_speed_up', 120);
        if ($x === null) {
            return null;
        }
        $w = $this->getSwimSpeedBase() ?? 1;
        return (1 + $x) * $w;
    }

    public function getSpecialLossPct()
    {
        $x = $this->calcX('special_saver', 60);
        if ($x === null) {
            return null;
        }
        $w = $this->getSpecialLossPctBase();
        if ($w === null) {
            return null;
        }
        return max($w - $x, 0);
    }

    public function getMarkingPct()
    {
        $x = $this->calcX('cold_blooded', 1);
        if ($x === null) {
            return null;
        }
        if ($x > 0) {
            return 0.50;
        }
        return 1.00;
    }

    protected function getSpecialDurationDefaultSec()
    {
        switch ($this->battle->weapon->special->key ?? null) {
            case null:
                return null;
            case 'barrier':
                return 5;
            case 'supersensor':
                return 9;
            default:
                return 6;
        }
    }

    protected function getSpecialLossPctBase()
    {
        switch ($this->battle->weapon->key ?? null) {
            case null:
                return null;

            case '96gal_deco':
            case 'dynamo':
            case 'dynamo_tesla':
            case 'herocharger_replica':
            case 'nova_neo':
            case 'octoshooter_replica':
            case 'splatcharger':
            case 'splatscope':
            case 'sshooter_collabo':
                return 0.75;

            case 'wakaba':
            case '52gal':
            case 'nova':
            case 'hotblaster_custom':
            case 'l3reelgun_d':
            case 'carbon':
            case 'splatroller_collabo':
            case 'hissen':
            case 'splatcharger_wakame':
            case 'splatscope_wakame':
            case 'liter3k':
            case 'liter3k_custom':
            case 'liter3k_scope':
            case 'liter3k_scope_custom':
            case 'splatspinner_collabo':
            case 'barrelspinner_deco':
                return 0.60;

            default:
                return 0.40;
        }
    }

    protected function getSwimSpeedBase()
    {
        switch ($this->battle->weapon->key ?? null) {
            case null:
                return null;

            case 'dynamo':
            case 'dynamo_burned':
            case 'dynamo_tesla':
            case 'hydra':
            case 'hydra_custom':
            case 'liter3k':
            case 'liter3k_custom':
            case 'liter3k_scope':
            case 'liter3k_scope_custom':
                return 0.9;

            default:
                return 1.0;
        }
    }
}
