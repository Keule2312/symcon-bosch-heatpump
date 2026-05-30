<?php

declare(strict_types=1);

class BoschHeatpump extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('TopicPrefix', 'ems-esp');
        $this->RegisterPropertyBoolean('EnableBoiler', true);
        $this->RegisterPropertyBoolean('EnableThermostat', true);
        $this->RegisterPropertyBoolean('EnableEnergy', true);
        $this->RegisterPropertyBoolean('EnableDashboard', false);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
    }

    public function Destroy()
    {
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
    }
}
