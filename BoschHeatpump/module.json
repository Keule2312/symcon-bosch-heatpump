<?php

declare(strict_types=1);

class BoschHeatpump extends IPSModuleStrict
{
    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyString('TopicPrefix', 'ems-esp');
        $this->RegisterPropertyBoolean('EnableBoiler', true);
        $this->RegisterPropertyBoolean('EnableThermostat', true);
        $this->RegisterPropertyBoolean('EnableEnergy', true);
        $this->RegisterPropertyBoolean('EnableDashboard', false);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        $this->ConnectParent('{82E1EEC2-2CD1-CB3D-2B22-B2851CCB6B02}');
        $this->RegisterTimer('UpdateTimer', 0, 'BHP_RequestUpdate($_IPS[\'TARGET\']);');
    }

    public function Destroy(): void
    {
        parent::Destroy();
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $prefix = $this->ReadPropertyString('TopicPrefix');
        $filters = [];
        if ($this->ReadPropertyBoolean('EnableBoiler'))     $filters[] = preg_quote($prefix . '/boiler', '/');
        if ($this->ReadPropertyBoolean('EnableThermostat')) $filters[] = preg_quote($prefix . '/thermostat', '/');
        if (!empty($filters)) {
            $this->SetReceiveDataFilter('/.*(' . implode('|', $filters) . ').*/');
        }

        $this->RegisterProfiles();

        if ($this->ReadPropertyBoolean('EnableBoiler'))     $this->CreateAllVariables(self::BOILER_ENTITIES,     'Kessel & Waermepumpe');
        if ($this->ReadPropertyBoolean('EnableThermostat')) $this->CreateAllVariables(self::THERMOSTAT_ENTITIES, 'Thermostat & Bedienung');

        $interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('UpdateTimer', $interval > 0 ? $interval * 1000 : 0);

        $this->RegisterWebHook();
    }

    public function ReceiveData(string $JSONString): void
    {
        $data = json_decode($JSONString, true);
        if (!isset($data['Topic'], $data['Payload'])) return;
        $payload = json_decode($data['Payload'], true);
        if (!is_array($payload)) return;
        $prefix = $this->ReadPropertyString('TopicPrefix');
        if ($data['Topic'] === $prefix . '/boiler')     $this->ProcessData($payload, self::BOILER_ENTITIES);
        if ($data['Topic'] === $prefix . '/thermostat') $this->ProcessData($payload, self::THERMOSTAT_ENTITIES);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $device = null;
        $emsKey = null;
        if (isset(self::BOILER_ENTITIES[$Ident]) && self::BOILER_ENTITIES[$Ident][3]) {
            $device = 'boiler';
            $emsKey = self::BOILER_ENTITIES[$Ident][4];
        } elseif (isset(self::THERMOSTAT_ENTITIES[$Ident]) && self::THERMOSTAT_ENTITIES[$Ident][3]) {
            $device = 'thermostat';
            $emsKey = self::THERMOSTAT_ENTITIES[$Ident][4];
        }
        if (!$device || !$emsKey) throw new Exception("Kein schreibbarer EMS-Key fuer: $Ident");
        $this->SendMQTT($device, $emsKey, $Value);
        $type = self::BOILER_ENTITIES[$Ident][1] ?? self::THERMOSTAT_ENTITIES[$Ident][1] ?? VARIABLETYPE_STRING;
        $this->SetVar($Ident, $Value, $type);
    }

    public function ProcessHookData(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_ends_with($uri, '/data')) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode($this->GetAllValues());
            return;
        }
        $html = __DIR__ . '/dashboard.html';
        if (file_exists($html)) {
            header('Content-Type: text/html; charset=utf-8');
            $content = file_get_contents($html);
            $content = str_replace(
                'var IPS_ID = parseInt(location.pathname.split(\'/\').pop()) || 0;',
                'var IPS_ID = ' . $this->InstanceID . ';',
                $content
            );
            echo $content;
        } else {
            http_response_code(404);
            echo 'Dashboard nicht gefunden.';
        }
    }

    // =========================================================================
    // Boiler Entitaeten
    // =========================================================================
    const BOILER_ENTITIES = [
        'B_HeizenAbschalten'        => ['Heizen abschalten',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'heatingoff',          'Status'],
        'B_HeizenAktiv'             => ['Heizen aktiv',                         VARIABLETYPE_BOOLEAN, '~Switch',           false, 'heatingactive',       'Status'],
        'B_WarmwasserAktiv'         => ['Warmwasser aktiv',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'tapwateractive',      'Status'],
        'B_HeizbetriebAktiviert'    => ['Heizbetrieb aktiviert',               VARIABLETYPE_BOOLEAN, '~Switch',           true,  'heatingactivated',    'Status'],
        'B_Notbetrieb'              => ['Notbetrieb',                           VARIABLETYPE_BOOLEAN, '~Switch',           true,  'emergencyop',         'Status'],
        'B_Heizungspumpe'           => ['Heizungspumpe',                        VARIABLETYPE_BOOLEAN, '~Switch',           false, 'heatingpump',         'Status'],
        'B_KompressorAktiv'         => ['WP Kompressor aktiv',                  VARIABLETYPE_BOOLEAN, '~Switch',           false, 'hpCompOn',            'Status'],
        'B_KompressorAktivitaet'    => ['Kompressoraktivitaet',                 VARIABLETYPE_STRING,  '',                  false, 'hpActivity',          'Status'],
        'B_VierWegeVentil'          => ['4-Wege-Ventil (VR4)',                  VARIABLETYPE_STRING,  '',                  false, 'hpVr4',               'Status'],
        'B_Zusatzheizerstatus'      => ['Zusatzheizerstatus',                   VARIABLETYPE_STRING,  '',                  false, 'auxStatus',           'Status'],
        'B_Zusatzheizer'            => ['Zusatzheizer',                         VARIABLETYPE_BOOLEAN, '~Switch',           false, 'auxHeater',           'Status'],
        'B_MischventilZusatz'       => ['Mischventil Zusatzheizer',             VARIABLETYPE_INTEGER, '',                  false, 'auxMixerValve',       'Status'],
        'B_ModulationHeizpumpe'     => ['Modulation Heizungspumpe',             VARIABLETYPE_INTEGER, 'BHP.Percent',       false, 'pumpMod',             'Status'],
        'B_Aussentemperatur'        => ['Aussentemperatur',                     VARIABLETYPE_FLOAT,   '~Temperature',      false, 'outdoortemp',         'Temperaturen'],
        'B_VorlaufAktuell'          => ['Aktuelle Vorlauftemperatur',           VARIABLETYPE_FLOAT,   '~Temperature',      false, 'curflowtemp',         'Temperaturen'],
        'B_Ruecklauftemperatur'     => ['Ruecklauftemperatur',                  VARIABLETYPE_FLOAT,   '~Temperature',      false, 'rettemp',             'Temperaturen'],
        'B_HydrWeiche'              => ['Hydr. Weiche',                         VARIABLETYPE_FLOAT,   '~Temperature',      false, 'mixertemp',           'Temperaturen'],
        'B_VorlaufGewaehlt'         => ['Gewaehlte Vorlauftemperatur',          VARIABLETYPE_FLOAT,   '~Temperature',      true,  'selflowtemp',         'Temperaturen'],
        'B_Heiztemperatur'          => ['Heiztemperatur (Sollwert)',            VARIABLETYPE_FLOAT,   '~Temperature',      true,  'heattemp',            'Temperaturen'],
        'B_Notfalltemperatur'       => ['Notfalltemperatur',                    VARIABLETYPE_FLOAT,   '~Temperature',      true,  'emergencytemp',       'Temperaturen'],
        'B_Systemdruck'             => ['Systemdruck',                          VARIABLETYPE_FLOAT,   'BHP.Bar',           false, 'syspressure',         'Temperaturen'],
        'B_TC0'                     => ['Kaeltemittelruecklauf (TC0)',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tc0',                 'Kaeltekreis'],
        'B_TC1'                     => ['Kaeltemittelvorlauf (TC1)',            VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tc1',                 'Kaeltekreis'],
        'B_TC3'                     => ['Kondensatortemperatur (TC3)',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tc3',                 'Kaeltekreis'],
        'B_TR1'                     => ['Kompressortemperatur (TR1)',           VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr1',                 'Kaeltekreis'],
        'B_TR3'                     => ['Kaeltemittel fluessig (TR3)',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr3',                 'Kaeltekreis'],
        'B_TR4'                     => ['Verdampfereingang (TR4)',              VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr4',                 'Kaeltekreis'],
        'B_TR5'                     => ['Kompressoreingang (TR5)',              VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr5',                 'Kaeltekreis'],
        'B_TR6'                     => ['Kompressorausgang (TR6)',              VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr6',                 'Kaeltekreis'],
        'B_TR7'                     => ['Kaeltemittel gasfoemig (TR7)',         VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tr7',                 'Kaeltekreis'],
        'B_TL2'                     => ['Aussenlufteintritt (TL2)',             VARIABLETYPE_FLOAT,   '~Temperature',      false, 'tl2',                 'Kaeltekreis'],
        'B_PL1'                     => ['Niederdrucktemperatur (PL1)',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'pl1',                 'Kaeltekreis'],
        'B_PH1'                     => ['Hochdrucktemperatur (PH1)',            VARIABLETYPE_FLOAT,   '~Temperature',      false, 'ph1',                 'Kaeltekreis'],
        'B_TA4'                     => ['Kondensatorwanne (TA4)',               VARIABLETYPE_FLOAT,   '~Temperature',      false, 'ta4',                 'Kaeltekreis'],
        'B_SoleIn'                  => ['Sole in / Verdampfer',                 VARIABLETYPE_FLOAT,   '~Temperature',      false, 'hpSourceIn',          'Kaeltekreis'],
        'B_SoleAus'                 => ['Sole aus / Kondensator',               VARIABLETYPE_FLOAT,   '~Temperature',      false, 'hpSourceOut',         'Kaeltekreis'],
        'B_Kompressordrehzahl'      => ['Kompressordrehzahl',                   VARIABLETYPE_INTEGER, 'BHP.RPM',           false, 'hpCompSpd',           'Kompressor'],
        'B_Kompressorleistung'      => ['Kompressorleistung',                   VARIABLETYPE_FLOAT,   'BHP.kW',            false, 'hpPower',             'Kompressor'],
        'B_AktKompressorleistung'   => ['Akt. Kompressorleistung',              VARIABLETYPE_INTEGER, 'BHP.Watt',          false, 'hpCompPower',         'Kompressor'],
        'B_KompressorStarts'        => ['Gesamtkompressorstarts',               VARIABLETYPE_INTEGER, '',                  false, 'hpStarts',            'Kompressor'],
        'B_Solepumpendrehzahl'      => ['Solepumpendrehzahl',                   VARIABLETYPE_INTEGER, 'BHP.Percent',       false, 'hpSourcePumpSpd',     'Kompressor'],
        'B_Zirkpumpendrehzahl'      => ['Zirkulationspumpendrehzahl',           VARIABLETYPE_INTEGER, 'BHP.Percent',       false, 'circPumpSpd',         'Kompressor'],
        'B_Pumpensolldruck'         => ['Pumpensolldruck',                      VARIABLETYPE_INTEGER, 'BHP.mbar',          true,  'pumpsetpressure',     'Kompressor'],
        'B_Schaltventil'            => ['Schaltventil',                         VARIABLETYPE_BOOLEAN, '~Switch',           false, 'switchvalve',         'Kompressor'],
        'B_Durchfluss0'             => ['Durchfluss PC0',                       VARIABLETYPE_FLOAT,   'BHP.lh',            false, 'pc0flowrate',         'Kompressor'],
        'B_Durchfluss1'             => ['Durchfluss PC1',                       VARIABLETYPE_FLOAT,   'BHP.lh',            false, 'pc1flowrate',         'Kompressor'],
        'B_AnlageGesamtlaufzeit'    => ['Anlagengesamtlaufzeit',                VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'ubaUptime',           'Betriebszeiten'],
        'B_WPGesamtbetriebszeit'    => ['Gesamtbetriebszeit WP',               VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'hpTotalCompTime',     'Betriebszeiten'],
        'B_BetriebszeitHeizen'      => ['Betriebszeit Heizen',                  VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'hpHeatTime',          'Betriebszeiten'],
        'B_BetriebszeitKompHeizen'  => ['Betriebszeit Kompressor Heizen',       VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'hpCompHeatTime',      'Betriebszeiten'],
        'B_BetriebszeitKompKuehlen' => ['Betriebszeit Kompressor Kuehlen',      VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'hpCompCoolTime',      'Betriebszeiten'],
        'B_BetriebszeitKompWWK'     => ['WWK Betriebszeit Kompressor',          VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'hpDhwTime',           'Betriebszeiten'],
        'B_HeizbetriebStarts'       => ['Heizungsregelungstarts',               VARIABLETYPE_INTEGER, '',                  false, 'hpHeatStarts',        'Betriebszeiten'],
        'B_KuehlbetriebStarts'      => ['Kuehlregelungstarts',                  VARIABLETYPE_INTEGER, '',                  false, 'hpCoolStarts',        'Betriebszeiten'],
        'B_WWKStartsWP'             => ['WWK Anzahl Starts WP',                 VARIABLETYPE_INTEGER, '',                  false, 'hpDhwStarts',         'Betriebszeiten'],
        'B_BrennerStarts'           => ['Brennerstarts',                        VARIABLETYPE_INTEGER, '',                  false, 'burnStarts',          'Betriebszeiten'],
        'B_BrennerLaufzeit'         => ['Brennerlaufzeit',                      VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'burnWorkMin',         'Betriebszeiten'],
        'B_HeizlaufzeitMin'         => ['Heizlaufzeit',                         VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'heatWorkMin',         'Betriebszeiten'],
        'B_GesamtEnergie'           => ['Gesamtenergie (Waerme)',               VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgTotal',            'Energie Abgabe'],
        'B_WWKEnergie'              => ['WWK Energie (Waerme)',                  VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgWw',               'Energie Abgabe'],
        'B_HeizenEnergie'           => ['Energie Heizen',                       VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgHeat',             'Energie Abgabe'],
        'B_KuehlenEnergie'          => ['Energie Kuehlen',                      VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgCool',             'Energie Abgabe'],
        'B_GesamtVerbrauch'         => ['Gesamtverbrauch (Strom)',              VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumTotal',      'Energie Verbrauch'],
        'B_VerbrauchKompressor'     => ['Verbrauch Kompressor',                 VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumComp',       'Energie Verbrauch'],
        'B_VerbrauchEHeizer'        => ['Verbrauch E-Heizstab',                VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumEHeat',      'Energie Verbrauch'],
        'B_VerbrauchKompHeizen'     => ['Verbrauch Kompressor Heizen',          VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumHeat',       'Energie Verbrauch'],
        'B_VerbrauchKompKuehlen'    => ['Verbrauch Kompressor Kuehlen',         VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumCool',       'Energie Verbrauch'],
        'B_VerbrauchKompWWK'        => ['WWK Verbrauch Kompressor',             VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumWwComp',     'Energie Verbrauch'],
        'B_VerbrauchEHeizerGesamt'  => ['Verbrauch E-Heizstab gesamt',         VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumEHeatTotal', 'Energie Verbrauch'],
        'B_VerbrauchEHeizerWWK'     => ['WWK Verbrauch E-Heizstab',            VARIABLETYPE_FLOAT,   'BHP.kWh',           false, 'nrgConsumWwEHeat',    'Energie Verbrauch'],
        'B_COP'                     => ['COP (berechnet)',                      VARIABLETYPE_FLOAT,   'BHP.COP',           false, '',                    'Energie Verbrauch'],
        'B_WWKInternTemp'           => ['WWK interne Temperatur',               VARIABLETYPE_FLOAT,   '~Temperature',      false, 'wWCurTmp',            'Warmwasser'],
        'B_WWKExternTemp'           => ['WWK externe Temperatur (TW1)',         VARIABLETYPE_FLOAT,   '~Temperature',      false, 'wWCurTmp2',           'Warmwasser'],
        'B_WWKSolltemperatur'       => ['WWK Solltemperatur',                   VARIABLETYPE_FLOAT,   '~Temperature',      false, 'wWSetTmp',            'Warmwasser'],
        'B_WWKGewaehltTemp'         => ['WWK gewaehlte Temperatur',             VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWSelTemp',           'Warmwasser'],
        'B_WWKUntereTemp'           => ['WWK untere Temperatur',                VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWSelLowerTemp',      'Warmwasser'],
        'B_WWKECO_PlusTemp'         => ['WWK ECO+ Temperatur',                  VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWSelEcoTemp',        'Warmwasser'],
        'B_WWKEinmalladungTemp'     => ['WWK Einmalladungstemperatur',          VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWOnceTmp',           'Warmwasser'],
        'B_WWKDesinfektionTemp'     => ['WWK Desinfektionstemperatur',          VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWDisinfectTemp',     'Warmwasser'],
        'B_WWKDurchfluss'           => ['WWK aktueller Durchfluss',             VARIABLETYPE_FLOAT,   'BHP.lmin',          false, 'wWFlow',              'Warmwasser'],
        'B_WWKAktiviert'            => ['WWK aktiviert',                        VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWActivated',         'Warmwasser'],
        'B_WWKEinmalladung'         => ['WWK Einmalladung',                     VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWOneTime',           'Warmwasser'],
        'B_WWKDesinfizieren'        => ['WWK Desinfizieren',                    VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWDisinfecting',      'Warmwasser'],
        'B_WWKLaden'                => ['WWK Laden',                            VARIABLETYPE_BOOLEAN, '~Switch',           false, 'wWCharging',          'Warmwasser'],
        'B_WWKNachladen'            => ['WWK Nachladen',                        VARIABLETYPE_BOOLEAN, '~Switch',           false, 'wWRecharge',          'Warmwasser'],
        'B_WWKTempOK'               => ['WWK Temperatur ok',                    VARIABLETYPE_BOOLEAN, '~Switch',           false, 'wWTempOK',            'Warmwasser'],
        'B_WWKDreiWegeAktiv'        => ['WWK 3-Wege-Ventil aktiv',              VARIABLETYPE_BOOLEAN, '~Switch',           false, 'wW3wayValve',         'Warmwasser'],
        'B_WWKAnzahlStarts'         => ['WWK Anzahl Starts',                    VARIABLETYPE_INTEGER, '',                  false, 'wWStarts',            'Warmwasser'],
        'B_WWKAktiveZeit'           => ['WWK aktive Zeit',                      VARIABLETYPE_FLOAT,   'BHP.Hours',         false, 'wWWorkM',             'Warmwasser'],
        'B_WWKZirkPumpe'            => ['WWK Zirkulationspumpe vorhanden',      VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWCircPump',          'Warmwasser'],
        'B_WWKZirkAktiv'            => ['WWK Zirkulation aktiv',                VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWCirc',              'Warmwasser'],
        'B_WWKZirkModus'            => ['WWK Zirkulationspumpenmodus',          VARIABLETYPE_INTEGER, 'BHP.CircMode',      true,  'wWCircMode',          'Warmwasser'],
        'B_WWKWechselbetrieb'       => ['WWK Wechselbetrieb',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wWAltOp',             'Warmwasser'],
        'B_WWKHeizVorWW'            => ['WWK Heizen bevorzugt vor WW',         VARIABLETYPE_INTEGER, 'BHP.Minutes',       true,  'wWAltOpPrioHeat',     'Warmwasser'],
        'B_WWKVorHeizen'            => ['WWK bevorzugt vor Heizen',             VARIABLETYPE_INTEGER, 'BHP.Minutes',       true,  'wWAltOpPrioDhw',      'Warmwasser'],
        'B_WWKKomfortModus'         => ['WWK Komfort-Modus',                    VARIABLETYPE_INTEGER, 'BHP.WWKComfort',    true,  'wWComfort',           'Warmwasser'],
        'B_WWKAubheizVorlauf'       => ['WWK Anhebung Vorlauftemperatur',       VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWFlowTempBoost',     'Warmwasser'],
        'B_WWKEcoPlus_Aus'          => ['WWK ECO+ Ausschalttemp.',              VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWEcoOffTemp',        'Warmwasser'],
        'B_WWKKomfortDiff'          => ['WWK Komfort Differenztemp.',           VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'wWComfortDiff',       'Warmwasser'],
        'B_WWKEcoDiff'              => ['WWK ECO Differenztemp.',               VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'wWEcoDiff',           'Warmwasser'],
        'B_WWKEcoPlusDiff'          => ['WWK ECO+ Differenztemp.',              VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'wWEcoEcoDiff',        'Warmwasser'],
        'B_WWKKomfortStopp'         => ['WWK Komfort Stopptemp.',               VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWComfortStopTemp',   'Warmwasser'],
        'B_WWKEcoStopp'             => ['WWK ECO Stopptemp.',                   VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWEcoStopTemp',       'Warmwasser'],
        'B_WWKEcoPlusStopp'         => ['WWK ECO+ Stopptemp.',                  VARIABLETYPE_FLOAT,   '~Temperature',      true,  'wWEcoEcoStopTemp',    'Warmwasser'],
        'B_WWKEinschaltDiff'        => ['WWK Einschalttemperaturdifferenz',     VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'wWDeltaP1',           'Warmwasser'],
        'B_WWKAusschaltDiff'        => ['WWK Ausschalttemperaturdifferenz',     VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'wWDeltaP2',           'Warmwasser'],
        'B_WWKHeizstabLimit'        => ['WWK Heizstab Limit fuer WW',          VARIABLETYPE_INTEGER, 'BHP.HeaterPower',   true,  'wWMaxPower',          'Warmwasser'],
        'B_NurZusatzheizer'         => ['Nur Zusatzheizer',                     VARIABLETYPE_BOOLEAN, '~Switch',           true,  'auxHeaterOnly',       'Zusatzheizer'],
        'B_ZusatzheiterDeaktiv'     => ['Zusatzheizer deaktivieren',            VARIABLETYPE_BOOLEAN, '~Switch',           true,  'auxHeaterOff',        'Zusatzheizer'],
        'B_ZusatzVerzoegert'        => ['Zusatzheizer verzoegert ein',          VARIABLETYPE_INTEGER, 'BHP.Kmin',          true,  'auxDelayTime',        'Zusatzheizer'],
        'B_ZusatzMaxGrenze'         => ['Zusatzheizer max. Grenze',             VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'auxLimit',            'Zusatzheizer'],
        'B_ZusatzGrenzeStart'       => ['Zusatzheizer Grenze Start',            VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'auxLimitStart',       'Zusatzheizer'],
        'B_ZusatzheizungsModus'     => ['Zusatzheizungsmodus',                  VARIABLETYPE_INTEGER, 'BHP.AuxMode',       true,  'auxMode',             'Zusatzheizer'],
        'B_HeizstabKompressor'      => ['Heizstab Limit mit Kompressor',        VARIABLETYPE_INTEGER, 'BHP.HeaterPower',   true,  'heatLimitPower2',     'Zusatzheizer'],
        'B_HeizstabLeistung'        => ['Heizstab Limit Leistung',              VARIABLETYPE_INTEGER, 'BHP.HeaterPower',   true,  'heatLimitPower',      'Zusatzheizer'],
        'B_HeizstabParallel'        => ['Heizstab Parallelbetrieb',             VARIABLETYPE_FLOAT,   '~Temperature',      true,  'auxParallelMode',     'Zusatzheizer'],
        'B_ElHeizerStufe1'          => ['El. Heizer Stufe 1',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'elHeatStep1',         'Zusatzheizer'],
        'B_ElHeizerStufe2'          => ['El. Heizer Stufe 2',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'elHeatStep2',         'Zusatzheizer'],
        'B_ElHeizerStufe3'          => ['El. Heizer Stufe 3',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'elHeatStep3',         'Zusatzheizer'],
        'B_Silentmodus'             => ['Silentmodus',                          VARIABLETYPE_INTEGER, 'BHP.SilentMode',    true,  'silentMode',          'WP Steuerung'],
        'B_SilentMinAussen'         => ['Minimale Aussentemp. Silentmodus',    VARIABLETYPE_FLOAT,   '~Temperature',      true,  'silentModeMinExt',    'WP Steuerung'],
        'B_PrimaererWPModus'        => ['Primaerer WP-Modus',                  VARIABLETYPE_INTEGER, 'BHP.PrimaryMode',   true,  'hpMode',              'WP Steuerung'],
        'B_KuehlenNurPV'            => ['Kuehlen nur mit PV',                  VARIABLETYPE_BOOLEAN, '~Switch',           true,  'pvCooling',           'WP Steuerung'],
        'B_TempDiffHeizen'          => ['Temp.diff. TC3/TC0 Heizen',           VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hpTcDiffHeat',        'WP Steuerung'],
        'B_TempDiffKuehlen'         => ['Temp.diff. TC3/TC0 Kuehlen',          VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hpTcDiffCool',        'WP Steuerung'],
        'B_VentilKuehlen'           => ['Ventil/Pumpe fuer Kuehlen',           VARIABLETYPE_BOOLEAN, '~Switch',           true,  'coolValve',           'WP Steuerung'],
        'B_Heizband'                => ['Heizband',                             VARIABLETYPE_BOOLEAN, '~Switch',           true,  'heatCable',           'WP Steuerung'],
        'B_VC0Ventil'               => ['VC0 Ventil',                           VARIABLETYPE_BOOLEAN, '~Switch',           true,  'vc0Valve',            'WP Steuerung'],
        'B_Hauptpumpe'              => ['Hauptpumpe',                           VARIABLETYPE_BOOLEAN, '~Switch',           true,  'mainPump',            'WP Steuerung'],
        'B_ModulationHauptpumpe'    => ['Modulation Hauptpumpe',                VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'mainPumpMod',         'WP Steuerung'],
        'B_DreiWegeVentil'          => ['3-Wege-Ventil',                        VARIABLETYPE_BOOLEAN, '~Switch',           true,  '3wayValve',           'WP Steuerung'],
        'B_ManuelleEnteisung'       => ['Manuelle Enteisung',                   VARIABLETYPE_BOOLEAN, '~Switch',           true,  'manDefrost',          'WP Steuerung'],
        'B_MaxKesselpumpe'          => ['Maximale Kesselpumpenleistung',        VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'pumpMaxPower',        'WP Steuerung'],
        'B_MinKesselpumpe'          => ['Minimale Kesselpumpenleistung',        VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'pumpMinPower',        'WP Steuerung'],
        'B_KesselpumpeCharakt'      => ['Charakteristik Kesselpumpe',           VARIABLETYPE_STRING,  '',                  true,  'pumpCharact',         'WP Steuerung'],
        'B_MaxBrennerLeistung'      => ['Max. Brennerleistung',                 VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'burnMaxPower',        'WP Steuerung'],
        'B_AktuelleBrennerLeist'    => ['Aktuelle Brennerleistung',             VARIABLETYPE_INTEGER, 'BHP.Percent',       false, 'curBurnPow',          'WP Steuerung'],
        'B_KondensatwanneHeizung'   => ['Heizung Kondensatwanne (EA0)',         VARIABLETYPE_BOOLEAN, '~Switch',           false, 'condensDrainHeater',  'WP Steuerung'],
        'B_StatusEingang1'          => ['Status Eingang 1',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'in1',                 'Eingaenge'],
        'B_EinstellungEingang1'     => ['Einstellung Eingang 1',                VARIABLETYPE_STRING,  '',                  true,  'in1Set',              'Eingaenge'],
        'B_StatusEingang2'          => ['Status Eingang 2',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'in2',                 'Eingaenge'],
        'B_EinstellungEingang2'     => ['Einstellung Eingang 2',                VARIABLETYPE_STRING,  '',                  true,  'in2Set',              'Eingaenge'],
        'B_StatusEingang3'          => ['Status Eingang 3',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'in3',                 'Eingaenge'],
        'B_EinstellungEingang3'     => ['Einstellung Eingang 3',                VARIABLETYPE_STRING,  '',                  true,  'in3Set',              'Eingaenge'],
        'B_StatusEingang4'          => ['Status Eingang 4',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'in4',                 'Eingaenge'],
        'B_EinstellungEingang4'     => ['Einstellung Eingang 4',                VARIABLETYPE_STRING,  '',                  true,  'in4Set',              'Eingaenge'],
    ];

    // =========================================================================
    // Thermostat Entitaeten
    // =========================================================================
    const THERMOSTAT_ENTITIES = [
        'T_DatumZeit'               => ['Datum / Zeit',                         VARIABLETYPE_STRING,  '',                  true,  'datetime',            'System'],
        'T_KorrekturInternTemp'     => ['Korrektur interne Temperatur',         VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'intTempcal',          'System'],
        'T_Estrichtrocknung'        => ['Estrichtrocknung',                     VARIABLETYPE_STRING,  '',                  false, 'screedDrying',        'System'],
        'T_EstrichtrocknungTemp'    => ['Estrichtrocknungstemperatur',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'screedTemp',          'System'],
        'T_Gebaeudetyp'             => ['Gebaeudetyp',                          VARIABLETYPE_INTEGER, 'BHP.Gebaeudetyp',   true,  'building',            'System'],
        'T_MinAussentemperatur'     => ['Min. Aussentemperatur',                VARIABLETYPE_FLOAT,   '~Temperature',      true,  'minexttemp',          'System'],
        'T_DaempfungAussen'         => ['Daempfung Aussentemperatur',           VARIABLETYPE_BOOLEAN, '~Switch',           true,  'damping',             'System'],
        'T_Solar'                   => ['Solar',                                VARIABLETYPE_BOOLEAN, '~Switch',           true,  'solar',               'System'],
        'T_EnergieKostenVerh'       => ['Energie-/Kostenverhaeltnis',           VARIABLETYPE_INTEGER, '',                  true,  'hp2000Cost',          'System'],
        'T_Abwesend'                => ['Abwesend',                             VARIABLETYPE_BOOLEAN, '~Switch',           true,  'absent',              'System'],
        'T_GedaempfteAussen'        => ['Gedaempfte Aussentemperatur',          VARIABLETYPE_FLOAT,   '~Temperature',      false, 'dampedoutdoortemp',   'System'],
        'T_WWAnhebungPV'            => ['WW-Anhebung mit PV aktivieren',        VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wwactivateheat',      'PV Integration'],
        'T_AnhebungHeizenPV'        => ['Anhebung Heizen mit PV',               VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'heatPvRaise',         'PV Integration'],
        'T_AbsenkungKuehlenPV'      => ['Absenkung Kuehlen mit PV',             VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'coolPvLower',         'PV Integration'],
        'T_HK1Betriebsart'          => ['HK1 Betriebsart',                      VARIABLETYPE_INTEGER, 'BHP.OperatingMode', true,  'hc1mode',             'HK1 Grundeinstellungen'],
        'T_HK1WPModus'              => ['HK1 WP-Modus',                         VARIABLETYPE_INTEGER, 'BHP.HPMode',        true,  'hc1hpmode',           'HK1 Grundeinstellungen'],
        'T_HK1Modustyp'             => ['HK1 Modustyp',                         VARIABLETYPE_STRING,  '',                  false, 'hc1modeType',         'HK1 Grundeinstellungen'],
        'T_HK1Steuermodus'          => ['HK1 Steuermodus',                      VARIABLETYPE_INTEGER, 'BHP.Steuermodus',   true,  'hc1controlMode',      'HK1 Grundeinstellungen'],
        'T_HK1Programm'             => ['HK1 Programm',                         VARIABLETYPE_INTEGER, 'BHP.Programm',      true,  'hc1prog',             'HK1 Grundeinstellungen'],
        'T_HK1Heizungstyp'          => ['HK1 Heizungstyp',                      VARIABLETYPE_INTEGER, 'BHP.Heizungstyp',   true,  'hc1htype',            'HK1 Grundeinstellungen'],
        'T_HK1Fernsteuerung'        => ['HK1 Fernsteuerung',                    VARIABLETYPE_STRING,  '',                  true,  'hc1remotecontrol',    'HK1 Grundeinstellungen'],
        'T_HK1Urlaubsmodus'         => ['HK1 Urlaubsmodus',                     VARIABLETYPE_BOOLEAN, '~Switch',           false, 'hc1vacation',         'HK1 Grundeinstellungen'],
        'T_HK1GewaehltRaumTemp'     => ['HK1 gewaehlte Raumtemperatur',         VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1seltemp',          'HK1 Temperaturen'],
        'T_HK1EcoTemp'              => ['HK1 eco Temperatur',                   VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1ecotemp',          'HK1 Temperaturen'],
        'T_HK1ManuelleTemp'         => ['HK1 manuelle Temperatur',              VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1manualtemp',       'HK1 Temperaturen'],
        'T_HK1Komforttemperatur'    => ['HK1 Komforttemperatur',                VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1comforttemp',      'HK1 Temperaturen'],
        'T_HK1Sommertemperatur'     => ['HK1 Sommertemperatur',                 VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1summertemp',       'HK1 Temperaturen'],
        'T_HK1Auslegungstemperatur' => ['HK1 Auslegungstemperatur',             VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1designtemp',       'HK1 Temperaturen'],
        'T_HK1Temperaturanhebung'   => ['HK1 Temperaturanhebung',               VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hc1tempadjust',       'HK1 Temperaturen'],
        'T_HK1MinVorlauf'           => ['HK1 min. Vorlauftemperatur',           VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1minflowtemp',      'HK1 Temperaturen'],
        'T_HK1MaxVorlauf'           => ['HK1 max. Vorlauftemperatur',           VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1maxflowtemp',      'HK1 Temperaturen'],
        'T_HK1BerechneteVorlauf'    => ['HK1 berechnete Vorlauftemperatur',     VARIABLETYPE_FLOAT,   '~Temperature',      false, 'hc1calcflowtemp',     'HK1 Temperaturen'],
        'T_HK1TempSollAuto'         => ['HK1 temp. Solltemperatur Automodus',   VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1tempauto',         'HK1 Temperaturen'],
        'T_HK1Kuehltemperatur'      => ['HK1 Kuehltemperatur',                  VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1cooltemp',         'HK1 Temperaturen'],
        'T_HK1WPMinVorlauf'         => ['HK1 WP minimale Vorlauftemperatur',    VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1hpminflowtemp',    'HK1 Temperaturen'],
        'T_HK1Taupunktdiff'         => ['HK1 Taupunktdifferenz',                VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hc1dewpoint',         'HK1 Temperaturen'],
        'T_HK1Raumtempdiff'         => ['HK1 Raumtemperaturdifferenz',          VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hc1roomtempdiff',     'HK1 Temperaturen'],
        'T_HK1Solareinfluß'         => ['HK1 Solareinfluss',                    VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hc1solarinfl',        'HK1 Temperaturen'],
        'T_HK1AktSolareinfluß'      => ['HK1 akt. Solareinfluss',              VARIABLETYPE_FLOAT,   '~Temperature.1',    false, 'hc1actualsolarinfl',  'HK1 Temperaturen'],
        'T_HK1Raumeinfluss'         => ['HK1 Raumeinfluss',                     VARIABLETYPE_FLOAT,   '~Temperature.1',    true,  'hc1roomInfluence',    'HK1 Raumeinfluss'],
        'T_HK1Raumeinflussfaktor'   => ['HK1 Raumeinflussfaktor',               VARIABLETYPE_INTEGER, '',                  true,  'hc1roomInfluenceFact','HK1 Raumeinfluss'],
        'T_HK1AktRaumeinfluss'      => ['HK1 aktueller Raumeinfluss',           VARIABLETYPE_FLOAT,   '~Temperature.1',    false, 'hc1currRoomInfluence','HK1 Raumeinfluss'],
        'T_HK1RaumtempRemote'       => ['HK1 Raumtemperatur Remote',            VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1remoteRoomTemp',   'HK1 Raumeinfluss'],
        'T_HK1RaumfeuchteRemote'    => ['HK1 Raumfeuchte Remote',               VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'hc1remoteHumidity',   'HK1 Raumeinfluss'],
        'T_HK1SommerEinstellung'    => ['HK1 Einstellung Sommerbetrieb',        VARIABLETYPE_INTEGER, 'BHP.SommerModus',   true,  'hc1summermode',       'HK1 Sommer/Winter'],
        'T_HK1Sommerbetrieb'        => ['HK1 Sommerbetrieb',                    VARIABLETYPE_STRING,  '',                  false, 'hc1summer',           'HK1 Sommer/Winter'],
        'T_HK1Absenkmodus'          => ['HK1 Absenkmodus',                      VARIABLETYPE_INTEGER, 'BHP.Absenkmodus',   true,  'hc1nightmode',        'HK1 Sommer/Winter'],
        'T_HK1DurchheizenUnter'     => ['HK1 Durchheizen unter',                VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1nofrosttemp',      'HK1 Sommer/Winter'],
        'T_HK1AbsenkmUnter'         => ['HK1 Absenkmodus unter',                VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1nighttemp',        'HK1 Sommer/Winter'],
        'T_HK1Absenkschwelle'       => ['HK1 Absenkschwelle',                   VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1switchofftemp',    'HK1 Sommer/Winter'],
        'T_HK1FrostschutzmModus'    => ['HK1 Frostschutzmodus',                 VARIABLETYPE_INTEGER, 'BHP.Frostmodus',    true,  'hc1frostmode',        'HK1 Sommer/Winter'],
        'T_HK1Frostschutztemp'      => ['HK1 Frostschutztemperatur',            VARIABLETYPE_FLOAT,   '~Temperature',      true,  'hc1frostprottemp',    'HK1 Sommer/Winter'],
        'T_HK1SchnellAufheizen'     => ['HK1 schnelles Aufheizen',              VARIABLETYPE_INTEGER, 'BHP.Percent',       true,  'hc1fastHeatup',       'HK1 Optimierung'],
        'T_HK1Einschaltoptimierung' => ['HK1 Einschaltoptimierung',             VARIABLETYPE_BOOLEAN, '~Switch',           true,  'hc1switchonoptim',    'HK1 Optimierung'],
        'T_HK1Boost'                => ['HK1 Boost',                            VARIABLETYPE_BOOLEAN, '~Switch',           true,  'hc1boost',            'HK1 Optimierung'],
        'T_HK1BoostDauer'           => ['HK1 Boost-Dauer',                      VARIABLETYPE_INTEGER, 'BHP.Hours',         true,  'hc1boosttime',        'HK1 Optimierung'],
        'T_HK1SchaltprogrammModus'  => ['HK1 Schaltprogrammmodus',              VARIABLETYPE_INTEGER, 'BHP.SchaltModus',   true,  'hc1switchProgMode',   'HK1 Optimierung'],
        'T_HK1WWVorrang'            => ['HK1 WW-Vorrang',                        VARIABLETYPE_BOOLEAN, '~Switch',           true,  'hc1dhwprio',          'HK1 Optimierung'],
        'T_HK1WPKuehlen'            => ['HK1 WP Kuehlen',                       VARIABLETYPE_BOOLEAN, '~Switch',           true,  'hc1hpcooling',        'HK1 Optimierung'],
        'T_HK1KuehlungAn'           => ['HK1 Kuehlung an',                      VARIABLETYPE_BOOLEAN, '~Switch',           false, 'hc1cooling',          'HK1 Optimierung'],
        'T_WWKBetriebsart'          => ['WWK Betriebsart',                      VARIABLETYPE_INTEGER, 'BHP.DHWMode',       true,  'wwmode',              'WWK Thermostat'],
        'T_WWKZirkModus'            => ['WWK Zirkulationspumpenmodus',          VARIABLETYPE_INTEGER, 'BHP.CircModeTH',    true,  'wwcircmode',          'WWK Thermostat'],
        'T_WWKLadedauer'            => ['WWK Ladedauer',                        VARIABLETYPE_INTEGER, 'BHP.Minutes',       true,  'wwtankcapacity',      'WWK Thermostat'],
        'T_WWKLaden'                => ['WWK Laden',                            VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wwload',              'WWK Thermostat'],
        'T_WWKExtra'                => ['WWK Extra',                            VARIABLETYPE_BOOLEAN, '~Switch',           false, 'wwextra',             'WWK Thermostat'],
        'T_WWKDesinfizieren'        => ['WWK Desinfizieren',                    VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wwdisinfecting',      'WWK Thermostat'],
        'T_WWKDesinfektionstag'     => ['WWK Desinfektionstag',                  VARIABLETYPE_INTEGER, 'BHP.Wochentag',     true,  'wwdisinfectday',      'WWK Thermostat'],
        'T_WWKDesinfektionszeit'    => ['WWK Desinfektionszeit',                VARIABLETYPE_INTEGER, 'BHP.Minutes',       true,  'wwdisinfecttime',     'WWK Thermostat'],
        'T_WWKTaeglHeizen'          => ['WWK taeglich Heizen',                  VARIABLETYPE_BOOLEAN, '~Switch',           true,  'wwdailyheating',      'WWK Thermostat'],
        'T_WWKTaeglHeizzeit'        => ['WWK taegliche Heizzeit',              VARIABLETYPE_INTEGER, 'BHP.Minutes',       true,  'wwdailyheatingtime',  'WWK Thermostat'],
    ];

    // =========================================================================
    // Private Methoden
    // =========================================================================

    private function ProcessData(array $payload, array $entities): void
    {
        $keyMap = [];
        foreach ($entities as $ident => $def) {
            if (!empty($def[4])) {
                $keyMap[strtolower($def[4])] = $ident;
            }
        }
        foreach ($payload as $key => $value) {
            $ident = $keyMap[strtolower((string)$key)] ?? null;
            if ($ident && @$this->GetIDForIdent($ident)) {
                $this->SetVar($ident, $value, $entities[$ident][1]);
            }
        }
        if ($this->ReadPropertyBoolean('EnableEnergy')) $this->CalculateCOP();
    }

    private function SetVar(string $ident, mixed $value, int $type): void
    {
        $id = @$this->GetIDForIdent($ident);
        if (!$id) return;
        if (is_string($value)) $value = str_replace(',', '.', $value);
        switch ($type) {
            case VARIABLETYPE_BOOLEAN:
                $v = is_string($value) ? in_array(strtolower($value), ['1','true','on','yes','an','ein','aktiv'], true) : (bool)$value;
                SetValueBoolean($id, $v);
                break;
            case VARIABLETYPE_INTEGER:
                SetValueInteger($id, (int)$value);
                break;
            case VARIABLETYPE_FLOAT:
                SetValueFloat($id, (float)$value);
                break;
            case VARIABLETYPE_STRING:
                SetValueString($id, (string)$value);
                break;
        }
    }

    private function SendMQTT(string $device, string $command, mixed $value): void
    {
        $prefix  = $this->ReadPropertyString('TopicPrefix');
        $topic   = $prefix . '/' . $device . '/set';
        $payload = json_encode([$command => $value]);
        $this->SendDataToParent(json_encode([
            'DataID'  => '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}',
            'Topic'   => $topic,
            'Payload' => $payload,
            'QoS'     => 0,
            'Retain'  => false,
        ]));
    }

    private function CalculateCOP(): void
    {
        $heatID = @$this->GetIDForIdent('B_HeizenEnergie');
        $dhwID  = @$this->GetIDForIdent('B_WWKEnergie');
        $elecID = @$this->GetIDForIdent('B_GesamtVerbrauch');
        $copID  = @$this->GetIDForIdent('B_COP');
        if (!$heatID || !$dhwID || !$elecID || !$copID) return;
        $heat = GetValueFloat($heatID) + GetValueFloat($dhwID);
        $elec = GetValueFloat($elecID);
        if ($elec > 0) SetValueFloat($copID, round($heat / $elec, 2));
    }

    private function CreateAllVariables(array $entities, string $deviceName): void
    {
        $deviceCatID = $this->EnsureCategory('DevCat_' . md5($deviceName), $deviceName, $this->InstanceID);
        $cats = [];
        foreach ($entities as $ident => $def) {
            $catName = $def[5] ?? 'Sonstiges';
            if (!isset($cats[$catName])) {
                $cats[$catName] = $this->EnsureCategory('SubCat_' . md5($deviceName . $catName), $catName, $deviceCatID);
            }
        }
        foreach ($entities as $ident => $def) {
            [$name, $type, $profile, $writable, $emsKey, $catName] = $def;
            $catID = $cats[$catName] ?? $deviceCatID;
            $varID = @$this->GetIDForIdent($ident);
            if (!$varID) {
                switch ($type) {
                    case VARIABLETYPE_BOOLEAN: $varID = $this->RegisterVariableBoolean($ident, $name, $profile); break;
                    case VARIABLETYPE_INTEGER: $varID = $this->RegisterVariableInteger($ident, $name, $profile); break;
                    case VARIABLETYPE_FLOAT:   $varID = $this->RegisterVariableFloat($ident, $name, $profile);   break;
                    case VARIABLETYPE_STRING:  $varID = $this->RegisterVariableString($ident, $name, $profile);  break;
                }
                if ($varID) IPS_SetParent($varID, $catID);
            }
            if ($varID && $writable) $this->EnableAction($ident);
        }
    }

    private function EnsureCategory(string $ident, string $name, int $parentID): int
    {
        $id = @$this->GetIDForIdent($ident);
        if (!$id) {
            $id = IPS_CreateCategory();
            IPS_SetName($id, $name);
            IPS_SetParent($id, $parentID);
            IPS_SetIdent($id, $ident);
        }
        return $id;
    }

    private function GetAllValues(): array
    {
        $result = [];
        $allEntities = array_merge(self::BOILER_ENTITIES, self::THERMOSTAT_ENTITIES);
        $enumMaps = [
            'B_Silentmodus'        => [0=>'Aus', 1=>'Auto', 2=>'An'],
            'B_HeizstabLeistung'   => [0=>'0 kW', 2=>'2 kW', 3=>'3 kW', 4=>'4 kW', 6=>'6 kW', 9=>'9 kW'],
            'B_ZusatzheizungsModus'=> [0=>'Eco', 1=>'Komfort'],
            'T_HK1Betriebsart'    => [0=>'Aus', 1=>'Manuell', 2=>'Auto'],
            'T_HK1WPModus'        => [0=>'Heizen', 1=>'Kuehlen', 2=>'Heizen & Kuehlen'],
            'T_WWKBetriebsart'    => [0=>'Aus', 1=>'Eco+', 2=>'Eco', 3=>'Komfort', 4=>'Auto'],
        ];
        foreach ($allEntities as $ident => $def) {
            $id = @$this->GetIDForIdent($ident);
            if (!$id) continue;
            $type = $def[1];
            $val = match($type) {
                VARIABLETYPE_BOOLEAN => GetValueBoolean($id),
                VARIABLETYPE_INTEGER => GetValueInteger($id),
                VARIABLETYPE_FLOAT   => GetValueFloat($id),
                default              => GetValueString($id),
            };
            $result[$ident] = $val;
            if (isset($enumMaps[$ident]) && $type === VARIABLETYPE_INTEGER) {
                $result[$ident . '_str'] = $enumMaps[$ident][$val] ?? (string)$val;
            }
        }
        return $result;
    }

    private function RegisterWebHook(): void
    {
        if (!$this->ReadPropertyBoolean('EnableDashboard')) return;
        $hookPath = '/hook/BoschHeatpump/' . $this->InstanceID;
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        foreach ($ids as $id) {
            $hooks = json_decode(IPS_GetProperty($id, 'Hooks'), true);
            if (is_array($hooks)) {
                foreach ($hooks as $hook) {
                    if ($hook['Hook'] === $hookPath) return;
                }
            }
        }
        if (!empty($ids)) {
            $whID  = $ids[0];
            $hooks = json_decode(IPS_GetProperty($whID, 'Hooks'), true) ?: [];
            $hooks[] = ['Hook' => $hookPath, 'TargetID' => $this->InstanceID];
            IPS_SetProperty($whID, 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($whID);
        }
    }

    // =========================================================================
    // Variablenprofile
    // =========================================================================

    private function RegisterProfiles(): void
    {
        $fp = function(string $n, int $t, string $ic, string $sfx, float $mn, float $mx, float $st, int $dg): void {
            if (!IPS_VariableProfileExists($n)) IPS_CreateVariableProfile($n, $t);
            IPS_SetVariableProfileIcon($n, $ic);
            IPS_SetVariableProfileText($n, '', $sfx);
            IPS_SetVariableProfileValues($n, $mn, $mx, $st);
            if ($t === VARIABLETYPE_FLOAT) IPS_SetVariableProfileDigits($n, $dg);
        };
        $fp('BHP.Bar',     VARIABLETYPE_FLOAT,   'Gauge',       ' bar',   0, 6,      0.1,  1);
        $fp('BHP.kWh',     VARIABLETYPE_FLOAT,   'Electricity', ' kWh',   0, 999999, 0.1,  1);
        $fp('BHP.kW',      VARIABLETYPE_FLOAT,   'Electricity', ' kW',    0, 20,     0.1,  2);
        $fp('BHP.Watt',    VARIABLETYPE_INTEGER, 'Electricity', ' W',     0, 9000,   1,    0);
        $fp('BHP.COP',     VARIABLETYPE_FLOAT,   'Graph',       '',       0, 10,     0.01, 2);
        $fp('BHP.Hours',   VARIABLETYPE_FLOAT,   'Clock',       ' h',     0, 999999, 1,    0);
        $fp('BHP.Minutes', VARIABLETYPE_INTEGER, 'Clock',       ' min',   0, 3810,   1,    0);
        $fp('BHP.Percent', VARIABLETYPE_INTEGER, 'Intensity',   ' %',     0, 100,    1,    0);
        $fp('BHP.RPM',     VARIABLETYPE_INTEGER, 'Gear',        ' rpm',   0, 9000,   1,    0);
        $fp('BHP.mbar',    VARIABLETYPE_INTEGER, 'Gauge',       ' mbar',  0, 1000,   10,   0);
        $fp('BHP.lh',      VARIABLETYPE_FLOAT,   'Drops',       ' l/h',   0, 5000,   0.1,  1);
        $fp('BHP.lmin',    VARIABLETYPE_FLOAT,   'Drops',       ' l/min', 0, 100,    0.1,  1);
        $fp('BHP.Kmin',    VARIABLETYPE_INTEGER, 'Clock',       ' Kmin',  0, 1000,   10,   0);

        $this->CreateEnumProfile('BHP.SilentMode',    VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'Auto','',0x0066CC],[2,'An','',0x00AA44]]);
        $this->CreateEnumProfile('BHP.PrimaryMode',   VARIABLETYPE_INTEGER, [[0,'Auto','',0x0066CC],[1,'Kontinuierlich','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.HeaterPower',   VARIABLETYPE_INTEGER, [[0,'0 kW','',0x888888],[2,'2 kW','',0xFFAA00],[3,'3 kW','',0xFF8800],[4,'4 kW','',0xFF6600],[6,'6 kW','',0xFF4400],[9,'9 kW','',0xFF0000]]);
        $this->CreateEnumProfile('BHP.OperatingMode', VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'Manuell','',0xFF6600],[2,'Auto','',0x00AA44]]);
        $this->CreateEnumProfile('BHP.HPMode',        VARIABLETYPE_INTEGER, [[0,'Heizen','',0xFF4400],[1,'Kuehlen','',0x0066CC],[2,'Heizen & Kuehlen','',0x9900CC]]);
        $this->CreateEnumProfile('BHP.DHWMode',       VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'Eco+','',0x00CC66],[2,'Eco','',0x00AA44],[3,'Komfort','',0xFF6600],[4,'Auto','',0x0066CC]]);
        $this->CreateEnumProfile('BHP.CircMode',      VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'1x3 min','',0xAACC00],[2,'2x3 min','',0x88CC00],[3,'3x3 min','',0x66BB00],[4,'4x3 min','',0x44AA00],[5,'5x3 min','',0x229900],[6,'6x3 min','',0x008800],[7,'Kontinuierlich','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.CircModeTH',    VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'An','',0x00AA44],[2,'Auto','',0x0066CC],[3,'Eigenprog.','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.AuxMode',       VARIABLETYPE_INTEGER, [[0,'Eco','',0x00AA44],[1,'Komfort','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.WWKComfort',    VARIABLETYPE_INTEGER, [[0,'Eco','',0x00AA44],[1,'Gehobener Komfort','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.Gebaeudetyp',   VARIABLETYPE_INTEGER, [[0,'Leicht','',0x66AAFF],[1,'Mittel','',0xFFAA00],[2,'Schwer','',0xFF4400]]);
        $this->CreateEnumProfile('BHP.Heizungstyp',   VARIABLETYPE_INTEGER, [[0,'Aus','',0x888888],[1,'Heizkoerper','',0xFF6600],[2,'Konvektor','',0xFFAA00],[3,'Fussboden','',0x00AA44]]);
        $this->CreateEnumProfile('BHP.SommerModus',   VARIABLETYPE_INTEGER, [[0,'Sommer','',0xFFDD00],[1,'Auto','',0x0066CC],[2,'Winter','',0x4488FF]]);
        $this->CreateEnumProfile('BHP.Absenkmodus',   VARIABLETYPE_INTEGER, [[0,'Aussen','',0x4488FF],[1,'Raum','',0xFF6600],[2,'Reduziert','',0x888888]]);
        $this->CreateEnumProfile('BHP.Frostmodus',    VARIABLETYPE_INTEGER, [[0,'Raum','',0xFF6600],[1,'Aussen','',0x4488FF],[2,'Raum + Aussen','',0x9900CC]]);
        $this->CreateEnumProfile('BHP.Steuermodus',   VARIABLETYPE_INTEGER, [[0,'Wetter kompensiert','',0x0066CC],[1,'Basispunkt Aussentemp.','',0xFF6600],[2,'n/a','',0x888888],[3,'Raum','',0x00AA44],[4,'Leistung','',0xFFAA00],[5,'Konstant','',0xFF4400]]);
        $this->CreateEnumProfile('BHP.Programm',      VARIABLETYPE_INTEGER, [[0,'Prog 1','',0x0066CC],[1,'Prog 2','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.SchaltModus',   VARIABLETYPE_INTEGER, [[0,'Level','',0x0066CC],[1,'Absolut','',0xFF6600]]);
        $this->CreateEnumProfile('BHP.Wochentag',     VARIABLETYPE_INTEGER, [[0,'Mo','',0x0066CC],[1,'Di','',0x0066CC],[2,'Mi','',0x0066CC],[3,'Do','',0x0066CC],[4,'Fr','',0x0066CC],[5,'Sa','',0xFF6600],[6,'So','',0xFF4400],[7,'Alle','',0x00AA44]]);
    }

    private function CreateEnumProfile(string $name, int $type, array $entries): void
    {
        if (!IPS_VariableProfileExists($name)) IPS_CreateVariableProfile($name, $type);
        $existing = IPS_GetVariableProfile($name)['Associations'];
        foreach ($existing as $a) IPS_SetVariableProfileAssociation($name, $a['Value'], '', '', -1);
        foreach ($entries as [$val, $lbl, $ico, $col]) {
            IPS_SetVariableProfileAssociation($name, $val, $lbl, $ico, $col);
        }
    }

    // =========================================================================
    // Oeffentliche Hilfsfunktionen
    // =========================================================================

    public function SetupVariables(): void
    {
        $this->ApplyChanges();
        $total = count(self::BOILER_ENTITIES) + count(self::THERMOSTAT_ENTITIES);
        echo "Variablen angelegt: $total\n";
    }

    public function RequestUpdate(): void
    {
        $prefix = $this->ReadPropertyString('TopicPrefix');
        foreach (['boiler', 'thermostat'] as $device) {
            $this->SendDataToParent(json_encode([
                'DataID'  => '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}',
                'Topic'   => $prefix . '/system/publish',
                'Payload' => $device,
                'QoS'     => 0,
                'Retain'  => false,
            ]));
        }
    }

    public function DHWOneTimeCharge(): void { $this->RequestAction('B_WWKEinmalladung', 1); }
    public function SetHK1Mode(int $mode): void { $this->RequestAction('T_HK1Betriebsart', $mode); }
    public function SetHPMode(int $mode): void  { $this->RequestAction('T_HK1WPModus', $mode); }
    public function SetDHWMode(int $mode): void  { $this->RequestAction('T_WWKBetriebsart', $mode); }
    public function SetSilentMode(int $mode): void { $this->RequestAction('B_Silentmodus', $mode); }
    public function SetFlowTemp(float $temp): void { $this->RequestAction('B_VorlaufGewaehlt', $temp); }
    public function SetDHWTemp(float $temp): void  { $this->RequestAction('B_WWKGewaehltTemp', $temp); }
    public function SetAbsent(bool $v): void { $this->RequestAction('T_Abwesend', $v ? 1 : 0); }
}
