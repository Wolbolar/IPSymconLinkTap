<?php
declare(strict_types=1);

require_once(__DIR__ . "/../bootstrap.php");
require_once __DIR__ . '/../libs/ProfileHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';

class LinkTap extends IPSModule
{
    use ProfileHelper;

    // helper properties
    private $position = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{F3E543DF-E914-748B-EB0B-E3348AF969B6}');
        $this->RegisterPropertyString('gatewayId', ''); // String type. Your LinkTap Gateway's first 16-digits/letters ID, case insensitive, no dash symbol, e,g, 3F7A23FE004B1200
        $this->RegisterPropertyString('taplinkerId', ''); // String type. Your LinkTap Taplinker's first 16-digits/letters ID, case insensitive, no dash symbol, e,g, 67ABCDEF004B1200
        $this->RegisterPropertyString('name', '');
        $this->RegisterPropertyInteger('type', 2);
        $this->RegisterAttributeString('name', '');
        $this->RegisterAttributeBoolean('name_enabled', false);
        $this->RegisterAttributeBoolean('irrigation_state', false);
        $this->RegisterAttributeInteger('irrigation_time', 10);
        $this->RegisterAttributeInteger('irrigation_mode', 0);

        $this->RegisterAttributeString('location', '');
        $this->RegisterAttributeBoolean('location_enabled', false);
        $this->RegisterAttributeString('status', '');
        $this->RegisterAttributeBoolean('status_enabled', false);
        $this->RegisterAttributeString('version', '');
        $this->RegisterAttributeBoolean('version_enabled', false);
        $this->RegisterAttributeString('taplinkerName', '');
        $this->RegisterAttributeBoolean('taplinkerName_enabled', false);
        $this->RegisterAttributeInteger('signal', 0);
        $this->RegisterAttributeBoolean('signal_enabled', false);
        $this->RegisterAttributeInteger('batteryStatus', 0);
        $this->RegisterAttributeBoolean('batteryStatus_enabled', false);
        $this->RegisterAttributeInteger('workMode', 5); // currently activated work mode.
        $this->RegisterAttributeBoolean('workMode_enabled', false);

        $this->RegisterAttributeInteger('interval', 0);
        $this->RegisterAttributeBoolean('interval_enabled', false);
        $this->RegisterAttributeInteger('Y', 0);
        $this->RegisterAttributeBoolean('Y_enabled', false);
        $this->RegisterAttributeInteger('X', 0);
        $this->RegisterAttributeBoolean('X_enabled', false);
        $this->RegisterAttributeInteger('Z', 0);
        $this->RegisterAttributeBoolean('Z_enabled', false);
        $this->RegisterAttributeInteger('ecoOff', 0);
        $this->RegisterAttributeBoolean('ecoOff_enabled', false);
        $this->RegisterAttributeInteger('ecoOn', 0);
        $this->RegisterAttributeBoolean('ecoOn_enabled', false);
        $this->RegisterAttributeBoolean('eco', false);
        $this->RegisterAttributeBoolean('eco_enabled', false);
        // current watering plan. 'H' represents hour, 'M' represents minute, 'D' represents duration.
        $this->RegisterAttributeInteger('H', 0);
        $this->RegisterAttributeBoolean('H_enabled', false);
        $this->RegisterAttributeInteger('M', 0);
        $this->RegisterAttributeBoolean('M_enabled', false);
        $this->RegisterAttributeInteger('D', 0);
        $this->RegisterAttributeBoolean('D_enabled', false);
        $this->RegisterAttributeInteger('watering', 0);
        $this->RegisterAttributeBoolean('watering_enabled', false);
        $this->RegisterAttributeFloat('vel', 0); // current flow rate (unit: ml per minute. For G2 only).
        $this->RegisterAttributeBoolean('vel_enabled', false);
        $this->RegisterAttributeBoolean('fall', false); // fall incident flag (boolean. For G2 only).
        $this->RegisterAttributeBoolean('fall_enabled', false);
        $this->RegisterAttributeBoolean('valveBroken', false); // valve failed to open flag (boolean. For G2 only).
        $this->RegisterAttributeBoolean('valveBroken_enabled', false);
        $this->RegisterAttributeBoolean('noWater', false); // water cut-off flag (boolean. For G2 only).
        $this->RegisterAttributeBoolean('noWater_enabled', false);

