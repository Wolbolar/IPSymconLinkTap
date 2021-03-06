<?php
declare(strict_types=1);

class LinkTapIO extends IPSModule
{
    // LinkTap API
    private const LINK_TAP_BASE_URL = 'https://www.link-tap.com/api/';
    private const GET_API_KEY = 'https://www.link-tap.com/auth/getApiKey';
    private const ACTIVATE_INSTANT_MODE = 'activateInstantMode';
    private const ACTIVATE_INTERVAL_MODE = 'activateIntervalMode';
    private const ACTIVATE_ODD_EVEN_MODE = 'activateOddEvenMode';
    private const ACTIVATE_SEVEN_DAY_MODE = 'activateSevenDayMode';
    private const ACTIVATE_MONTH_MODE = 'activateMonthMode';
    private const GET_ALL_DEVICES = 'getAllDevices';
    private const GET_WATERING_STATUS = 'getWateringStatus';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString("user", '');
        $this->RegisterPropertyString("password", '');
        $this->RegisterPropertyString("apikey", '');
        $this->RegisterAttributeString("apikey", '');
        $this->RegisterAttributeString("taplinkerId", '');
        $this->RegisterPropertyInteger("UpdateInterval", 15);
        $this->RegisterTimer("Update", 0, "LINKTAP_Update(" . $this->InstanceID . ");");
        $this->RegisterAttributeString('devices', '[]');
        $this->RegisterTimer("Delay", 0, "LINKTAP_DelayCommand(" . $this->InstanceID . ");");
        $this->RegisterAttributeBoolean("simulation", false);
        $this->RegisterAttributeInteger("watering_status_timestamp", 0);
        $this->RegisterAttributeInteger("last_get_devices_timestamp", 0);
        $this->RegisterAttributeInteger("last_command_timestamp", 0);
        $this->RegisterAttributeString('watering_status', '[]');
        $this->RegisterAttributeInteger('delay_command', 0);

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