        $this->RegisterAttributeInteger('onDuration', 0); // the remaining watering duration of a watering slot.
        $this->RegisterAttributeBoolean('onDuration_enabled', false);
        $this->RegisterAttributeInteger('total', 0); // the total watering duration of a watering slot.
        $this->RegisterAttributeBoolean('total_enabled', false);
        $this->RegisterAttributeInteger('onStamp', 0);
        $this->RegisterAttributeBoolean('onStamp_enabled', false);
        $this->RegisterAttributeInteger('firstDt', 0);
        $this->RegisterAttributeBoolean('firstDt_enabled', false);
        $this->RegisterAttributeInteger('pbThrd', 0);
        $this->RegisterAttributeBoolean('pbThrd_enabled', false);
        $this->RegisterAttributeInteger('pcThrd', 0);
        $this->RegisterAttributeBoolean('pcThrd_enabled', false);
        $this->RegisterAttributeInteger('pDelay', 0);
        $this->RegisterAttributeBoolean('pDelay_enabled', false);
        $this->RegisterAttributeInteger('pbCnt', 0);
        $this->RegisterAttributeBoolean('pbCnt_enabled', false);
        $this->RegisterAttributeInteger('pcCnt', 0);
        $this->RegisterAttributeBoolean('pcCnt_enabled', false);
        $this->RegisterAttributeInteger('vol', 0);
        $this->RegisterAttributeBoolean('vol_enabled', false);

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] === IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] === KR_READY) {
                    $this->ApplyChanges();
                }
                break;

            default:
                break;
        }
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $id = $this->ReadPropertyString('gatewayId');
        $this->SetReceiveDataFilter(".*" . $id . ".*");
        $this->ValidateConfiguration();
    }

    private function ValidateConfiguration()
    {
        $taplinkerId = $this->ReadPropertyString('taplinkerId');
        if ($taplinkerId == '') {
            $this->SetStatus(205);
        } elseif ($taplinkerId != '') {
            $this->RegisterVariables();
            $this->SetStatus(IS_ACTIVE);
        }
    }


    /** @noinspection PhpMissingParentCallCommonInspection */

    private function RegisterVariables(): void
    {


        $this->SetupVariable(
            'name', $this->Translate('gateway name'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'irrigation_state', $this->Translate('irrigation status'), '~Switch', $this->_getPosition(), VARIABLETYPE_BOOLEAN, true, true
        );
        $this->RegisterProfile('LinkTap.IrrigationTime', 'Clock', '', ' min', 0, 1439, 1, 0, VARIABLETYPE_INTEGER);
        $this->SetupVariable(
            'irrigation_time', $this->Translate('irrigation time'), 'LinkTap.IrrigationTime', $this->_getPosition(), VARIABLETYPE_INTEGER, true, true
        );
        $mode_ass = [
            [0, $this->Translate("Instant Mode"), "", -1],
            [1, $this->Translate("Interval Mode"), "", -1],
            [2, $this->Translate("Odd-Even Mode"), "", -1],
            [3, $this->Translate("Seven Day Mode"), "", -1],
            [4, $this->Translate("Month Mode"), "", -1],
            [5, $this->Translate("Month Mode"), "", -1],
            [6, $this->Translate("No Mode"), "", -1]];
        $this->RegisterProfileAssociation('LinkTap.IrrigationMode', 'Drops', '', '', 0, 6, 1, 0, VARIABLETYPE_INTEGER, $mode_ass);

        $this->SetupVariable(
            'workMode', $this->Translate('irrigation mode'), 'LinkTap.IrrigationMode', $this->_getPosition(), VARIABLETYPE_INTEGER, true, true
        );
        $this->SetupVariable(
            'location', $this->Translate('location'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'status', $this->Translate('status'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'version', $this->Translate('version'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'taplinkerName', $this->Translate('Taplinker name'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'signal', $this->Translate('signal'), '~Intensity.100', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'batteryStatus', $this->Translate('battery status'), '~Battery.100', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'fall', $this->Translate('fall'), '~Switch', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $this->SetupVariable(
            'valveBroken', $this->Translate('valve broken'), '~Switch', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $this->SetupVariable(
            'noWater', $this->Translate('no water'), '~Switch', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        ); // // water cut-off flag (boolean. For G2 only).
        $this->RegisterProfile('LinkTap.Flowrate', 'Drops', '', ' l/min', 0, 10, 0.1, 1, VARIABLETYPE_FLOAT);
        $this->SetupVariable(
            'vel', $this->Translate('current flow rate'), 'LinkTap.Flowrate', $this->_getPosition(), VARIABLETYPE_FLOAT, false, false
        ); // current flow rate (unit: ml per minute. For G2 only).
        $this->RegisterProfile('LinkTap.RemainingDuration', 'Drops', '', ' s', 0, 1000, 1, 0, VARIABLETYPE_INTEGER);
        $this->SetupVariable(
            'onDuration', $this->Translate('remaining watering duration'), 'LinkTap.RemainingDuration', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        ); // the remaining watering duration of a watering slot.
        $this->RegisterProfile('LinkTap.Total', 'Drops', '', ' ml/minute', 0, 1000, 1, 0, VARIABLETYPE_INTEGER);
        $this->SetupVariable(
            'total', $this->Translate('total'), 'LinkTap.Total', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        ); // the total watering duration of a watering slot.

        $this->SetupVariable(
            'interval', $this->Translate('interval'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'Y', $this->Translate('Y'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'X', $this->Translate('X'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'Z', $this->Translate('z'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'ecoOff', $this->Translate('ecoOff'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'ecoOn', $this->Translate('ecoOn'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'ecoOn', $this->Translate('eco'), '', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $this->SetupVariable(
            'H', $this->Translate('Hour'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'M', $this->Translate('Minutes'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'D', $this->Translate('Duration'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'watering', $this->Translate('watering'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'onStamp', $this->Translate('onStamp'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'firstDt', $this->Translate('firstDt'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );

        $this->SetupVariable(
            'pbThrd', $this->Translate('pbThrd'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'pcThrd', $this->Translate('pcThrd'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'pDelay', $this->Translate('pDelay'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'pbCnt', $this->Translate('pbCnt'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'pcCnt', $this->Translate('pcCnt'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'vol', $this->Translate('volume'), '', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );

        $this->WriteValues();
    }

    /** Variable anlegen / löschen
     *
     * @param $ident
     * @param $name
     * @param $profile
     * @param $position
     * @param $vartype
     * @param $enableaction
     * @param bool $visible
     *
     * @return bool|int
     */
    protected function SetupVariable($ident, $name, $profile, $position, $vartype, $enableaction, $visible = false)
    {
        $objid = false;
        if ($visible) {
            $this->SendDebug('LinkTap Variable:', 'Variable with Ident ' . $ident . ' is visible', 0);
        } else {
            $visible = $this->ReadAttributeBoolean($ident . '_enabled');
            $this->SendDebug('LinkTap Variable:', 'Variable with Ident ' . $ident . ' is shown ' . print_r($visible, true), 0);
        }
        if ($visible == true) {
            switch ($vartype) {
                case VARIABLETYPE_BOOLEAN:
                    $objid = $this->RegisterVariableBoolean($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeBoolean($ident);
                    break;
                case VARIABLETYPE_INTEGER:
                    $objid = $this->RegisterVariableInteger($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeInteger($ident);
                    break;
                case VARIABLETYPE_FLOAT:
                    $objid = $this->RegisterVariableFloat($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeFloat($ident);
                    break;
                case VARIABLETYPE_STRING:
                    $objid = $this->RegisterVariableString($ident, $name, $profile, $position);
                    if ($ident == 'taplinkerId') {
                        $value = $this->ReadPropertyString($ident);
                    } else {
                        $value = $this->ReadAttributeString($ident);
                    }
                    break;
            }
            $this->SetValue($ident, $value);
            if ($enableaction) {
                $this->EnableAction($ident);
            }
        } else {
            $objid = @$this->GetIDForIdent($ident);
            if ($objid > 0) {
                $this->UnregisterVariable($ident);
            }
        }
        return $objid;
    }

    /**
     * return incremented position
     * @return int
     */
    private function _getPosition()
    {
        $this->position++;
        return $this->position;
    }


    /** @noinspection PhpMissingParentCallCommonInspection */

    private function WriteValues()
    {
        $id = $this->ReadPropertyString('gatewayId');
        $this->SendDebug('LinkTap Write Values', 'Gateway ID ' . $id, 0);
        $this->WriteEnabledValue('name', VARIABLETYPE_STRING);
        $this->WriteEnabledValue('irrigation_state', VARIABLETYPE_BOOLEAN, true);
        $this->WriteEnabledValue('irrigation_time', VARIABLETYPE_INTEGER, true);
        $this->WriteEnabledValue('workMode', VARIABLETYPE_INTEGER, true);
        $this->WriteEnabledValue('batteryStatus', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('signal', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('location', VARIABLETYPE_STRING);
        $this->WriteEnabledValue('status', VARIABLETYPE_STRING);
        $this->WriteEnabledValue('version', VARIABLETYPE_STRING);
        $this->WriteEnabledValue('taplinkerName', VARIABLETYPE_STRING);
        $this->WriteEnabledValue('vel', VARIABLETYPE_FLOAT);
        $this->WriteEnabledValue('onDuration', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('total', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('fall', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('valveBroken', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('noWater', VARIABLETYPE_BOOLEAN);

        $this->WriteEnabledValue('interval', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('Y', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('X', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('Z', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('ecoOff', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('ecoOn', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('eco', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('H', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('M', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('D', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('watering', VARIABLETYPE_INTEGER);

        $this->WriteEnabledValue('onStamp', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('firstDt', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('pbThrd', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('pcThrd', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('pDelay', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('pbCnt', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('pcCnt', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('vol', VARIABLETYPE_INTEGER);
    }

    // LinkTap API

    // User data

    private function WriteEnabledValue($ident, $vartype, $enabled = false)
    {
        if ($enabled) {
            $value_enabled = true;
        } else {
            $value_enabled = $this->ReadAttributeBoolean($ident . '_enabled');
        }

        if ($value_enabled) {
            switch ($vartype) {
                case VARIABLETYPE_BOOLEAN:
                    $value = $this->ReadAttributeBoolean($ident);
                    $this->SendDebug('SetValue boolean', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_INTEGER:
                    $value = $this->ReadAttributeInteger($ident);
                    $this->SendDebug('SetValue integer', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_FLOAT:
                    if($ident == 'vel')
                    {
                        $value = $this->ReadAttributeFloat($ident);
                        if($value != 0)
                        {
                            $value = $value / 1000;
                        }
                    }
                    else{
                        $value = $this->ReadAttributeFloat($ident);
                    }
                    $this->SendDebug('SetValue float', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_STRING:
                    $value = $this->ReadAttributeString($ident);
                    $this->SendDebug('SetValue string', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
            }
        }
    }

    private function SetVariableValue($ident, $value)
    {
        if (@$this->GetIDForIdent($ident)) {
            $this->SetValue($ident, $value);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident === 'irrigation_state') {
            if ($Value) {
                $this->Watering_On();
            } else {
                $this->Watering_Off();
            }
        }
        if ($Ident === 'irrigation_time') {
            $this->SetDuration($Value);
        }
        if ($Ident === 'irrigation_mode') {
            switch ($Value) {
                case 0: // Activate Interval Mode
                    $this->ActivateIntervalMode();
                    break;
                case 1: // Activate Odd-Even Mode
                    $this->ActivateOddEvenMode();
                    break;
                case 2: // Activate Seven Day Mode
                    $this->ActivateSevenDayMode();
                    break;
                case 3: // ActivateMonthMode
                    $this->ActivateMonthMode();
                    break;
            }
        }
        if ($Ident === 'eco') {
            $this->WriteAttributeBoolean('eco', $Value);
            $this->SetValue('eco', $Value);
        }
        if ($Ident === 'ecoOn') {
            $this->WriteAttributeInteger('ecoOn', $Value);
            $this->SetValue('ecoOn', $Value);
        }
        if ($Ident === 'ecoOff') {
            $this->WriteAttributeInteger('ecoOff', $Value);
            $this->SetValue('ecoOff', $Value);
        }
    }

    protected function CheckIntervalLastCommand()
    {
        $last_command_timestamp = $this->ReadAttributeInteger('last_command_timestamp');
        $current_time = time();
        $interval = $current_time - $last_command_timestamp;
        if($interval > 15)
        {
            $check = true;
        }
        else
        {
            $check = false;
        }
        return $check;
    }

    public function SendCommand(string $endpoint, string $data)
    {
        return $this->SendDataToParent(json_encode([
            'DataID' => '{CEEFEF59-8DCC-17F6-BDA4-D5DDBEFF1E22}',
            'Type' => 'POST',
            'Endpoint' => $endpoint,
            'Payload' => $data
        ]));
    }

    public function DelayCommand()
    {
        $last_command_timestamp = $this->ReadAttributeInteger('last_command_timestamp');
        do {
            IPS_Sleep(1000);
            $current_time = time();
            $remaining_seconds = $current_time - $last_command_timestamp;
            $this->SendDebug('API Limit', 'reached API limit, waiting for ' . (15 - $remaining_seconds), 0);
        } while ($remaining_seconds < 15);
    }

    /** Set watering duration
     * (unit is minute) the range is from 1 minute to 1439 minutes.
     * @param int $duration
     */
    public function SetDuration(int $duration)
    {
        if($duration < 1)
        {
            $duration = 1;
        }
        elseif($duration > 1439)
        {
            $duration = 1439;
        }
        $this->WriteAttributeInteger('irrigation_time', $duration);
        $this->SetValue('irrigation_time', $duration);
    }

    public function ReceiveData($JSONString) {
        $data = json_decode($JSONString);
        $this->SendDebug('Receive Data', $JSONString, 0);
        $payload = $data->Buffer;
        $this->CheckResponse('get_all_devices', $payload);
        $this->WriteValues();
    }

    private function CheckResponse($ident, $payload, $value = NULL)
    {
        if($payload != '[]')
        {
            $this->SendDebug('LinkTap Response', $payload, 0);
            if($ident == 'get_all_devices')
            {
                $devices = json_decode($payload);

                foreach($devices as $device)
                {
                    // var_dump($device);

                    $name = $device->name;
                    $this->SendDebug('LinkTap Device Name', $name, 0);
                    $this->WriteAttributeString('name', $name);
                    $location = $device->location;
                    $this->SendDebug('LinkTap Device Location', $location, 0);
                    $gatewayId = $device->gatewayId;
                    $this->SendDebug('LinkTap Device Gateway ID', $gatewayId, 0);
                    $status = $device->status;
                    $this->SendDebug('LinkTap Device Status', $status, 0);
                    $version = $device->version;
                    $this->SendDebug('LinkTap Device Version', $version, 0);
                    $taplinker = $device->taplinker;
                    foreach($taplinker as $taplink)
                    {
                        $taplinkerId = $taplink->taplinkerId;
                        if($this->ReadPropertyString('taplinkerId') == $taplinkerId)
                        {
                            $taplinkerName = $taplink->taplinkerName;
                            $this->WriteAttributeString('taplinkerName', $taplinkerName);
                            $status = $taplink->status;
                            $this->WriteAttributeString('status', $status);
                            $location = $taplink->location;
                            $this->WriteAttributeString('location', $location);
                            $version = $taplink->version;
                            $this->WriteAttributeString('version', $version);
                            $signal = intval(str_replace('%', '', $taplink->signal));
                            $this->WriteAttributeInteger('signal', $signal);
                            $batteryStatus = intval(str_replace('%', '', $taplink->batteryStatus));
                            $this->WriteAttributeInteger('batteryStatus', $batteryStatus);
                            $workMode = $taplink->workMode;
                            if($workMode == 'M') // ‘M’ is for Instant Mode
                            {
                            $this->WriteAttributeInteger('workMode', 0);
                            }
                            elseif($workMode == 'I') // ‘I’ is for Interval Mode
                            {
                                $this->WriteAttributeInteger('workMode', 1);
                            }
                            elseif($workMode == 'O') // ‘O’ is for Odd-Even Mode
                            {
                                $this->WriteAttributeInteger('workMode', 2);
                            }
                            elseif($workMode == 'T') // ‘T’ is for 7-Day Mode
                            {
                                $this->WriteAttributeInteger('workMode', 3);
                            }
                            elseif($workMode == 'Y') // ‘Y’ is for Month Mode
                            {
                                $this->WriteAttributeInteger('workMode', 4);
                            }
                            elseif($workMode == 'N') // N’ means no work mode assigned.
                            {
                                $this->WriteAttributeInteger('workMode', 5);
                            }
                            $plan = $taplink->plan; // current watering plan
                            if(isset($plan->interval))
                            {
                                $interval = $plan->interval;
                                $this->WriteAttributeInteger('interval', $interval);
                            }
                            $Y = $plan->Y;
                            $this->WriteAttributeInteger('Y', $Y);
                            $X = $plan->X;
                            $this->WriteAttributeInteger('X', $X);
                            $Z = $plan->Z;
                            $this->WriteAttributeInteger('Z', $Z);
                            $ecoOff = $plan->ecoOff;
                            $this->WriteAttributeInteger('ecoOff', $ecoOff);
                            $ecoOn = $plan->ecoOn;
                            $this->WriteAttributeInteger('ecoOn', $ecoOn);
                            $eco = $plan->eco;
                            $this->WriteAttributeBoolean('eco', $eco);
                            if(isset($plan->slot))
                            {
                                $slot = $plan->slot; // current watering plan
                                $hour = $slot->{0}->H; // 'H' represents hour
                                $this->WriteAttributeInteger('H', $hour);
                                $minute = $slot->{0}->M; // 'M' represents minute
                                $this->WriteAttributeInteger('M', $minute);
                                $duration = $slot->{0}->D; // 'D' represents duration
                                $this->WriteAttributeInteger('D', $duration);
                            }
                            $watering = $taplink->watering;
                            $this->WriteAttributeInteger('watering', $watering);
                            $vel = floatval($taplink->vel); // latest flow rate (unit: ml per minute. For G2 only)
                            $this->WriteAttributeFloat('vel', $vel);
                            $fall = $taplink->fall; // fall incident flag (boolean. For G2 only)
                            $this->WriteAttributeBoolean('fall', $fall);
                            $valveBroken = $taplink->valveBroken; // valve failed to open flag (boolean. For G2 only)
                            $this->WriteAttributeBoolean('valveBroken', $valveBroken);
                            $noWater = $taplink->noWater; // water cut-off flag (boolean. For G2 only)
                            $this->WriteAttributeBoolean('noWater', $noWater);
                        }
                    }
                }
                $this->SendDebug('LinkTap Get Devices', $payload, 0);
            }
            else
            {
                if($ident == 'irrigation_state')
                {
                    if(empty($value))
                    {
                        $value = false;
                    }
                    $this->WriteAttributeBoolean($ident, $value);
                }
                if($ident == 'irrigation_mode')
                {
                    if(empty($value))
                    {
                        $value = 0;
                    }
                    $this->WriteAttributeInteger($ident, $value);
                }
                $this->SetValue($ident, $value);
            }
        }
        return $payload;
    }

    // LinkTap API

    /** Watering On
     * duration The watering duration (unit is minute) the range is from 1 minute to 1439 minutes.
     * @return string
     */
    public function Watering_On()
    {
        $duration = $this->ReadAttributeInteger('irrigation_time');
        $ecoOn = $this->ReadAttributeInteger('ecoOn');
        $ecoOff = $this->ReadAttributeInteger('ecoOff');
        $eco = $this->ReadAttributeBoolean('eco');

        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId'),
            'action' => true,
            'duration' => $duration,
            'eco' => $eco,
            'ecoOn' => $ecoOn,
            'ecoOff' => $ecoOff
        ]);
        $payload = $this->SendCommand('Watering_On', $data);
        $this->CheckResponse('irrigation_state', $payload, true);
        return $payload;
    }

    /** Watering On Extended
     * duration The watering duration (unit is minute) the range is from 1 minute to 1439 minutes.
     * @param int $duration
     * @param bool $eco
     * @param int $ecoOn
     * @param int $ecoOff
     * @return string
     */
    public function Watering_On_Extended(int $duration, bool $eco, int $ecoOn, int $ecoOff)
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId'),
            'action' => true,
            'duration' => $duration,
            'eco' => $eco,
            'ecoOn' => $ecoOn,
            'ecoOff' => $ecoOff
        ]);
        $payload = $this->SendCommand('Watering_On', $data);
        $this->CheckResponse('irrigation_state', $payload, true);
        return $payload;
    }

    /** Watering Off
     * @return string
     */
    public function Watering_Off()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId'),
            'action' => false,
            'duration' => 0
        ]);
        $payload = $this->SendCommand('Watering_Off', $data);
        return $this->CheckResponse('irrigation_state', $payload, false);
    }


    /** Activate Interval Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     */
    public function ActivateIntervalMode()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId')
        ]);
        $payload = $this->SendCommand('ActivateIntervalMode', $data);
        return $this->CheckResponse('irrigation_mode', $payload, 0);
    }

    /** Activate Odd-Even Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     */
    public function ActivateOddEvenMode()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId')
        ]);
        $payload = $this->SendCommand('ActivateOddEvenMode', $data);
        return $this->CheckResponse('irrigation_mode', $payload, 1);
    }

    /** Activate Seven Day Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     */
    public function ActivateSevenDayMode()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId')
        ]);
        $payload = $this->SendCommand('ActivateSevenDayMode', $data);
        return $this->CheckResponse('irrigation_mode', $payload, 2);
    }

    /** Activate Month Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     */
    public function ActivateMonthMode()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'gatewayId' => $this->ReadPropertyString('gatewayId'),
            'taplinkerId' => $this->ReadPropertyString('taplinkerId')
        ]);
        $payload = $this->SendCommand('ActivateMonthMode', $data);
        return $this->CheckResponse('irrigation_mode', $payload, 3);
    }

    /** Get All Devices
     * @param  (string $gatewayId)
     * @return string
     */
    public function Get_All_Devices()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}'
        ]);
        return $this->SendCommand('Get_All_Devices', $data);
    }

    /** Watering Status of a single Taplinker
     * @param (string $taplinkerId)
     * @return string
     */
    public function Watering_Status()
    {
        $data = json_encode([
            'username' => '{USERNAME}',
            'apiKey' => '{APIKEY}',
            'taplinkerId' => $this->ReadPropertyString('taplinkerId')
        ]);
        return $this->SendCommand('Watering_Status', $data);
        /*
         * total: the total watering duration of a watering slot.
onDuration: the remaining watering duration of a watering slot.
ecoTotal: the total watering duration of a watering plan when the ECO mode is enabled.
ecoOn: valve ON duration.
ecoOff: valve OFF duration.
vel: current flow rate (unit: ml per minute. For G2 only).

         */
    }

    public function SetWebFrontVariable(string $ident, bool $value)
    {
        $this->WriteAttributeBoolean($ident, $value);
        if ($value) {
            $this->SendDebug('LinkTap Webfront Variable', $ident . ' enabled', 0);
        } else {
            $this->SendDebug('LinkTap Webfront Variable', $ident . ' disabled', 0);
        }

        $this->RegisterVariables();
    }

    /**
     * build configuration form
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode([
            'elements' => $this->FormHead(),
            'actions' => $this->FormActions(),
            'status' => $this->FormStatus()
        ]);
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * return form configurations on configuration step
     * @return array
     */
    protected function FormHead()
    {
        $taplinkerId = $this->ReadPropertyString('taplinkerId');;
        if ($taplinkerId != '') {
            $form = [
                [
                    'type' => 'RowLayout',
                    'visible' => true,
                    'items' => [
                        [
                            'type' => 'Image',
                            'image' => 'data:image/png;base64, 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',],

                        [
                            'type' => 'Label',
                            'caption' => $this->ReadAttributeString('name')
                        ],]]
            ];
        } else {
            $form = [
                [
                    'type' => 'Label',
                    'caption' => 'This device can only created by the LinkTap configurator, please use the LinkTap configurator for creating LinkTap devices.'
                ]
            ];
        }
        return $form;
    }

    /**
     * return form actions by token
     * @return array
     */
    protected function FormActions()
    {
        $form = [
            [
                'name' => 'name_enabled',
                'type' => 'CheckBox',
                'caption' => 'name',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('name_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "name_enabled", $name_enabled);'],
            [
                'name' => 'batteryStatus_enabled',
                'type' => 'CheckBox',
                'caption' => 'battery status',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('batteryStatus_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "batteryStatus_enabled", $batteryStatus_enabled);'],
            [
                'name' => 'signal_enabled',
                'type' => 'CheckBox',
                'caption' => 'signal',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('signal_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "signal_enabled", $signal_enabled);'],
            [
                'name' => 'location_enabled',
                'type' => 'CheckBox',
                'caption' => 'location',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('location_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "location_enabled", $location_enabled);'],
            [
                'name' => 'status_enabled',
                'type' => 'CheckBox',
                'caption' => 'status',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('status_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "status_enabled", $status_enabled);'],
            [
                'name' => 'version_enabled',
                'type' => 'CheckBox',
                'caption' => 'version',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('version_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "version_enabled", $version_enabled);'],
            [
                'name' => 'taplinkerName_enabled',
                'type' => 'CheckBox',
                'caption' => 'taplinker name',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('taplinkerName_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "taplinkerName_enabled", $taplinkerName_enabled);'],
            [
                'name' => 'vel_enabled',
                'type' => 'CheckBox',
                'caption' => 'current flow rate',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('vel_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "vel_enabled", $vel_enabled);'],
            [
                'name' => 'onDuration_enabled',
                'type' => 'CheckBox',
                'caption' => 'remaining watering duration',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('onDuration_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "onDuration_enabled", $onDuration_enabled);'],
            [
                'name' => 'total_enabled',
                'type' => 'CheckBox',
                'caption' => 'total',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('total_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "total_enabled", $total_enabled);'],
            [
                'name' => 'fall_enabled',
                'type' => 'CheckBox',
                'caption' => 'fall',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('fall_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "fall_enabled", $fall_enabled);'],
            [
                'name' => 'valveBroken_enabled',
                'type' => 'CheckBox',
                'caption' => 'valve broken',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('valveBroken_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "valveBroken_enabled", $valveBroken_enabled);'],
            [
                'name' => 'noWater_enabled',
                'type' => 'CheckBox',
                'caption' => 'no water',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('noWater_enabled'),
                'onChange' => 'LINKTAP_SetWebFrontVariable($id, "noWater_enabled", $noWater_enabled);'],
            [
                'type' => 'Button',
                'visible' => true,
                'caption' => 'Read Information',
                'onClick' => 'LINKTAP_Get_All_Devices($id);'
            ]
        ];
        return $form;
    }

    /**
     * return from status
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code' => IS_CREATING,
                'icon' => 'inactive',
                'caption' => 'Creating instance.'
            ],
            [
                'code' => IS_ACTIVE,
                'icon' => 'active',
                'caption' => 'LinkTap device created.'
            ],
            [
                'code' => IS_INACTIVE,
                'icon' => 'inactive',
                'caption' => 'interface closed.'
            ],
            [
                'code' => 205,
                'icon' => 'error',
                'caption' => 'This device can only created by the LinkTap configurator, please use the LinkTap configurator for creating LinkTap devices.'
            ]
        ];

        return $form;
    }

    private function CalculateTime($time_string, $subject)
    {
        $date = new DateTime($time_string);
        $date->setTimezone(new DateTimeZone('Europe/Berlin'));
        $timestamp = $date->getTimestamp();
        $this->SendDebug('LinkTap ' . $subject . ' Timestamp', $date->format('Y-m-d H:i:sP'), 0);
        return $timestamp;
    }
}