    /** @noinspection PhpMissingParentCallCommonInspection */

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }


        $linktap_interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetLinkTapInterval($linktap_interval);

        if ($this->ReadPropertyString('apikey') == '') {
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }
    }

    private function SetLinkTapInterval($linktap_interval): void
    {
        if ($linktap_interval < 15 && $linktap_interval != 0) {
            $linktap_interval = 15;
        }
        $interval = $linktap_interval * 1000 * 60; // minutes
        $this->SetTimerInterval('Update', $interval);
    }

    public function Update()
    {
        $data = json_encode([
        'username' => $this->ReadPropertyString('user'),
        'apiKey' => $this->ReadPropertyString('apikey')
    ]);
        $this->Get_All_Devices_Info($data);
    }

    public function UseSimulation(bool $status)
    {
        $this->WriteAttributeBoolean('simulation', $status);
    }

    public function GetAPIKey()
    {
        $url = self::LINK_TAP_BASE_URL . self::GET_API_KEY;
        $data = json_encode([
            'username' => $this->ReadPropertyString('user'),
            'password' => $this->ReadPropertyString('password')
        ]);
        return $this->PostData($url, $data);
    }

    protected function PostData($url, $data)
    {
        $last_command_timestamp = $this->ReadAttributeInteger('last_command_timestamp');
        $last_get_devices_timestamp = $this->ReadAttributeInteger('last_get_devices_timestamp');
        $watering_status_timestamp = $this->ReadAttributeInteger('watering_status_timestamp');

        if(((time() - $watering_status_timestamp) < 30) &&  ($url == self::LINK_TAP_BASE_URL . self::GET_WATERING_STATUS))
        {
            return $this->ReadAttributeString('watering_status');
        }
        if(((time() - $last_get_devices_timestamp) < 300) &&  ($url == self::LINK_TAP_BASE_URL . self::GET_ALL_DEVICES))
        {
            return $this->ReadAttributeString('devices');
        }
        if(((time() - $last_command_timestamp) < 15) &&  ($url != self::LINK_TAP_BASE_URL . self::GET_ALL_DEVICES && $url != self::LINK_TAP_BASE_URL . self::GET_WATERING_STATUS))
        {
            $this->DelayCommand($url, $data);
        }

        $this->SendDebug('LinkTap URL', $url, 0);
        $this->SendDebug('LinkTap Data', $data, 0);


        if ($url == self::LINK_TAP_BASE_URL . self::GET_API_KEY) {
            $headers = ['accept: application/json, text/plain, */*', 'accept-encoding: gzip, deflate, br', 'accept-language: de-DE,de;q=0.9,en;q=0.8,en-US;q=0.7', 'Content-type: application/json;charset="UTF-8"', 'content-length: ' . strlen($data), 'origin: https://www.link-tap.com'];
        } else {
            $headers[] = "Accept-Charset: UTF-8";
            $headers[] = "Content-type: application/json;charset=\"UTF-8\"";
            $headers[] = "Content-Length: " . strlen($data);
        }
        $simulation = $this->ReadAttributeBoolean('simulation');
        if ($simulation) {
            $response = '{"result":"ok","message":"success"}';
            $info = [];
        } else {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_URL, $url);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                trigger_error('Error:' . curl_error($ch));
            }
            $info = curl_getinfo($ch);
            $header_out = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $this->SendDebug(__FUNCTION__, 'Header Out:' . $header_out, 0);
            curl_close($ch);
        }
        if($url == self::LINK_TAP_BASE_URL . self::GET_WATERING_STATUS)
        {
            $this->WriteAttributeInteger('watering_status_timestamp', time());
        }
        elseif($url == self::LINK_TAP_BASE_URL . self::GET_ALL_DEVICES)
        {
            $this->WriteAttributeInteger('last_get_devices_timestamp', time());
        }
        elseif($url != self::LINK_TAP_BASE_URL . self::GET_ALL_DEVICES && $url == self::LINK_TAP_BASE_URL . self::GET_WATERING_STATUS)
        {
            $this->WriteAttributeInteger('last_command_timestamp', time());
        }
        return $this->getReturnValues($info, $response);
    }

    // LinkTap API

    private function getReturnValues(array $info, string $response)
    {
        $simulation = $this->ReadAttributeBoolean('simulation');
        if ($simulation) {
            $http_code = 200;
            $body = $response;
        } else {
            $HeaderSize = $info['header_size'];

            $http_code = $info['http_code'];
            $this->SendDebug(__FUNCTION__, 'Response (http_code): ' . $http_code, 0);

            $header = explode("\n", substr($response, 0, $HeaderSize));
            $this->SendDebug(__FUNCTION__, 'Response (header): ' . json_encode($header), 0);

            $body = substr($response, $HeaderSize);
            $this->SendDebug(__FUNCTION__, 'Response (body): ' . $body, 0);
        }
        if ($http_code == 200) {
            $this->SendDebug('HTTP Response', 'Success. Response Body: ' . $body, 0);
            $response = json_decode($body, true);
            $result = $response['result'];
            $this->SendDebug(__FUNCTION__, 'Response (result): ' . $result, 0);
            if (isset($response['message'])) {
                $linktap_response = $response['message'];
            } elseif (isset($response['status'])) {

                if($result == 'ok')
                {
                    if(empty($response['status']))
                    {
                        $linktap_response = $this->ReadAttributeString('watering_status');
                    }
                    else{
                        $linktap_response = json_encode($response['status']);
                        $this->WriteAttributeString('watering_status', json_encode($response['status']));
                    }
                }
            } elseif (isset($response['devices'])) {
                $linktap_response = json_encode($response['devices']);
                if($result == 'ok')
                {
                    $this->WriteAttributeString('devices', json_encode($response['devices']));
                }
            }
            else{
                $linktap_response = '[]';
            }
        } else {
            $this->SendDebug('HTTP Response Header', $http_code . ' Response Body: ' . $body, 0);
            $result = 'not ok';
            $this->SendDebug(__FUNCTION__, 'Response (result): ' . $result, 0);
            $linktap_response = '[]';

        }
        return $linktap_response;
    }

    protected function DelayCommand($url, $data)
    {
        $last_command_timestamp = $this->ReadAttributeInteger('last_command_timestamp');
        do {
            IPS_Sleep(1000);
            $current_time = time();
            $remaining_seconds = $current_time - $last_command_timestamp;
            $this->SendDebug('API Limit', 'reached API limit, waiting for ' . (15 - $remaining_seconds), 0);
        } while ($remaining_seconds < 15);

        $this->PostData($url, $data);
    }

    /** Get LinkTap Configuration
     * @return bool|false|string
     */
    public function GetConfiguration()
    {
        $devices = '[]';
        if ($this->ReadPropertyString('user') != '' && $this->ReadPropertyString('apikey') != '') {
            $data = $data = json_encode([
                'username' => $this->ReadPropertyString('user'),
                'apiKey' => $this->ReadPropertyString('apikey')
            ]);
            $devices = $this->Get_All_Devices_Info($data);
        }
        return $devices;
    }

    /** Get All Devices
     *
     * @param $data
     * @return string
     */
    protected function Get_All_Devices_Info($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::GET_ALL_DEVICES;
        $simulation = $this->ReadAttributeBoolean('simulation');
        if ($simulation) {
            $data = (object)array(
                'result' => 'ok',
                'devices' =>
                    array(
                        0 =>
                            (object)array(
                                'name' => 'Link-Tap-1-Gateway',
                                'location' => '12345 Ort, Deutschland',
                                'gatewayId' => '1234567890',
                                'status' => 'Connected',
                                'version' => 'B1234567890I_C1234567890',
                                'taplinker' =>
                                    array(
                                        0 =>
                                            (object)array(
                                                'taplinkerName' => 'Hauptanschluss',
                                                'taplinkerId' => '1234567890',
                                                'status' => 'Connected',
                                                'location' => 'Not specified',
                                                'version' => 'T1234567890',
                                                'signal' => '32%',
                                                'batteryStatus' => '100%',
                                                'workMode' => 'I',
                                                'plan' =>
                                                    (object)array(
                                                        'interval' => 1,
                                                        'Y' => 2020,
                                                        'X' => 6,
                                                        'Z' => 18,
                                                        'ecoOff' => 1,
                                                        'ecoOn' => 1,
                                                        'eco' => false,
                                                        'slot' =>
                                                            array(
                                                                0 =>
                                                                    (object)array(
                                                                        'H' => 7,
                                                                        'M' => 0,
                                                                        'D' => 840,
                                                                    ),
                                                            ),
                                                    ),
                                                'watering' => NULL,
                                                'vel' => 0,
                                                'fall' => false,
                                                'valveBroken' => false,
                                                'noWater' => false,
                                            ),
                                        1 =>
                                            (object)array(
                                                'taplinkerName' => 'Nebenanschluss',
                                                'taplinkerId' => '234567890',
                                                'status' => 'Connected',
                                                'location' => 'Not specified',
                                                'version' => 'T1234567890',
                                                'signal' => '36%',
                                                'batteryStatus' => '100%',
                                                'workMode' => 'M',
                                                'plan' =>
                                                    (object)array(
                                                        'duration' => 0,
                                                        'Y' => 2020,
                                                        'X' => 6,
                                                        'Z' => 20,
                                                        'H' => 10,
                                                        'M' => 35,
                                                        'ecoOff' => 2,
                                                        'ecoOn' => 1,
                                                        'eco' => true,
                                                        'action' => false,
                                                    ),
                                                'watering' => NULL,
                                                'vel' => 3506,
                                                'fall' => false,
                                                'valveBroken' => false,
                                                'noWater' => false,
                                            ),
                                    ),
                            ),
                    ),
            );
            $devices = $data->devices;
            $this->WriteAttributeString('devices', json_encode($devices));
            $this->SendDataToChildren(json_encode(Array("DataID" => "{F1F4C680-F5E4-F0E0-670D-0574FF253233}", "Buffer" => $devices)));
            return json_encode($devices);
        } else {
            $devices = $this->PostData($url, $data);
            $this->SendDataToChildren(json_encode(Array("DataID" => "{F1F4C680-F5E4-F0E0-670D-0574FF253233}", "Buffer" => $devices)));
            return $devices;
        }
    }

    public function ForwardData($data)
    {
        $data = json_decode($data);
        $response = '[]';
        $type = $data->Type;
        if (strlen($data->Payload) > 0) {
            if ($type == 'POST') {
                $this->SendDebug('ForwardData', $data->Endpoint . ', Payload: ' . $data->Payload, 0);
                $post_data = str_replace(['{USERNAME}', '{APIKEY}'], [$this->ReadPropertyString('user'), $this->ReadPropertyString('apikey')], $data->Payload);
                $this->SendDebug('ForwardData Post Data', $post_data, 0);
                if ($data->Endpoint == 'Watering_On') {
                    $response = $this->Send_Watering_On($post_data);
                } elseif ($data->Endpoint == 'Watering_On') {
                    $response = $this->Send_Watering_On($post_data);
                } elseif ($data->Endpoint == 'Watering_Off') {
                    $response = $this->Send_Watering_Off($post_data);
                } elseif ($data->Endpoint == 'ActivateIntervalMode') {
                    $response = $this->Send_ActivateIntervalMode($post_data);
                } elseif ($data->Endpoint == 'ActivateOddEvenMode') {
                    $response = $this->Send_ActivateOddEvenMode($post_data);
                } elseif ($data->Endpoint == 'ActivateSevenDayMode') {
                    $response = $this->Send_ActivateSevenDayMode($post_data);
                } elseif ($data->Endpoint == 'ActivateMonthMode') {
                    $response = $this->Send_ActivateMonthMode($post_data);
                } elseif ($data->Endpoint == 'Get_All_Devices') {
                    $response = $this->Get_All_Devices_Info($post_data);
                } elseif ($data->Endpoint == 'Get_All_Devices_Buffer') {
                    $response = $this->Get_All_Devices_Buffer();
                } elseif ($data->Endpoint == 'Watering_Status') {
                    $response = $this->Query_Watering_Status($post_data);
                } elseif ($data->Endpoint == 'apikey') {
                    $response = $this->CheckAPIKey();
                }
                $this->SendDebug('ForwardData Response', $response, 0);
            }
        } else {
            $this->SendDebug('ForwardData', 'Type: ' . $type . ', Endpoint: ' . $data->Endpoint, 0);
        }
        return $response;
    }

    /** Watering On
     * duration The watering duration (unit is minute) the range is from 1 minute to 1439 minutes.
     * @param $data
     * @return string
     */
    protected function Send_Watering_On($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_INSTANT_MODE;
        return $this->PostData($url, $data);
    }

    /** Watering Off
     * @param $data
     * @return string
     */
    protected function Send_Watering_Off($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_INSTANT_MODE;
        return $this->PostData($url, $data);
    }

    /** Activate Interval Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     * @param $data
     * @return string
     */
    protected function Send_ActivateIntervalMode($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_INTERVAL_MODE;
        return $this->PostData($url, $data);
    }

    /** Activate Odd-Even Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     * @param $data
     * @return string
     */
    protected function Send_ActivateOddEvenMode($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_ODD_EVEN_MODE;
        return $this->PostData($url, $data);
    }

    /** Activate Seven Day Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     * @param $data
     * @return string
     */
    protected function Send_ActivateSevenDayMode($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_SEVEN_DAY_MODE;
        return $this->PostData($url, $data);
    }

    /** Activate Month Mode
     * Rate limiting is applied for this API. The minimum interval of calling this API is 15 seconds.
     * @return string
     */
    protected function Send_ActivateMonthMode($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::ACTIVATE_MONTH_MODE;
        return $this->PostData($url, $data);
    }

    /** Get All Devices Buffer
     *
     * @return string
     */
    protected function Get_All_Devices_Buffer()
    {
        return $this->ReadAttributeString('devices');
    }

    /** Watering Status of a single Taplinker
     *
     * @return string
     */
    protected function Query_Watering_Status($data)
    {
        $url = self::LINK_TAP_BASE_URL . self::GET_WATERING_STATUS;
        $simulation = $this->ReadAttributeBoolean('simulation');
        if ($simulation) {
            $data = (object)array(
                'result' => 'ok',
                'status' =>
                    (object)array(
                        'onDuration' => '4',
                        'total' => '5',
                        'onStamp' => '1592651809576',
                        'firstDt' => '1592651749983',
                        'vel' => '0',
                        'pbThrd' => '25000',
                        'pcThrd' => '0',
                        'pDelay' => '60000',
                        'pbCnt' => '0',
                        'pcCnt' => '0',
                        'vol' => '0',
                    ),
            );
            $status = $data->status;
            return json_encode($status);
        } else {
            return $this->PostData($url, $data);
        }
    }

    protected function CheckAPIKey()
    {
        // $apikey = $this->ReadAttributeString('apikey');
        $apikey = $this->ReadPropertyString('apikey');
        $this->SendDebug('API Key', $apikey, 0);
        return $apikey;
    }

    /**
     * build configuration form
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        $Form = json_encode([
            'elements' => $this->FormHead(),
            'actions' => $this->FormActions(),
            'status' => $this->FormStatus()
        ]);
        $this->SendDebug('FORM', $Form, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return $Form;
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
        $form = [
            [
                'type' => 'Image',
                'image' => 'data:image/png;base64, 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'],
            [
                'type' => 'Label',
                'visible' => true,
                'caption' => 'LinkTap username and password:'
            ],
            [
                'name' => 'user',
                'type' => 'ValidationTextBox',
                'visible' => true,
                'caption' => 'LinkTap username'
            ],
            [
                'name' => 'password',
                'type' => 'PasswordTextBox',
                'visible' => true,
                'caption' => 'LinkTap password'
            ],
            [
                'type' => 'Label',
                'visible' => true,
                'caption' => 'LinkTap API key, can be generated at https://www.link-tap.com/#!/api-for-developers'
            ],
            [
                'name' => 'apikey',
                'type' => 'ValidationTextBox',
                'visible' => true,
                'caption' => 'LinkTap API key'
            ],
            [
                'type' => 'Label',
                'visible' => false,
                'label' => 'Update interval in minutes (minimum 15 minutes):'
            ],
            [
                'name' => 'UpdateInterval',
                'visible' => false,
                'type' => 'IntervalBox',
                'suffix' => 'minutes',
                'caption' => 'Update interval'
            ]
        ];
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
                'type' => 'Label',
                'visible' => true,
                'caption' => 'Read LinkTap configuration:'
            ],
            [
                'type' => 'Button',
                'visible' => true,
                'caption' => 'Read configuration',
                'onClick' => 'LINKTAP_GetConfiguration($id);'
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
                'caption' => 'configuration valid.'
            ],
            [
                'code' => IS_INACTIVE,
                'icon' => 'inactive',
                'caption' => 'interface closed.'
            ],
            [
                'code' => 201,
                'icon' => 'inactive',
                'caption' => 'Please follow the instructions.'
            ],
            [
                'code' => 202,
                'icon' => 'error',
                'caption' => 'no category selected.'
            ]
        ];

        return $form;
    }

    protected function CheckIntervalLastCommand()
    {
        $last_command_timestamp = $this->ReadAttributeInteger('last_command_timestamp');
        $current_time = time();
        $interval = $current_time - $last_command_timestamp;
        if ($interval > 15) {
            $check = true;
        } else {
            $check = false;
        }
        return $check;
    }
}